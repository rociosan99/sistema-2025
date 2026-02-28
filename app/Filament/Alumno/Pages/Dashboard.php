<?php

namespace App\Filament\Alumno\Pages;

use App\Models\CalificacionProfesor;
use App\Models\Turno;
use App\Models\TurnoReemplazo;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class Dashboard extends Page
{
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-home';
    protected static ?string $navigationLabel = 'Dashboard';
    protected static ?string $title = 'Panel del Alumno';
    protected static ?string $slug = 'dashboard';

    protected string $view = 'filament.alumno.pages.dashboard';

    /** @var array<int, array> */
    public array $pendientes = [];

    /** @var array<int, array> */
    public array $invitacionesReemplazo = [];

    public function mount(): void
    {
        $this->cargarPendientes();
        $this->cargarInvitacionesReemplazo();
    }

    protected function getActions(): array
    {
        return [
            $this->calificarAction(),
            $this->aceptarReemplazoAction(),
            $this->rechazarReemplazoAction(),
        ];
    }

    private function cargarPendientes(): void
    {
        $alumnoId = Auth::id();

        $turnos = Turno::query()
            ->where('alumno_id', $alumnoId)
            ->where('estado', Turno::ESTADO_CONFIRMADO)
            ->with(['profesor', 'materia', 'tema', 'calificacionProfesor'])
            ->orderByDesc('fecha')
            ->get();

        $this->pendientes = $turnos
            ->filter(function (Turno $t) {
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

    private function cargarInvitacionesReemplazo(): void
    {
        $alumnoId = Auth::id();

        $rows = TurnoReemplazo::query()
            ->where('alumno_id', $alumnoId)
            ->where('estado', TurnoReemplazo::ESTADO_PENDIENTE)
            ->where(function ($q) {
                $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
            })
            ->with(['profesor', 'materia', 'tema'])
            ->orderBy('expires_at')
            ->limit(20)
            ->get();

        $this->invitacionesReemplazo = $rows->map(function (TurnoReemplazo $r) {
            $profesor = trim(($r->profesor?->name ?? '') . ' ' . ($r->profesor?->apellido ?? ''));
            return [
                'id' => $r->id,
                'fecha' => Carbon::parse($r->fecha)->format('d/m/Y'),
                'hora_inicio' => substr((string) $r->hora_inicio, 0, 5),
                'hora_fin' => substr((string) $r->hora_fin, 0, 5),
                'vence' => $r->expires_at ? $r->expires_at->format('d/m/Y H:i') : '-',
                'profesor' => $profesor !== '' ? $profesor : ($r->profesor?->name ?? 'Profesor'),
                'materia' => $r->materia?->materia_nombre ?? '-',
                'tema' => $r->tema?->tema_nombre ?? 'Sin tema',
            ];
        })->values()->all();
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

    private function aceptarReemplazoAction(): Action
    {
        return Action::make('aceptar_reemplazo')
            ->label('Aceptar reemplazo')
            ->requiresConfirmation()
            ->modalHeading('Aceptar esta clase')
            ->modalDescription('Si aceptás, se te asigna el turno y vas a poder pagarlo.')
            ->action(function (array $data, array $arguments) {
                $reemplazoId = (int) ($arguments['reemplazo_id'] ?? 0);
                $alumnoId = Auth::id();

                DB::transaction(function () use ($reemplazoId, $alumnoId) {

                    $r = TurnoReemplazo::query()
                        ->where('alumno_id', $alumnoId)
                        ->lockForUpdate()
                        ->findOrFail($reemplazoId);

                    if ($r->estado !== TurnoReemplazo::ESTADO_PENDIENTE) {
                        throw new \RuntimeException('Esta invitación ya fue procesada.');
                    }

                    if ($r->expires_at && $r->expires_at->lte(now())) {
                        $r->update(['estado' => TurnoReemplazo::ESTADO_EXPIRADO]);
                        throw new \RuntimeException('La invitación ya venció.');
                    }

                    // anti-choque: profesor sigue libre
                    $hayChoqueProfe = Turno::query()
                        ->where('profesor_id', $r->profesor_id)
                        ->whereDate('fecha', $r->fecha)
                        ->whereIn('estado', [
                            Turno::ESTADO_PENDIENTE,
                            Turno::ESTADO_ACEPTADO,
                            Turno::ESTADO_PENDIENTE_PAGO,
                            Turno::ESTADO_CONFIRMADO,
                        ])
                        ->where(function ($q) use ($r) {
                            $q->where('hora_inicio', '<', $r->hora_fin)
                              ->where('hora_fin', '>', $r->hora_inicio);
                        })
                        ->lockForUpdate()
                        ->exists();

                    if ($hayChoqueProfe) {
                        $r->update(['estado' => TurnoReemplazo::ESTADO_EXPIRADO]);
                        throw new \RuntimeException('Ese horario ya no está disponible.');
                    }

                    // crear turno para el alumno que acepta
                    Turno::create([
                        'alumno_id'   => $alumnoId,
                        'profesor_id' => $r->profesor_id,
                        'materia_id'  => $r->materia_id,
                        'tema_id'     => $r->tema_id,
                        'fecha'       => $r->fecha,
                        'hora_inicio' => $r->hora_inicio,
                        'hora_fin'    => $r->hora_fin,
                        'estado'      => Turno::ESTADO_PENDIENTE_PAGO,
                    ]);

                    // marcar invitación aceptada
                    $r->update(['estado' => TurnoReemplazo::ESTADO_ACEPTADO]);

                    // expirar otras invitaciones del mismo slot (para no duplicar)
                    TurnoReemplazo::query()
                        ->where('id', '!=', $r->id)
                        ->where('profesor_id', $r->profesor_id)
                        ->whereDate('fecha', $r->fecha)
                        ->where('hora_inicio', $r->hora_inicio)
                        ->where('hora_fin', $r->hora_fin)
                        ->where('estado', TurnoReemplazo::ESTADO_PENDIENTE)
                        ->update(['estado' => TurnoReemplazo::ESTADO_EXPIRADO]);
                });

                Notification::make()
                    ->title('¡Listo!')
                    ->body('Se te asignó la clase. Podés pagarla desde tus turnos.')
                    ->success()
                    ->send();

                $this->cargarInvitacionesReemplazo();
            });
    }

    private function rechazarReemplazoAction(): Action
    {
        return Action::make('rechazar_reemplazo')
            ->label('Rechazar reemplazo')
            ->requiresConfirmation()
            ->modalHeading('Rechazar esta clase')
            ->modalDescription('Si rechazás, esta invitación se descarta.')
            ->action(function (array $data, array $arguments) {
                $reemplazoId = (int) ($arguments['reemplazo_id'] ?? 0);

                $r = TurnoReemplazo::query()
                    ->where('alumno_id', Auth::id())
                    ->findOrFail($reemplazoId);

                if ($r->estado !== TurnoReemplazo::ESTADO_PENDIENTE) {
                    Notification::make()->title('Esta invitación ya fue procesada.')->warning()->send();
                    $this->cargarInvitacionesReemplazo();
                    return;
                }

                $r->update(['estado' => TurnoReemplazo::ESTADO_RECHAZADO]);

                Notification::make()->title('Invitación rechazada.')->success()->send();

                $this->cargarInvitacionesReemplazo();
            });
    }
}