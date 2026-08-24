<?php

namespace App\Filament\Profesor\Pages;

use App\Models\CalificacionAlumno;
use App\Models\Disponibilidad;
use App\Models\Materia;
use App\Models\OfertaSolicitud;
use App\Models\SolicitudDisponibilidad;
use App\Models\Turno;
use App\Models\User;
use App\Services\SolicitudMatchingService;
use BackedEnum;
use Carbon\Carbon;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;
use App\Mail\ProfesorRespondioTurno;

class OfertasSolicitudes extends Page
{
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-inbox';
    protected static ?string $navigationLabel = 'Oferta de alumnos';
    protected static ?string $title = 'Oferta de alumnos';
    protected static ?string $slug = 'ofertas-solicitudes';

    protected string $view = 'filament.profesor.pages.ofertas-solicitudes';

    public array $ofertas = [];

    public ?int $fAlumnoId = null;
    public ?int $fMateriaId = null;
    public ?string $fFechaDesde = null;
    public ?string $fFechaHasta = null;
    public bool $fSoloRecomendadas = false;

    public array $alumnosOptions = [];
    public array $materiasOptions = [];

    // 🔥 enlace del modal
    public ?string $enlaceClase = null;

    public ?int $ofertaSeleccionada = null;

    public function mount(): void
    {
        $this->cargarOpcionesFiltros();
        $this->cargar();
    }

    public function cargarOpcionesFiltros(): void
    {
        $profesorId = (int) Auth::id();

        $this->alumnosOptions = User::query()
            ->whereIn('id', function ($q) use ($profesorId) {
                $q->from('ofertas_solicitud')
                    ->join('solicitudes_disponibilidad', 'solicitudes_disponibilidad.id', '=', 'ofertas_solicitud.solicitud_id')
                    ->where('ofertas_solicitud.profesor_id', $profesorId)
                    ->where('ofertas_solicitud.estado', OfertaSolicitud::ESTADO_PENDIENTE)
                    ->select('solicitudes_disponibilidad.alumno_id');
            })
            ->pluck(DB::raw("CONCAT(name,' ',apellido)"), 'id')
            ->toArray();

        $materiaIds = DB::table('ofertas_solicitud')
            ->join('solicitudes_disponibilidad', 'solicitudes_disponibilidad.id', '=', 'ofertas_solicitud.solicitud_id')
            ->where('ofertas_solicitud.profesor_id', $profesorId)
            ->where('ofertas_solicitud.estado', OfertaSolicitud::ESTADO_PENDIENTE)
            ->pluck('solicitudes_disponibilidad.materia_id')
            ->filter()
            ->unique();

        $this->materiasOptions = Materia::whereIn('materia_id', $materiaIds)
            ->pluck('materia_nombre', 'materia_id')
            ->toArray();
    }

    public function cargar(): void
    {
        $this->validate([
            'fAlumnoId' => ['nullable', 'integer'],
            'fMateriaId' => ['nullable', 'integer'],
            'fFechaDesde' => ['nullable', 'date'],
            'fFechaHasta' => ['nullable', 'date', 'after_or_equal:fFechaDesde'],
            'fSoloRecomendadas' => ['boolean'],
        ], [
            'fFechaHasta.after_or_equal' => 'La fecha hasta no puede ser anterior a la fecha desde.',
        ]);

        $profesorId = (int) Auth::id();

        $rows = OfertaSolicitud::query()
            ->where('profesor_id', $profesorId)
            ->where('estado', OfertaSolicitud::ESTADO_PENDIENTE)
            ->when($this->fSoloRecomendadas, function ($query) {
                $query->whereNotNull('recommended_turno_id');
            })
            ->whereHas('solicitud', function ($query) {
                $query
                    ->when($this->fAlumnoId, function ($query, $alumnoId) {
                        $query->where('alumno_id', $alumnoId);
                    })
                    ->when($this->fMateriaId, function ($query, $materiaId) {
                        $query->where('materia_id', $materiaId);
                    })
                    ->when($this->fFechaDesde, function ($query, $fechaDesde) {
                        $query->whereDate('fecha', '>=', $fechaDesde);
                    })
                    ->when($this->fFechaHasta, function ($query, $fechaHasta) {
                        $query->whereDate('fecha', '<=', $fechaHasta);
                    });
            })
            ->with(['solicitud.materia', 'solicitud.tema', 'solicitud.alumno'])
            ->get();

        $visibles = [];

        foreach ($rows as $oferta) {

            $solicitud = $oferta->solicitud;
            if (!$solicitud) continue;

            if ($solicitud->estado !== SolicitudDisponibilidad::ESTADO_ACTIVA) continue;

            $fecha = $solicitud->fecha->toDateString();

            $slotInicio = $this->normalizarHora($oferta->hora_inicio ?? $solicitud->hora_inicio);
            $slotFin = $this->normalizarHora($oferta->hora_fin ?? $solicitud->hora_fin);

            $inicioClase = Carbon::parse("$fecha $slotInicio");

            $vencimiento = $oferta->expires_at->min($inicioClase);

            if ($vencimiento->lte(now())) continue;

            $visibles[] = [
                'id' => $oferta->id,
                'alumno_id' => (int) $solicitud->alumno_id,
                'alumno' => trim(($solicitud->alumno->name ?? '') . ' ' . ($solicitud->alumno->apellido ?? '')),
                'materia' => $solicitud->materia?->materia_nombre ?? '-',
                'tema' => $solicitud->tema?->tema_nombre ?? '-',
                'fecha' => $solicitud->fecha->format('d/m/Y'),
                'hora_inicio' => substr($slotInicio, 0, 5),
                'hora_fin' => substr($slotFin, 0, 5),
                'expires_at' => $vencimiento->format('d/m/Y H:i'),
            ];
        }

        $alumnoIds = collect($visibles)
            ->pluck('alumno_id')
            ->unique()
            ->values();

        $calificacionesPorAlumno = $alumnoIds->isEmpty()
            ? collect()
            : CalificacionAlumno::query()
                ->whereIn('alumno_id', $alumnoIds)
                ->select('alumno_id')
                ->selectRaw('AVG(estrellas) as promedio')
                ->selectRaw('COUNT(*) as cantidad')
                ->groupBy('alumno_id')
                ->get()
                ->keyBy('alumno_id');

        foreach ($visibles as &$ofertaVisible) {
            $resumen = $calificacionesPorAlumno->get($ofertaVisible['alumno_id']);

            $ofertaVisible['calificacion_promedio'] = $resumen
                ? round((float) $resumen->promedio, 1)
                : null;
            $ofertaVisible['calificaciones_cantidad'] = $resumen
                ? (int) $resumen->cantidad
                : 0;
        }
        unset($ofertaVisible);

        $this->ofertas = $visibles;
    }

