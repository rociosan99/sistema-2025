<?php

namespace App\Filament\Alumno\Pages;

use App\Models\Materia;
use App\Models\SolicitudDisponibilidad;
use App\Models\Tema;
use BackedEnum;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class SolicitudesDisponibilidad extends Page
{
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-bell-alert';
    protected static ?string $navigationLabel = 'Disponibilidad';
    protected static ?string $title = 'Solicitudes de disponibilidad';
    protected static ?string $slug = 'solicitudes-disponibilidad';

    protected string $view = 'filament.alumno.pages.solicitudes-disponibilidad';

    public ?int $materiaId = null;
    public ?int $temaId = null;

    public ?string $fecha = null;
    public ?string $horaInicio = null; // HH:MM
    public ?string $horaFin = null;    // HH:MM

    public ?string $expiresAt = null;  // datetime-local (opcional)

    /** @var array<int, array> */
    public array $misSolicitudes = [];

    public function mount(): void
    {
        $this->cargarMisSolicitudes();
    }

    public function cargarMisSolicitudes(): void
    {
        $this->misSolicitudes = SolicitudDisponibilidad::query()
            ->where('alumno_id', Auth::id())
            ->orderByDesc('created_at')
            ->with(['materia', 'tema'])
            ->get()
            ->map(fn ($s) => [
                'id' => $s->id,
                'estado' => $s->estado,
                'fecha' => $s->fecha->format('d/m/Y'),
                'hora_inicio' => substr((string) $s->hora_inicio, 0, 5),
                'hora_fin' => substr((string) $s->hora_fin, 0, 5),
                'materia' => $s->materia?->materia_nombre ?? '-',
                'tema' => $s->tema?->tema_nombre ?? 'Sin tema',
                'expires_at' => $s->expires_at?->format('d/m/Y H:i') ?? null,
            ])
            ->all();
    }

   public function crearSolicitud(): void
    {
        $carreraId = $this->carreraActivaId();

        if (! $carreraId) {
            throw ValidationException::withMessages([
                'materiaId' => 'Configurá una carrera activa en tu perfil antes de crear una solicitud.',
            ]);
        }

        if (! $this->materiaId) {
            throw ValidationException::withMessages([
                'materiaId' => 'Seleccioná una materia.'
            ]);
        }

        if (! $this->materiaPerteneceACarrera($this->materiaId, $carreraId)) {
            throw ValidationException::withMessages([
                'materiaId' => 'La materia seleccionada no pertenece a tu carrera activa.',
            ]);
        }

        if (
            $this->temaId
            && ! $this->temaPerteneceAMateriaYCarrera($this->temaId, $this->materiaId, $carreraId)
        ) {
            throw ValidationException::withMessages([
                'temaId' => 'El tema seleccionado no corresponde a la materia y carrera activas.',
            ]);
        }

        if (! $this->fecha) {
    throw ValidationException::withMessages([
        'fecha' => 'Seleccioná una fecha.'
    ]);
}

// No permitir fechas pasadas
$fechaSeleccionada = strtotime($this->fecha);
$hoy = strtotime(date('Y-m-d'));

if ($fechaSeleccionada < $hoy) {
    throw ValidationException::withMessages([
        'fecha' => 'No podés crear solicitudes para fechas pasadas.'
    ]);
}

// Si es hoy, la hora de inicio no puede haber pasado
    if ($this->fecha === date('Y-m-d') && $this->horaInicio) {
        $ahora = strtotime(date('H:i'));
        $horaInicio = strtotime($this->horaInicio);

        if ($horaInicio <= $ahora) {
            throw ValidationException::withMessages([
                'hora' => 'La hora de inicio debe ser posterior a la hora actual.'
            ]);
        }
    }

        if (! $this->horaInicio || ! $this->horaFin) {
            throw ValidationException::withMessages([
                'hora' => 'Ingresá hora inicio y fin.'
            ]);
        }

        if ($this->horaInicio >= $this->horaFin) {
            throw ValidationException::withMessages([
                'hora' => 'La hora fin debe ser mayor a la hora inicio.'
            ]);
        }

        // Solo horas exactas (15:00, 16:00, etc.)
        if (
            !str_ends_with($this->horaInicio, ':00') ||
            !str_ends_with($this->horaFin, ':00')
        ) {
            throw ValidationException::withMessages([
                'hora' => 'Solo se permiten horarios en horas exactas (ej: 15:00, 16:00).'
            ]);
        }

        // Duración mínima de 1 hora
        $inicio = strtotime($this->horaInicio);
        $fin = strtotime($this->horaFin);

        if (($fin - $inicio) < 3600) {
            throw ValidationException::withMessages([
                'hora' => 'La solicitud debe tener una duración mínima de 1 hora.'
            ]);
        }

        SolicitudDisponibilidad::create([
            'alumno_id' => Auth::id(),
            'materia_id' => $this->materiaId,
            'tema_id' => $this->temaId ?: null,
            'fecha' => $this->fecha,
            'hora_inicio' => $this->horaInicio . ':00',
            'hora_fin' => $this->horaFin . ':00',
            'estado' => SolicitudDisponibilidad::ESTADO_ACTIVA,
            'expires_at' => $this->expiresAt
                ? date('Y-m-d H:i:s', strtotime($this->expiresAt))
                : null,
        ]);

        Notification::make()
            ->title('Solicitud creada')
            ->body('Te avisaremos cuando aparezca un profesor disponible.')
            ->success()
            ->send();

        $this->temaId = null;
        $this->fecha = null;
        $this->horaInicio = null;
        $this->horaFin = null;
        $this->expiresAt = null;

        $this->cargarMisSolicitudes();
    }

    public function cancelarSolicitud(int $id): void
    {
        $s = SolicitudDisponibilidad::where('alumno_id', Auth::id())->findOrFail($id);

        if ($s->estado !== SolicitudDisponibilidad::ESTADO_ACTIVA) {
            Notification::make()->title('No se puede cancelar')->warning()->send();
            return;
        }

        $s->update(['estado' => SolicitudDisponibilidad::ESTADO_CANCELADA]);

        Notification::make()->title('Solicitud cancelada')->success()->send();
        $this->cargarMisSolicitudes();
    }

    // Helpers para selects
    public function getMateriasOptionsProperty(): array
    {
        $carreraId = $this->carreraActivaId();

        if (! $carreraId) {
            return [];
        }

        return Materia::query()
            ->join('programas', 'programas.programa_materia_id', '=', 'materias.materia_id')
            ->join('planes_estudio', 'planes_estudio.plan_id', '=', 'programas.programa_plan_id')
            ->where('planes_estudio.plan_carrera_id', $carreraId)
            ->select('materias.materia_id', 'materias.materia_nombre')
            ->distinct()
            ->orderBy('materias.materia_nombre')
            ->pluck('materias.materia_nombre', 'materias.materia_id')
            ->toArray();
    }

    public function getTemasOptionsProperty(): array
    {
        $carreraId = $this->carreraActivaId();

        if (! $carreraId || ! $this->materiaId) {
            return [];
        }

        return Tema::query()
            ->join('programa_tema', 'programa_tema.tema_id', '=', 'temas.tema_id')
            ->join('programas', 'programas.programa_id', '=', 'programa_tema.programa_id')
            ->join('planes_estudio', 'planes_estudio.plan_id', '=', 'programas.programa_plan_id')
            ->where('planes_estudio.plan_carrera_id', $carreraId)
            ->where('programas.programa_materia_id', $this->materiaId)
            ->select('temas.tema_id', 'temas.tema_nombre')
            ->distinct()
            ->orderBy('temas.tema_nombre')
            ->pluck('temas.tema_nombre', 'temas.tema_id')
            ->toArray();
    }

    public function getTieneCarreraActivaProperty(): bool
    {
        return $this->carreraActivaId() !== null;
    }

    public function updatedMateriaId(?int $materiaId): void
    {
        $this->temaId = null;
        $this->resetValidation(['materiaId', 'temaId']);
    }

    private function carreraActivaId(): ?int
    {
        $carreraId = Auth::user()?->carrera_activa_id;

        return $carreraId ? (int) $carreraId : null;
    }

    private function materiaPerteneceACarrera(int $materiaId, int $carreraId): bool
    {
        return Materia::query()
            ->join('programas', 'programas.programa_materia_id', '=', 'materias.materia_id')
            ->join('planes_estudio', 'planes_estudio.plan_id', '=', 'programas.programa_plan_id')
            ->where('materias.materia_id', $materiaId)
            ->where('planes_estudio.plan_carrera_id', $carreraId)
            ->exists();
    }

    private function temaPerteneceAMateriaYCarrera(int $temaId, int $materiaId, int $carreraId): bool
    {
        return Tema::query()
            ->join('programa_tema', 'programa_tema.tema_id', '=', 'temas.tema_id')
            ->join('programas', 'programas.programa_id', '=', 'programa_tema.programa_id')
            ->join('planes_estudio', 'planes_estudio.plan_id', '=', 'programas.programa_plan_id')
            ->where('temas.tema_id', $temaId)
            ->where('programas.programa_materia_id', $materiaId)
            ->where('planes_estudio.plan_carrera_id', $carreraId)
            ->exists();
    }
}
