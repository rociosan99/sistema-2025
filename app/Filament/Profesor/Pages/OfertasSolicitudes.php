<?php

namespace App\Filament\Profesor\Pages;

use App\Models\OfertaSolicitud;
use App\Models\SolicitudDisponibilidad;
use App\Models\Turno;
use BackedEnum;
use Carbon\Carbon;
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
        $this->ofertas = OfertaSolicitud::query()
            ->where('profesor_id', Auth::id())
            ->where('estado', OfertaSolicitud::ESTADO_PENDIENTE)
            ->where('expires_at', '>', now())
            ->with(['solicitud.materia', 'solicitud.tema', 'solicitud.alumno'])
            ->orderBy('expires_at')
            ->get()
            ->map(function (OfertaSolicitud $o) {
                $s = $o->solicitud;

                return [
                    'id' => $o->id,
                    'expires_at' => $o->expires_at->format('d/m/Y H:i'),
                    'solicitud_id' => $s->id,
                    'alumno' => $s->alumno?->name ?? '-',
                    'materia' => $s->materia?->materia_nombre ?? '-',
                    'tema' => $s->tema?->tema_nombre ?? 'Sin tema',
                    'fecha' => $s->fecha->format('d/m/Y'),
                    'hora_inicio' => substr((string) $s->hora_inicio, 0, 5),
                    'hora_fin' => substr((string) $s->hora_fin, 0, 5),
                ];
            })
            ->all();
    }

    public function rechazar(int $ofertaId): void
    {
        $oferta = OfertaSolicitud::where('profesor_id', Auth::id())->findOrFail($ofertaId);

        if ($oferta->estado !== OfertaSolicitud::ESTADO_PENDIENTE) {
            Notification::make()->title('Oferta ya procesada')->warning()->send();
            return;
        }

        $oferta->update(['estado' => OfertaSolicitud::ESTADO_RECHAZADA]);

        Notification::make()->title('Oferta rechazada')->success()->send();
        $this->cargar();
    }

    public function aceptar(int $ofertaId): void
    {
        $profesorId = Auth::id();

        DB::transaction(function () use ($ofertaId, $profesorId) {
            /** @var OfertaSolicitud $oferta */
            $oferta = OfertaSolicitud::where('profesor_id', $profesorId)
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
            $s = SolicitudDisponibilidad::lockForUpdate()->findOrFail($oferta->solicitud_id);

            if ($s->estado !== SolicitudDisponibilidad::ESTADO_ACTIVA) {
                throw new \RuntimeException('La solicitud ya no está activa.');
            }

            if ($s->expires_at && $s->expires_at->lte(now())) {
                $s->update(['estado' => SolicitudDisponibilidad::ESTADO_EXPIRADA]);
                throw new \RuntimeException('La solicitud expiró.');
            }

            // Validar que el profe sigue libre (anti carrera)
            $hayChoque = Turno::query()
                ->where('profesor_id', $profesorId)
                ->whereDate('fecha', $s->fecha->toDateString())
                ->whereIn('estado', ['pendiente', 'aceptado', 'pendiente_pago', 'confirmado'])
                ->where(function ($q) use ($s) {
                    $q->where('hora_inicio', '<', $s->hora_fin)
                      ->where('hora_fin', '>', $s->hora_inicio);
                })
                ->lockForUpdate()
                ->exists();

            if ($hayChoque) {
                throw new \RuntimeException('Ya no estás disponible en ese horario.');
            }

            // Crear turno: profe ya aceptó -> pendiente_pago
            Turno::create([
                'alumno_id'   => $s->alumno_id,
                'profesor_id' => $profesorId,
                'materia_id'  => $s->materia_id,
                'tema_id'     => $s->tema_id, // puede ser null (por eso la migración recomendada)
                'fecha'       => $s->fecha->toDateString(),
                'hora_inicio' => (string) $s->hora_inicio,
                'hora_fin'    => (string) $s->hora_fin,
                'estado'      => Turno::ESTADO_PENDIENTE_PAGO,
            ]);

            // Marcar solicitud y oferta
            $s->update(['estado' => SolicitudDisponibilidad::ESTADO_TOMADA]);
            $oferta->update(['estado' => OfertaSolicitud::ESTADO_ACEPTADA]);

            // Expirar otras ofertas pendientes de la misma solicitud
            OfertaSolicitud::query()
                ->where('solicitud_id', $s->id)
                ->where('id', '!=', $oferta->id)
                ->where('estado', OfertaSolicitud::ESTADO_PENDIENTE)
                ->update(['estado' => OfertaSolicitud::ESTADO_EXPIRADA]);
        });

        Notification::make()
            ->title('Oferta aceptada')
            ->body('Se creó el turno y el alumno podrá pagar.')
            ->success()
            ->send();

        $this->cargar();
    }
}