    public function limpiarFiltros(): void
    {
        $this->reset([
            'fAlumnoId',
            'fMateriaId',
            'fFechaDesde',
            'fFechaHasta',
            'fSoloRecomendadas',
        ]);

        $this->resetValidation();
        $this->cargar();
    }

    // ✅ SIN parámetros raros
    public function aceptar(SolicitudMatchingService $matchingService): void
    {
        $this->validate([
            'enlaceClase' => ['required', 'url', 'max:2048'],
            'ofertaSeleccionada' => ['required', 'integer'],
        ]);

        $profesorId = (int) Auth::id();
        $ofertaId = (int) $this->ofertaSeleccionada;

        $ofertaReferencia = OfertaSolicitud::query()
            ->whereKey($ofertaId)
            ->where('profesor_id', $profesorId)
            ->firstOrFail(['id', 'solicitud_id']);

        try {
            $turno = DB::transaction(function () use (
                $ofertaId,
                $profesorId,
                $ofertaReferencia,
                $matchingService,
            ): Turno {
                // La solicitud es el punto comÃºn entre todos los profesores candidatos.
                // Bloquearla primero garantiza que solamente uno pueda ganarla.
                $solicitud = SolicitudDisponibilidad::query()
                    ->whereKey($ofertaReferencia->solicitud_id)
                    ->lockForUpdate()
                    ->firstOrFail();

                $oferta = OfertaSolicitud::query()
                    ->whereKey($ofertaId)
                    ->where('profesor_id', $profesorId)
                    ->where('solicitud_id', $solicitud->id)
                    ->lockForUpdate()
                    ->firstOrFail();

                if ($solicitud->estado !== SolicitudDisponibilidad::ESTADO_ACTIVA) {
                    throw ValidationException::withMessages([
                        'ofertaSeleccionada' => 'La solicitud ya fue tomada por otro profesor.',
                    ]);
                }

                if (
                    $oferta->estado !== OfertaSolicitud::ESTADO_PENDIENTE
                    || $oferta->expires_at->lte(now())
                    || ($solicitud->expires_at && $solicitud->expires_at->lte(now()))
                ) {
                    throw ValidationException::withMessages([
                        'ofertaSeleccionada' => 'La oferta ya no estÃ¡ vigente.',
                    ]);
                }

                $fecha = $solicitud->fecha->toDateString();
                $slotInicio = $this->normalizarHora($oferta->hora_inicio ?? $solicitud->hora_inicio);
                $slotFin = $this->normalizarHora($oferta->hora_fin ?? $solicitud->hora_fin);
                $inicioClase = Carbon::parse("{$fecha} {$slotInicio}");
                $finClase = Carbon::parse("{$fecha} {$slotFin}");

                if ($inicioClase->lte(now()) || $finClase->lte($inicioClase)) {
                    throw ValidationException::withMessages([
                        'ofertaSeleccionada' => 'El horario de esta oferta ya no estÃ¡ disponible.',
                    ]);
                }

                // Serializa aceptaciones simultÃ¡neas que involucren al mismo profesor o alumno.
                User::query()
                    ->whereIn('id', array_values(array_unique([
                        $profesorId,
                        (int) $solicitud->alumno_id,
                    ])))
                    ->orderBy('id')
                    ->lockForUpdate()
                    ->get(['id']);

                $profesorCompatible = $matchingService
                    ->profesoresCompatibles($solicitud, $slotInicio, $slotFin)
                    ->contains(fn (array $candidato): bool =>
                        (int) $candidato['profesor_id'] === $profesorId
                    );

                if (! $profesorCompatible) {
                    throw ValidationException::withMessages([
                        'ofertaSeleccionada' => 'Ya no tenÃ©s disponibilidad para este horario.',
                    ]);
                }

                $alumnoTieneSolapamiento = Turno::query()
                    ->where('alumno_id', $solicitud->alumno_id)
                    ->whereDate('fecha', $fecha)
                    ->whereIn('estado', [
                        Turno::ESTADO_PENDIENTE,
                        Turno::ESTADO_ACEPTADO,
                        Turno::ESTADO_PENDIENTE_PAGO,
                        Turno::ESTADO_CONFIRMADO,
                    ])
                    ->where('hora_inicio', '<', $slotFin)
                    ->where('hora_fin', '>', $slotInicio)
                    ->exists();

                if ($alumnoTieneSolapamiento) {
                    throw ValidationException::withMessages([
                        'ofertaSeleccionada' => 'El alumno ya tiene otro turno en ese horario.',
                    ]);
                }

                $precioPorHora = DB::table('profesor_materia')
                    ->where('profesor_id', $profesorId)
                    ->where('materia_id', $solicitud->materia_id)
                    ->lockForUpdate()
                    ->value('precio_por_hora');

                if ($precioPorHora === null || (float) $precioPorHora <= 0) {
                    throw ValidationException::withMessages([
                        'ofertaSeleccionada' => 'No hay un precio vÃ¡lido configurado para esta materia.',
                    ]);
                }

                $duracionMinutos = $inicioClase->diffInMinutes($finClase);
                $precioTotal = round((float) $precioPorHora * ($duracionMinutos / 60), 2);

                if ($precioTotal <= 0) {
                    throw ValidationException::withMessages([
                        'ofertaSeleccionada' => 'No se pudo calcular un precio vÃ¡lido para la clase.',
                    ]);
                }

                $turno = Turno::create([
                    'alumno_id' => $solicitud->alumno_id,
                    'profesor_id' => $profesorId,
                    'materia_id' => $solicitud->materia_id,
                    'tema_id' => $solicitud->tema_id,
                    'fecha' => $fecha,
                    'hora_inicio' => $slotInicio,
                    'hora_fin' => $slotFin,
                    'estado' => Turno::ESTADO_ACEPTADO,
                    'enlace_clase' => trim((string) $this->enlaceClase),
                    'precio_por_hora' => round((float) $precioPorHora, 2),
                    'precio_total' => $precioTotal,
                ]);

                $solicitud->update([
                    'estado' => SolicitudDisponibilidad::ESTADO_TOMADA,
                ]);

                $oferta->update([
                    'estado' => OfertaSolicitud::ESTADO_ACEPTADA,
                ]);

                OfertaSolicitud::query()
                    ->where('solicitud_id', $solicitud->id)
                    ->whereKeyNot($oferta->id)
                    ->where('estado', OfertaSolicitud::ESTADO_PENDIENTE)
                    ->update([
                        'estado' => OfertaSolicitud::ESTADO_EXPIRADA,
                    ]);

                return $turno->load(['alumno', 'profesor', 'materia', 'tema']);
            }, 3);
        } catch (ValidationException $exception) {
            Notification::make()
                ->title('La oferta ya no estÃ¡ disponible')
                ->body(collect($exception->errors())->flatten()->first())
                ->warning()
                ->send();

            $this->cargar();

            return;
        }

        if ($turno->alumno?->email) {
            Mail::to($turno->alumno->email)
                ->send(new ProfesorRespondioTurno($turno));
        }

        $this->enlaceClase = null;
        $this->ofertaSeleccionada = null;

        Notification::make()
            ->title('Oferta aceptada')
            ->success()
            ->send();

        $this->cargar();
    }

    public function abrirModal(int $id): void
    {
        $this->ofertaSeleccionada = $id;
        $this->enlaceClase = null;
    }

    public function rechazar(int $id): void
    {
        OfertaSolicitud::where('id', $id)
            ->update(['estado' => OfertaSolicitud::ESTADO_RECHAZADA]);

        $this->cargar();
    }

    private function normalizarHora($hora): string
    {
        $hora = trim((string) $hora);

        if (preg_match('/^\d{2}:\d{2}$/', $hora)) {
            return $hora . ':00';
        }

        return $hora;
    }
}
