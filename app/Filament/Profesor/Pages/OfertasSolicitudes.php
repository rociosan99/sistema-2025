<?php

namespace App\Filament\Profesor\Pages;

use App\Models\CalificacionAlumno;
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
use Illuminate\Support\Facades\Mail;
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
        $profesorId = (int) Auth::id();

        $rows = OfertaSolicitud::query()
            ->where('profesor_id', $profesorId)
            ->where('estado', OfertaSolicitud::ESTADO_PENDIENTE)
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

    // ✅ SIN parámetros raros
    public function aceptar(): void
    {
        $this->validate([
            'enlaceClase' => 'required|url'
        ]);

        $profesorId = (int) Auth::id();
        $ofertaId = $this->ofertaSeleccionada;

        DB::transaction(function () use ($ofertaId, $profesorId) {

            $oferta = OfertaSolicitud::where('profesor_id', $profesorId)
                ->with('solicitud.alumno')
                ->findOrFail($ofertaId);

            $solicitud = $oferta->solicitud;

            $fecha = $solicitud->fecha->toDateString();
            $slotInicio = $this->normalizarHora($oferta->hora_inicio ?? $solicitud->hora_inicio);
            $slotFin = $this->normalizarHora($oferta->hora_fin ?? $solicitud->hora_fin);

            $turno = Turno::create([
                'alumno_id' => $solicitud->alumno_id,
                'profesor_id' => $profesorId,
                'materia_id' => $solicitud->materia_id,
                'tema_id' => $solicitud->tema_id,
                'fecha' => $fecha,
                'hora_inicio' => $slotInicio,
                'hora_fin' => $slotFin,
                'estado' => Turno::ESTADO_PENDIENTE_PAGO,
                'enlace_clase' => $this->enlaceClase,
            ]);

            $oferta->update([
                'estado' => OfertaSolicitud::ESTADO_ACEPTADA,
            ]);

            if ($turno->alumno?->email) {
                Mail::to($turno->alumno->email)
                    ->send(new ProfesorRespondioTurno($turno));
            }
        });

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
