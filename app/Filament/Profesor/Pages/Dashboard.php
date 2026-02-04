<?php

namespace App\Filament\Profesor\Pages;

use App\Models\CalificacionAlumno;
use App\Models\Turno;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Auth;

class Dashboard extends Page
{
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-home';
    protected static ?string $navigationLabel = 'Dashboard';
    protected static ?string $title = 'Panel del Profesor';
    protected static ?string $slug = 'dashboard';
    protected static ?int $navigationSort = -1;

    protected string $view = 'filament.profesor.pages.dashboard';

    public array $materias = [];
    public array $temas = [];

    /** ✅ NUEVO: Agenda semanal */
    public string $weekStart;        // lunes (YYYY-mm-dd)
    public array $agendaSemana = []; // [fecha => [turnos...]]

    /** ✅ Pendientes de calificar (alumnos) */
    public array $pendientesCalificar = [];

    public function mount(): void
    {
        $this->weekStart = now()->startOfWeek(Carbon::MONDAY)->toDateString();
        $this->cargarDashboard();
    }

    /**
     * ✅ Necesario para que funcione mountAction('calificar_alumno', ...)
     */
    protected function getActions(): array
    {
        return [
            $this->calificarAlumnoAction(),
        ];
    }

    private function cargarDashboard(): void
    {
        $user = Auth::user();

        // Materias / Temas que dicta el profesor (lo tenías)
        $this->materias = $user->materias()
            ->orderBy('materia_nombre')
            ->pluck('materia_nombre')
            ->toArray();

        $this->temas = $user->temas()
            ->orderBy('tema_nombre')
            ->pluck('tema_nombre')
            ->toArray();

        // ✅ Agenda semanal
        $this->cargarAgendaSemana();

        // ✅ Pendientes de calificar (confirmado + ya pasó + sin calificacion)
        $this->cargarPendientesCalificar();
    }

    private function cargarAgendaSemana(): void
    {
        $user = Auth::user();

        $inicio = Carbon::parse($this->weekStart)->startOfDay();          // lunes
        $fin    = Carbon::parse($this->weekStart)->addDays(6)->endOfDay(); // domingo

        $turnos = Turno::query()
            ->where('profesor_id', $user->id)
            ->whereBetween('fecha', [$inicio->toDateString(), $fin->toDateString()])
            ->where('estado', Turno::ESTADO_CONFIRMADO)
            ->with(['alumno', 'materia', 'tema'])
            ->orderBy('fecha')
            ->orderBy('hora_inicio')
            ->get();

        // Inicializar los 7 días (aunque no haya turnos)
        $dias = [];
        for ($i = 0; $i < 7; $i++) {
            $d = $inicio->copy()->addDays($i)->toDateString();
            $dias[$d] = [];
        }

        foreach ($turnos as $t) {
            $fecha = $t->fecha instanceof Carbon ? $t->fecha->toDateString() : (string) $t->fecha;

            $dias[$fecha][] = [
                'id' => $t->id,
                'inicio' => substr((string) $t->hora_inicio, 0, 5),
                'fin' => substr((string) $t->hora_fin, 0, 5),
                'alumno' => trim(($t->alumno?->name ?? '') . ' ' . ($t->alumno?->apellido ?? '')) ?: ($t->alumno?->name ?? '-'),
                'materia' => $t->materia?->materia_nombre ?? '-',
                'tema' => $t->tema?->tema_nombre ?? null,
            ];
        }

        $this->agendaSemana = $dias;
    }

    private function cargarPendientesCalificar(): void
    {
        $profesorId = Auth::id();

        $turnos = Turno::query()
            ->where('profesor_id', $profesorId)
            ->where('estado', Turno::ESTADO_CONFIRMADO)
            ->with(['alumno', 'materia', 'tema', 'calificacionAlumno'])
            ->orderByDesc('fecha')
            ->get();

        $ahora = now();

        $this->pendientesCalificar = $turnos
            ->filter(function (Turno $t) use ($ahora) {
                $fin = Carbon::parse($t->fecha->format('Y-m-d') . ' ' . $t->hora_fin);
                if ($fin->isFuture()) return false;
                if ($t->calificacionAlumno) return false;
                return true;
            })
            ->map(function (Turno $t) {
                return [
                    'id' => $t->id,
                    'fecha' => $t->fecha->format('d/m/Y'),
                    'hora_inicio' => substr((string) $t->hora_inicio, 0, 5),
                    'hora_fin' => substr((string) $t->hora_fin, 0, 5),
                    'alumno' => trim(($t->alumno?->name ?? '') . ' ' . ($t->alumno?->apellido ?? '')) ?: ($t->alumno?->name ?? '-'),
                    'materia' => $t->materia?->materia_nombre ?? '-',
                    'tema' => $t->tema?->tema_nombre ?? '-',
                    'alumno_id' => $t->alumno_id,
                ];
            })
            ->values()
            ->all();
    }

    public function semanaAnterior(): void
    {
        $this->weekStart = Carbon::parse($this->weekStart)->subWeek()->toDateString();
        $this->cargarAgendaSemana();
    }

    public function semanaSiguiente(): void
    {
        $this->weekStart = Carbon::parse($this->weekStart)->addWeek()->toDateString();
        $this->cargarAgendaSemana();
    }

    public function semanaActual(): void
    {
        $this->weekStart = now()->startOfWeek(Carbon::MONDAY)->toDateString();
        $this->cargarAgendaSemana();
    }

    public function calificarAlumnoAction(): Action
    {
        return Action::make('calificar_alumno')
            ->label('Calificar alumno')
            ->icon('heroicon-o-star')
            ->modalHeading('Calificar alumno')
            ->form([
                Radio::make('estrellas')
                    ->label('Tu calificación')
                    ->options([
                        1 => '⭐',
                        2 => '⭐⭐',
                        3 => '⭐⭐⭐',
                        4 => '⭐⭐⭐⭐',
                        5 => '⭐⭐⭐⭐⭐',
                    ])
                    ->required(),

                Textarea::make('comentario')
                    ->label('Comentario (opcional)')
                    ->rows(4)
                    ->maxLength(1000),
            ])
            ->action(function (array $data, array $arguments) {
                $turnoId = (int) ($arguments['turno_id'] ?? 0);

                $turno = Turno::with(['calificacionAlumno'])
                    ->where('profesor_id', Auth::id())
                    ->findOrFail($turnoId);

                $fin = Carbon::parse($turno->fecha->format('Y-m-d') . ' ' . $turno->hora_fin);

                if ($turno->estado !== Turno::ESTADO_CONFIRMADO) {
                    Notification::make()->title('Este turno no está confirmado/pagado.')->danger()->send();
                    return;
                }

                if ($fin->isFuture()) {
                    Notification::make()->title('Todavía no terminó la clase.')->warning()->send();
                    return;
                }

                if ($turno->calificacionAlumno) {
                    Notification::make()->title('Este turno ya fue calificado.')->warning()->send();
                    return;
                }

                CalificacionAlumno::create([
                    'turno_id' => $turno->id,
                    'profesor_id' => Auth::id(),
                    'alumno_id' => $turno->alumno_id,
                    'estrellas' => (int) $data['estrellas'],
                    'comentario' => $data['comentario'] ?? null,
                ]);

                Notification::make()->title('Calificación guardada.')->success()->send();

                // refrescar ambas secciones
                $this->cargarPendientesCalificar();
                $this->cargarAgendaSemana();
            });
    }
}
