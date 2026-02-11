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
        if (! $this->materiaId) {
            throw ValidationException::withMessages(['materiaId' => 'Seleccioná una materia.']);
        }
        if (! $this->fecha) {
            throw ValidationException::withMessages(['fecha' => 'Seleccioná una fecha.']);
        }
        if (! $this->horaInicio || ! $this->horaFin) {
            throw ValidationException::withMessages(['hora' => 'Ingresá hora inicio y fin.']);
        }
        if ($this->horaInicio >= $this->horaFin) {
            throw ValidationException::withMessages(['hora' => 'La hora fin debe ser mayor a la hora inicio.']);
        }

        SolicitudDisponibilidad::create([
            'alumno_id' => Auth::id(),
            'materia_id' => $this->materiaId,
            'tema_id' => $this->temaId ?: null,
            'fecha' => $this->fecha,
            'hora_inicio' => $this->horaInicio . ':00',
            'hora_fin' => $this->horaFin . ':00',
            'estado' => SolicitudDisponibilidad::ESTADO_ACTIVA,
            'expires_at' => $this->expiresAt ? date('Y-m-d H:i:s', strtotime($this->expiresAt)) : null,
        ]);

        Notification::make()
            ->title('Solicitud creada')
            ->body('Te avisaremos cuando aparezca un profesor disponible.')
            ->success()
            ->send();

        // limpiar
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
        return Materia::orderBy('materia_nombre')->pluck('materia_nombre', 'materia_id')->toArray();
    }

    public function getTemasOptionsProperty(): array
    {
        return Tema::orderBy('tema_nombre')->pluck('tema_nombre', 'tema_id')->toArray();
    }
}
