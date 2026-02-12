<?php

namespace App\Filament\Profesor\Pages;

use App\Models\OfertaSolicitud;
use App\Models\SolicitudDisponibilidad;
use App\Models\Turno;
use BackedEnum;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class OfertasSolicitudes extends Page
{
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-inbox';
    protected static ?string $navigationLabel = 'Ofertas de alumnos';
    protected static ?string $title = 'Ofertas de solicitudes';
    protected static ?string $slug = 'ofertas-solicitudes';

    protected string $view = 'filament.profesor.pages.ofertas-solicitudes';

    /** @var array<int, array> */
    public array $ofertas = [];

    public function mount(): void
    {
        $this->cargar();
    }

    public function cargar(): void
    {
        $profesorId = (int) Auth::id();

        $rows = OfertaSolicitud::query()
            ->where('profesor_id', $profesorId)
            ->where('estado', OfertaSolicitud::ESTADO_PENDIENTE)
            ->where('expires_at', '>', now())
            ->with(['solicitud.materia', 'solicitud.tema', 'solicitud.alumno'])
            ->orderBy('expires_at')
            ->get();

        $visibles = [];

        foreach ($rows as $o) {
            $s = $o->solicitud;

            // Si no existe la solicitud o ya no está activa -> expirar
            if (! $s || $s->estado !== SolicitudDisponibilidad::ESTADO_ACTIVA) {
                $o->update(['estado' => OfertaSolicitud::ESTADO_EXPIRADA]);
                continue;
            }

            // Slot real de la oferta
            $slotInicio = (string) ($o->hora_inicio ?? $s->hora_inicio);
            $slotFin    = (string) ($o->hora_fin ?? $s->hora_fin);

            // ✅ LIMPIEZA: si ya hay un turno ocupando ese slot -> expirar
            $hayChoque = Turno::query()
                ->where('profesor_id', $profesorId)
                ->whereDate('fecha', $s->fecha->toDateString())
                ->whereIn('estado', [
                    Turno::ESTADO_PENDIENTE,
                    Turno::ESTADO_ACEPTADO,
                    Turno::ESTADO_PENDIENTE_PAGO,
                    Turno::ESTADO_CONFIRMADO,
                ])
                ->where(function ($q) use ($slotInicio, $slotFin) {
                    $q->where('hora_inicio', '<', $slotFin)
                      ->where('hora_fin', '>', $slotInicio);
                })
                ->exists();

            if ($hayChoque) {
                $o->update(['estado' => OfertaSolicitud::ESTADO_EXPIRADA]);
                continue;
            }

            // ✅ RECOMENDACIÓN:
            // Si existe un TURNO CANCELADO recientemente del profe en ese mismo slot,
            // marcamos la oferta como recomendada (porque “reemplaza” esa hora).
            $turnoCancelado = Turno::query()
                ->where('profesor_id', $profesorId)
                ->where('estado', Turno::ESTADO_CANCELADO)
                ->whereDate('fecha', $s->fecha->toDateString())
                ->where(function ($q) use ($slotInicio, $slotFin) {
                    $q->where('hora_inicio', '<', $slotFin)
                      ->where('hora_fin', '>', $slotInicio);
                })
                // “reciente”: usá updated_at porque es cuando pasó a cancelado
                ->where('updated_at', '>=', now()->subDays(2))
                ->orderByDesc('updated_at')
                ->first();

            $recomendada = (bool) $turnoCancelado;

            $visibles[] = [
                'id' => $o->id,
                'expires_at' => $o->expires_at?->format('d/m/Y H:i') ?? '-',
                'solicitud_id' => $s->id,

                'recomendada' => $recomendada,
                'recomendacion_texto' => $recomendada
                    ? 'Se liberó este horario por una cancelación'
                    : '—',

                'alumno' => $s->alumno?->name ?? '-',
                'materia' => $s->materia?->materia_nombre ?? '-',
                'tema' => $s->tema?->tema_nombre ?? 'Sin tema',
                'fecha' => $s->fecha->format('d/m/Y'),
                'hora_inicio' => substr((string) $slotInicio, 0, 5),
                'hora_fin' => substr((string) $slotFin, 0, 5),
            ];
        }

        // ✅ ORDEN: recomendadas primero, luego por vencimiento
        usort($visibles, function ($a, $b) {
            if (($a['recomendada'] ?? false) !== ($b['recomendada'] ?? false)) {
                return ($b['recomendada'] ?? false) <=> ($a['recomendada'] ?? false);
            }
            return strcmp((string) $a['expires_at'], (string) $b['expires_at']);
        });

        $this->ofertas = $visibles;
    }

    public function rechazar(int $ofertaId): void
    {
        $profesorId = (int) Auth::id();

        $oferta = OfertaSolicitud::query()
            ->where('profesor_id', $profesorId)
            ->findOrFail($ofertaId);

        if ($oferta->estado !== OfertaSolicitud::ESTADO_PENDIENTE) {
            Notification::make()->title('Oferta ya procesada')->warning()->send();
            $this->cargar();
            return;
        }

        $oferta->update(['estado' => OfertaSolicitud::ESTADO_RECHAZADA]);

        Notification::make()->title('Oferta rechazada')->success()->send();
        $this->cargar();
    }

    public function aceptar(int $ofertaId): void
    {
        $profesorId = (int) Auth::id();

        try {
            DB::transaction(function () use ($ofertaId, $profesorId) {

                /** @var OfertaSolicitud $oferta */
                $oferta = OfertaSolicitud::query()
                    ->where('profesor_id', $profesorId)
                    ->lockForUpdate()
                    ->with(['solicitud'])
                    ->findOrFail($ofertaId);

                if ($oferta->estado !== OfertaSolicitud::ESTADO_PENDIENTE) {
                    throw new \RuntimeException('Oferta ya procesada.');
                }

                if ($oferta->expires_at->lte(now())) {
                    $oferta->update(['estado' => OfertaSolicitud::ESTADO_EXPIRADA]);
                    throw new \RuntimeException('Oferta vencida.');
                }

                /** @var SolicitudDisponibilidad $s */
                $s = SolicitudDisponibilidad::query()
                    ->lockForUpdate()
                    ->findOrFail($oferta->solicitud_id);

                if ($s->estado !== SolicitudDisponibilidad::ESTADO_ACTIVA) {
                    $oferta->update(['estado' => OfertaSolicitud::ESTADO_EXPIRADA]);
                    throw new \RuntimeException('La solicitud ya no está activa.');
                }

                if ($s->expires_at && $s->expires_at->lte(now())) {
                    $s->update(['estado' => SolicitudDisponibilidad::ESTADO_EXPIRADA]);
                    $oferta->update(['estado' => OfertaSolicitud::ESTADO_EXPIRADA]);
                    throw new \RuntimeException('La solicitud expiró.');
                }

                $slotInicio = (string) ($oferta->hora_inicio ?? $s->hora_inicio);
                $slotFin    = (string) ($oferta->hora_fin ?? $s->hora_fin);

                $hayChoque = Turno::query()
                    ->where('profesor_id', $profesorId)
                    ->whereDate('fecha', $s->fecha->toDateString())
                    ->whereIn('estado', [
                        Turno::ESTADO_PENDIENTE,
                        Turno::ESTADO_ACEPTADO,
                        Turno::ESTADO_PENDIENTE_PAGO,
                        Turno::ESTADO_CONFIRMADO,
                    ])
                    ->where(function ($q) use ($slotInicio, $slotFin) {
                        $q->where('hora_inicio', '<', $slotFin)
                          ->where('hora_fin', '>', $slotInicio);
                    })
                    ->lockForUpdate()
                    ->exists();

                if ($hayChoque) {
                    $oferta->update(['estado' => OfertaSolicitud::ESTADO_EXPIRADA]);
                    throw new \RuntimeException('Ya no estás disponible en ese horario.');
                }

                Turno::create([
                    'alumno_id'   => $s->alumno_id,
                    'profesor_id' => $profesorId,
                    'materia_id'  => $s->materia_id,
                    'tema_id'     => $s->tema_id,
                    'fecha'       => $s->fecha->toDateString(),
                    'hora_inicio' => $slotInicio,
                    'hora_fin'    => $slotFin,
                    'estado'      => Turno::ESTADO_PENDIENTE_PAGO,
                ]);

                $oferta->update(['estado' => OfertaSolicitud::ESTADO_ACEPTADA]);

                OfertaSolicitud::query()
                    ->where('solicitud_id', $s->id)
                    ->where('hora_inicio', $slotInicio)
                    ->where('hora_fin', $slotFin)
                    ->where('id', '!=', $oferta->id)
                    ->where('estado', OfertaSolicitud::ESTADO_PENDIENTE)
                    ->update(['estado' => OfertaSolicitud::ESTADO_EXPIRADA]);

                OfertaSolicitud::query()
                    ->where('profesor_id', $profesorId)
                    ->where('estado', OfertaSolicitud::ESTADO_PENDIENTE)
                    ->whereHas('solicitud', function ($q) use ($s) {
                        $q->whereDate('fecha', $s->fecha->toDateString());
                    })
                    ->where(function ($q) use ($slotInicio, $slotFin) {
                        $q->where('hora_inicio', '<', $slotFin)
                          ->where('hora_fin', '>', $slotInicio);
                    })
                    ->update(['estado' => OfertaSolicitud::ESTADO_EXPIRADA]);
            });

            Notification::make()
                ->title('Oferta aceptada')
                ->body('Se creó el turno por ese horario y el alumno podrá pagar.')
                ->success()
                ->send();

        } catch (\Throwable $e) {
            Notification::make()
                ->title('No se pudo aceptar')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }

        $this->cargar();
    }
}
