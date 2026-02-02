<?php

namespace App\Filament\Alumno\Pages;

use App\Models\CalificacionProfesor;
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
    protected static ?string $title = 'Panel del Alumno';
    protected static ?string $slug = 'dashboard';

    protected string $view = 'filament.alumno.pages.dashboard';

    /** @var array<int, array> */
    public array $pendientes = [];

    public function mount(): void
    {
        $this->cargarPendientes();
    }

    /**
     * ✅ CLAVE: registramos la action para que mountAction funcione
     * (y no aparece como botón arriba porque NO usamos getHeaderActions)
     */
    protected function getActions(): array
    {
        return [
            $this->calificarAction(),
        ];
    }

    private function cargarPendientes(): void
    {
        $alumnoId = Auth::id();

        $turnos = Turno::query()
            ->where('alumno_id', $alumnoId)
            ->where('estado', Turno::ESTADO_CONFIRMADO) // solo pagadas
            ->with(['profesor', 'materia', 'tema', 'calificacionProfesor'])
            ->orderByDesc('fecha')
            ->get();

        $ahora = now();

        $this->pendientes = $turnos
            ->filter(function (Turno $t) use ($ahora) {
                $fin = Carbon::parse($t->fecha->format('Y-m-d') . ' ' . $t->hora_fin);
                if ($fin->isFuture()) return false;
                if ($t->calificacionProfesor) return false;
                return true;
            })
            ->map(function (Turno $t) {
                return [
                    'id' => $t->id,
                    'fecha' => $t->fecha->format('d/m/Y'),
                    'hora_inicio' => substr((string) $t->hora_inicio, 0, 5),
                    'hora_fin' => substr((string) $t->hora_fin, 0, 5),
                    'profesor' => trim(($t->profesor?->name ?? '') . ' ' . ($t->profesor?->apellido ?? '')),
                    'materia' => $t->materia?->materia_nombre ?? '-',
                    'tema' => $t->tema?->tema_nombre ?? '-',
                    'profesor_id' => $t->profesor_id,
                ];
            })
            ->values()
            ->all();
    }

    public function calificarAction(): Action
    {
        return Action::make('calificar')
            ->label('Calificar')
            ->icon('heroicon-o-star')
            ->modalHeading('Calificar clase')
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

                $turno = Turno::with(['calificacionProfesor'])
                    ->where('alumno_id', Auth::id())
                    ->findOrFail($turnoId);

                $fin = Carbon::parse($turno->fecha->format('Y-m-d') . ' ' . $turno->hora_fin);

                if ($turno->estado !== Turno::ESTADO_CONFIRMADO) {
                    Notification::make()->title('Este turno no está pagado.')->danger()->send();
                    return;
                }

                if ($fin->isFuture()) {
                    Notification::make()->title('Todavía no terminó la clase.')->warning()->send();
                    return;
                }

                if ($turno->calificacionProfesor) {
                    Notification::make()->title('Este turno ya fue calificado.')->warning()->send();
                    return;
                }

                CalificacionProfesor::create([
                    'turno_id' => $turno->id,
                    'alumno_id' => Auth::id(),
                    'profesor_id' => $turno->profesor_id,
                    'estrellas' => (int) $data['estrellas'],
                    'comentario' => $data['comentario'] ?? null,
                ]);

                Notification::make()->title('¡Gracias por tu calificación!')->success()->send();

                $this->cargarPendientes();
            });
    }
}
