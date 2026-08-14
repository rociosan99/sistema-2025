<?php

namespace App\Filament\Profesor\Pages;

use App\Models\CalificacionAlumno;
use App\Models\MotivoCalificacion;
use App\Models\Turno;
use App\Services\CalificacionAlumnoService;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
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

    /** @var array<int, array> */
    public array $propuestasReemplazo = [];

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

        $this->cargarPropuestasReemplazo();
    }

    private function cargarPropuestasReemplazo(): void
    {
        $turnos = Turno::query()
            ->where('reemplazo_profesor_propuesto_id', Auth::id())
            ->where('estado', Turno::ESTADO_SUSPENDIDO_PROFESOR)
            ->whereNotNull('reemplazo_expires_at')
            ->where('reemplazo_expires_at', '>', now())
            ->with([
                'alumno:id,name,apellido',
                'materia:materia_id,materia_nombre',
                'tema:tema_id,tema_nombre',
            ])
            ->orderBy('reemplazo_expires_at')
            ->get();

        $alumnoIds = $turnos->pluck('alumno_id')->unique()->values();

        $calificaciones = $alumnoIds->isEmpty()
            ? collect()
            : CalificacionAlumno::query()
                ->whereIn('alumno_id', $alumnoIds)
                ->select('alumno_id')
                ->selectRaw('AVG(estrellas) as promedio, COUNT(*) as cantidad')
                ->groupBy('alumno_id')
                ->get()
                ->keyBy('alumno_id');

        $this->propuestasReemplazo = $turnos
            ->map(function (Turno $turno) use ($calificaciones): array {
                $resumen = $calificaciones->get($turno->alumno_id);
                $cantidad = (int) ($resumen?->cantidad ?? 0);

                return [
                    'id' => (int) $turno->id,
                    'alumno' => trim(($turno->alumno?->name ?? '') . ' ' . ($turno->alumno?->apellido ?? '')) ?: 'Alumno',
                    'materia' => $turno->materia?->materia_nombre ?? '-',
                    'tema' => $turno->tema?->tema_nombre,
                    'fecha' => $turno->reemplazo_fecha?->format('d/m/Y') ?? '-',
                    'horario' => substr((string) $turno->reemplazo_hora_inicio, 0, 5) . ' - ' . substr((string) $turno->reemplazo_hora_fin, 0, 5),
                    'vence' => $turno->reemplazo_expires_at?->format('d/m/Y H:i') ?? '-',
                    'calificacion_promedio' => $cantidad > 0 ? round((float) $resumen->promedio, 1) : null,
                    'calificaciones_cantidad' => $cantidad,
                    'url' => ResolverPropuestaReemplazo::getUrl(['record' => $turno->id], panel: 'profesor'),
                ];
            })
            ->values()
            ->all();
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
                    ->label('1. Calificación')
                    ->options([
                        1 => '⭐',
                        2 => '⭐⭐',
                        3 => '⭐⭐⭐',
                        4 => '⭐⭐⭐⭐',
                        5 => '⭐⭐⭐⭐⭐',
                    ])
                    ->descriptions([
                        1 => 'Muy malo',
                        2 => 'Malo',
                        3 => 'Regular',
                        4 => 'Bueno',
                        5 => 'Excelente',
                    ])
                    ->inline(false)
                    ->live()
                    ->afterStateUpdated(fn (Set $set) => $set('motivos', []))
                    ->required(),
                CheckboxList::make('motivos')
                    ->label('2. Motivos')
                    ->options(function (Get $get): array {
                        $estrellas = (int) $get('estrellas');

                        if ($estrellas < 1 || $estrellas > 5) {
                            return [];
                        }

                        return MotivoCalificacion::query()
                            ->where('tipo_evaluado', MotivoCalificacion::TIPO_ALUMNO)
                            ->where('estrellas', $estrellas)
                            ->where('activo', true)
                            ->orderBy('orden')
                            ->orderBy('id')
                            ->pluck('descripcion', 'id')
                            ->toArray();
                    })
                    ->helperText(fn (Get $get): string => $get('estrellas')
                        ? 'Seleccioná al menos un motivo. Podés elegir más de uno.'
                        : 'Seleccioná una calificación para ver los motivos disponibles.')
                    ->columns([
                        'default' => 1,
                        'md' => 2,
                    ])
                    ->minItems(1)
                    ->required(),
                Textarea::make('comentario')
                    ->label('3. Comentario')
                    ->helperText('Compartí brevemente cómo fue el desempeño del alumno durante la clase.')
                    ->placeholder('Escribí tu evaluación del alumno...')
                    ->rows(5)
                    ->required()
                    ->maxLength(1000),
            ])
            ->action(function (
                array $data,
                array $arguments,
                CalificacionAlumnoService $calificacionAlumnoService,
            ) {
                $turnoId = (int) ($arguments['turno_id'] ?? 0);

                $calificacionAlumnoService->calificar(
                    profesorId: (int) Auth::id(),
                    turnoId: $turnoId,
                    estrellas: $data['estrellas'] ?? null,
                    motivoIds: $data['motivos'] ?? [],
                    comentario: $data['comentario'] ?? null,
                );

                Notification::make()->title('Calificación guardada.')->success()->send();

                // refrescar ambas secciones
                $this->cargarPendientesCalificar();
                $this->cargarAgendaSemana();
            });
    }
}
