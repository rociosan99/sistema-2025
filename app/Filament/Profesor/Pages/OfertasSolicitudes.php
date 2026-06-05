<?php

namespace App\Filament\Profesor\Pages;

use App\Models\Disponibilidad;
use App\Models\Materia;
use App\Models\OfertaSolicitud;
use App\Models\SolicitudDisponibilidad;
use App\Models\Turno;
use App\Models\User;
use BackedEnum;
use Carbon\Carbon;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class OfertasSolicitudes extends Page
{
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-inbox';
    protected static ?string $navigationLabel = 'Oferta de alumnos';
    protected static ?string $title = 'Oferta de alumnos';
    protected static ?string $slug = 'ofertas-solicitudes';

    protected string $view = 'filament.profesor.pages.ofertas-solicitudes';

    /** @var array<int, array> */
    public array $ofertas = [];

    public ?int $fAlumnoId = null;
    public ?int $fMateriaId = null;
    public ?string $fFechaDesde = null;
    public ?string $fFechaHasta = null;
    public bool $fSoloRecomendadas = false;

    public array $alumnosOptions = [];
    public array $materiasOptions = [];

    public function mount(): void
    {
        $this->cargarOpcionesFiltros();
        $this->cargar();
    }

    private function cargarOpcionesFiltros(): void
    {
        $profesorId = (int) Auth::id();

        $alumnos = User::query()
            ->whereIn('id', function ($q) use ($profesorId) {
                $q->from('ofertas_solicitud')
                    ->join('solicitudes_disponibilidad', 'solicitudes_disponibilidad.id', '=', 'ofertas_solicitud.solicitud_id')
                    ->where('ofertas_solicitud.profesor_id', $profesorId)
                    ->where('ofertas_solicitud.estado', OfertaSolicitud::ESTADO_PENDIENTE)
                    ->select('solicitudes_disponibilidad.alumno_id')
                    ->distinct();
            })
            ->orderBy('name')
            ->get(['id', 'name', 'apellido']);

        $this->alumnosOptions = $alumnos->mapWithKeys(function ($u) {
            $nombre = trim(($u->name ?? '') . ' ' . ($u->apellido ?? ''));
            return [$u->id => ($nombre !== '' ? $nombre : ($u->name ?? 'Alumno'))];
        })->toArray();

        $materiaIds = DB::table('ofertas_solicitud')
            ->join('solicitudes_disponibilidad', 'solicitudes_disponibilidad.id', '=', 'ofertas_solicitud.solicitud_id')
            ->where('ofertas_solicitud.profesor_id', $profesorId)
            ->where('ofertas_solicitud.estado', OfertaSolicitud::ESTADO_PENDIENTE)
            ->select('solicitudes_disponibilidad.materia_id')
            ->distinct()
            ->pluck('materia_id')
            ->filter()
            ->values()
            ->all();

        $this->materiasOptions = Materia::query()
            ->whereIn('materia_id', $materiaIds)
            ->orderBy('materia_nombre')
            ->pluck('materia_nombre', 'materia_id')
            ->toArray();
    }

    public function limpiarFiltros(): void
    {
        $this->fAlumnoId = null;
        $this->fMateriaId = null;
        $this->fFechaDesde = null;
        $this->fFechaHasta = null;
        $this->fSoloRecomendadas = false;

        $this->cargar();
    }

    public function cargar(): void
    {
        $profesorId = (int) Auth::id();

        $query = OfertaSolicitud::query()
            ->where('profesor_id', $profesorId)
            ->where('estado', OfertaSolicitud::ESTADO_PENDIENTE)
            ->where('expires_at', '>', now())
            ->with([
                'solicitud.materia',
                'solicitud.tema',
                'solicitud.alumno',
                'recommendedTurno',
            ])
            ->orderByDesc('recommended_turno_id')
            ->orderBy('expires_at');

        if ($this->fSoloRecomendadas) {
            $query->whereNotNull('recommended_turno_id');
        }

        if ($this->fAlumnoId) {
            $query->whereHas('solicitud', function ($q) {
                $q->where('alumno_id', $this->fAlumnoId);
            });
        }

        if ($this->fMateriaId) {
            $query->whereHas('solicitud', function ($q) {
                $q->where('materia_id', $this->fMateriaId);
            });
        }

        if ($this->fFechaDesde) {
            $query->whereHas('solicitud', function ($q) {
                $q->whereDate('fecha', '>=', $this->fFechaDesde);
            });
        }

        if ($this->fFechaHasta) {
            $query->whereHas('solicitud', function ($q) {
                $q->whereDate('fecha', '<=', $this->fFechaHasta);
            });
        }

        $rows = $query->get();
        $visibles = [];

        foreach ($rows as $oferta) {
            $solicitud = $oferta->solicitud;

            if (! $solicitud || $solicitud->estado !== SolicitudDisponibilidad::ESTADO_ACTIVA) {
                $oferta->update(['estado' => OfertaSolicitud::ESTADO_EXPIRADA]);
                continue;
            }

            $fecha = $solicitud->fecha->toDateString();
            $slotInicio = $this->normalizarHora((string) ($oferta->hora_inicio ?? $solicitud->hora_inicio));
            $slotFin = $this->normalizarHora((string) ($oferta->hora_fin ?? $solicitud->hora_fin));

            // 🌟 LÓGICA DE NEGOCIO INCORPORADA: Regla de Oro para Vencimientos
            // Armamos el inicio exacto de la clase particular
            $inicioClaseDatetime = Carbon::parse($fecha . ' ' . $slotInicio);

            // Si la oferta de base de datos dice que vence después del inicio de la clase,
            // o si la clase ya empezó en tiempo real, se fuerza la expiración inmediata en la BD.
            if ($oferta->expires_at->gt($inicioClaseDatetime) || $inicioClaseDatetime->lte(now())) {
                $oferta->update([
                    'estado' => OfertaSolicitud::ESTADO_EXPIRADA,
                    'expires_at' => $inicioClaseDatetime->min($oferta->expires_at) 
                ]);
                continue;
            }

            if ($solicitud->expires_at && $solicitud->expires_at->lte(now())) {
                $solicitud->update(['estado' => SolicitudDisponibilidad::ESTADO_EXPIRADA]);
                $oferta->update(['estado' => OfertaSolicitud::ESTADO_EXPIRADA]);
                continue;
            }

            // No mostrar horarios que ya empezaron o pasaron.
            if ($this->slotYaNoEsAceptable($fecha, $slotInicio)) {
                $oferta->update(['estado' => OfertaSolicitud::ESTADO_EXPIRADA]);
                continue;
            }

            // Si el profesor cambió disponibilidad y ya no cubre el slot, expirar.
            if (! $this->profesorCubreSlot($profesorId, $fecha, $slotInicio, $slotFin)) {
                $oferta->update(['estado' => OfertaSolicitud::ESTADO_EXPIRADA]);
                continue;
            }

            if ($this->hayChoqueProfesor($profesorId, $fecha, $slotInicio, $slotFin)) {
                $oferta->update(['estado' => OfertaSolicitud::ESTADO_EXPIRADA]);
                continue;
            }

            if ($this->hayChoqueAlumno((int) $solicitud->alumno_id, $fecha, $slotInicio, $slotFin)) {
                $oferta->update(['estado' => OfertaSolicitud::ESTADO_EXPIRADA]);
                continue;
            }

            $recomendada = ! empty($oferta->recommended_turno_id);
            $texto = $recomendada
                ? 'Se liberó este horario por una cancelación'
                : 'Match generado por disponibilidad e interés del alumno';

            $alumnoNombre = trim(($solicitud->alumno?->name ?? '') . ' ' . ($solicitud->alumno?->apellido ?? ''));

            if ($alumnoNombre === '') {
                $alumnoNombre = $solicitud->alumno?->name ?? '-';
            }

            // Leemos los minutos de cortesía desde el archivo .env (default: 60 minutos)
            $minutosConfig = (int) env('OFERTA_VENCIMIENTO_MINUTOS', 60);
            $vencimientoDinamicoEstandar = $oferta->created_at ? $oferta->created_at->copy()->addMinutes($minutosConfig) : now()->addMinutes($minutosConfig);
            
            // Evaluamos visualmente el menor vencimiento real aplicable
            $vencimientoRealCálculo = $vencimientoDinamicoEstandar->min($inicioClaseDatetime);

            $visibles[] = [
                'id' => $oferta->id,
                'expires_at' => $vencimientoRealCálculo->format('d/m/Y H:i'),
                'solicitud_id' => $solicitud->id,

                'recomendada' => $recomendada,
                'recomendacion_texto' => $texto,

                'alumno' => $alumnoNombre,
                'materia' => $solicitud->materia?->materia_nombre ?? '-',
                'tema' => $solicitud->tema?->tema_nombre ?? 'Sin tema',
                'fecha' => $solicitud->fecha->format('d/m/Y'),
                'hora_inicio' => substr($slotInicio, 0, 5),
                'hora_fin' => substr($slotFin, 0, 5),
            ];
        }

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
            Notification::make()
                ->title('Oferta ya procesada')
                ->warning()
                ->send();

            $this->cargar();
            return;
        }

        $oferta->update([
            'estado' => OfertaSolicitud::ESTADO_RECHAZADA,
        ]);

        Notification::make()
            ->title('Oferta Docente rechazada')
            ->success()
            ->send();

        $this->cargar();
    }

    public function aceptar(int $ofertaId): void
    {
        $profesorId = (int) Auth::id();

        try {
            DB::transaction(function () use ($ofertaId, $profesorId) {
                $oferta = OfertaSolicitud::query()
                    ->where('profesor_id', $profesorId)
                    ->lockForUpdate()
                    ->with(['solicitud'])
                    ->findOrFail($ofertaId);

                if ($oferta->estado !== OfertaSolicitud::ESTADO_PENDIENTE) {
                    throw new \RuntimeException('La oferta ya fue procesada.');
                }

                $solicitud = SolicitudDisponibilidad::query()
                    ->lockForUpdate()
                    ->findOrFail($oferta->solicitud_id);

                $fecha = $solicitud->fecha->toDateString();
                $slotInicio = $this->normalizarHora((string) ($oferta->hora_inicio ?? $solicitud->hora_inicio));
                $slotFin = $this->normalizarHora((string) ($oferta->hora_fin ?? $solicitud->hora_fin));

                // 🌟 CERROJO DE SEGURIDAD ESTRICTO EN TRANSACCIÓN:
                // Si la clase ya comenzó en el tiempo real, se aborta la creación del turno.
                $inicioClaseDatetime = Carbon::parse($fecha . ' ' . $slotInicio);
                if ($inicioClaseDatetime->lte(now()) || $oferta->expires_at->lte(now())) {
                    $oferta->update(['estado' => OfertaSolicitud::ESTADO_EXPIRADA]);
                    throw new \RuntimeException('El horario de esta clase ya comenzó o la oferta expiró.');
                }

                if ($solicitud->estado !== SolicitudDisponibilidad::ESTADO_ACTIVA) {
                    $oferta->update(['estado' => OfertaSolicitud::ESTADO_EXPIRADA]);
                    throw new \RuntimeException('La solicitud ya no está activa.');
                }

                if ($solicitud->expires_at && $solicitud->expires_at->lte(now())) {
                    $solicitud->update(['estado' => SolicitudDisponibilidad::ESTADO_EXPIRADA]);
                    $oferta->update(['estado' => OfertaSolicitud::ESTADO_EXPIRADA]);
                    throw new \RuntimeException('La solicitud expiró.');
                }

                if ($this->slotYaNoEsAceptable($fecha, $slotInicio)) {
                    $oferta->update(['estado' => OfertaSolicitud::ESTADO_EXPIRADA]);
                    throw new \RuntimeException('El horario de esta oferta ya pasó.');
                }

                if (! $this->profesorCubreSlot($profesorId, $fecha, $slotInicio, $slotFin)) {
                    $oferta->update(['estado' => OfertaSolicitud::ESTADO_EXPIRADA]);
                    throw new \RuntimeException('Ya no tenés disponibilidad para ese horario.');
                }

                if ($this->hayChoqueProfesor($profesorId, $fecha, $slotInicio, $slotFin)) {
                    $oferta->update(['estado' => OfertaSolicitud::ESTADO_EXPIRADA]);
                    throw new \RuntimeException('Ya tenés un turno en ese horario.');
                }

                if ($this->hayChoqueAlumno((int) $solicitud->alumno_id, $fecha, $slotInicio, $slotFin)) {
                    $oferta->update(['estado' => OfertaSolicitud::ESTADO_EXPIRADA]);
                    throw new \RuntimeException('El alumno ya tiene un turno en ese horario.');
                }

                Turno::create([
                    'alumno_id' => $solicitud->alumno_id,
                    'profesor_id' => $profesorId,
                    'materia_id' => $solicitud->materia_id,
                    'tema_id' => $solicitud->tema_id,
                    'fecha' => $fecha,
                    'hora_inicio' => $slotInicio,
                    'hora_fin' => $slotFin,
                    'estado' => Turno::ESTADO_PENDIENTE_PAGO,
                ]);

                $oferta->update([
                    'estado' => OfertaSolicitud::ESTADO_ACEPTADA,
                ]);

                $solicitud->update([
                    'estado' => SolicitudDisponibilidad::ESTADO_TOMADA,
                ]);

                OfertaSolicitud::query()
                    ->where('solicitud_id', $solicitud->id)
                    ->where('id', '!=', $oferta->id)
                    ->where('estado', OfertaSolicitud::ESTADO_PENDIENTE)
                    ->update([
                        'estado' => OfertaSolicitud::ESTADO_EXPIRADA,
                    ]);

                OfertaSolicitud::query()
                    ->where('profesor_id', $profesorId)
                    ->where('estado', OfertaSolicitud::ESTADO_PENDIENTE)
                    ->whereHas('solicitud', function ($q) use ($fecha) {
                        $q->whereDate('fecha', $fecha);
                    })
                    ->where(function ($q) use ($slotInicio, $slotFin) {
                        $q->where('hora_inicio', '<', $slotFin)
                            ->where('hora_fin', '>', $slotInicio);
                    })
                    ->update([
                        'estado' => OfertaSolicitud::ESTADO_EXPIRADA,
                    ]);
            });

            Notification::make()
                ->title('Oferta aceptada con éxito')
                ->body('Se creó el turno y el alumno podrá pagarlo.')
                ->success()
                ->send();
        } catch (\Throwable $e) {
            Notification::make()
                ->title('No se pudo procesar la oferta')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }

        $this->cargar();
    }

    private function profesorCubreSlot(int $profesorId, string $fecha, string $horaInicio, string $horaFin): bool
    {
        $diaSemana = Carbon::parse($fecha)->dayOfWeekIso;

        return Disponibilidad::query()
            ->where('profesor_id', $profesorId)
            ->where('dia_semana', $diaSemana)
            ->where('hora_inicio', '<=', $horaInicio)
            ->where('hora_fin', '>=', $horaFin)
            ->exists();
    }

    private function slotYaNoEsAceptable(string $fecha, string $horaInicio): bool
    {
        return Carbon::parse($fecha . ' ' . $this->normalizarHora($horaInicio))->lte(now());
    }

    private function hayChoqueProfesor(int $profesorId, string $fecha, string $horaInicio, string $horaFin): bool
    {
        return Turno::query()
            ->where('profesor_id', $profesorId)
            ->whereDate('fecha', $fecha)
            ->whereIn('estado', [
                Turno::ESTADO_PENDIENTE,
                Turno::ESTADO_ACEPTADO,
                Turno::ESTADO_PENDIENTE_PAGO,
                Turno::ESTADO_CONFIRMADO,
            ])
            ->where(function ($q) use ($horaInicio, $horaFin) {
                $q->where('hora_inicio', '<', $horaFin)
                    ->where('hora_fin', '>', $horaInicio);
            })
            ->exists();
    }

    private function hayChoqueAlumno(int $alumnoId, string $fecha, string $horaInicio, string $horaFin): bool
    {
        return Turno::query()
            ->where('alumno_id', $alumnoId)
            ->whereDate('fecha', $fecha)
            ->whereIn('estado', [
                Turno::ESTADO_PENDIENTE,
                Turno::ESTADO_ACEPTADO,
                Turno::ESTADO_PENDIENTE_PAGO,
                Turno::ESTADO_CONFIRMADO,
            ])
            ->where(function ($q) use ($horaInicio, $horaFin) {
                $q->where('hora_inicio', '<', $horaFin)
                    ->where('hora_fin', '>', $horaInicio);
            })
            ->exists();
    }

    private function normalizarHora(string $hora): string
    {
        $hora = trim($hora);

        if (preg_match('/^\d{4}-\d{2}-\d{2}\s+(\d{2}:\d{2}:\d{2})$/', $hora, $m)) {
            return $m[1];
        }

        if (preg_match('/^\d{2}:\d{2}$/', $hora)) {
            return $hora . ':00';
        }

        return $hora;
    }
}