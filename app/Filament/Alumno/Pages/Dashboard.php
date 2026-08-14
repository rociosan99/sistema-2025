<?php

namespace App\Filament\Alumno\Pages;

use App\Models\MotivoCalificacion;
use App\Models\Turno;
use App\Models\TurnoReemplazo;
use App\Services\CalificacionProfesorService;
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

    /** @var array<int, array{id:int, profesor:string, materia:string, fecha:string, horario:string, url:string}> */
    public array $suspensionesProfesor = [];

    public function mount(): void
    {
        $this->cargarPendientes();
        $this->cargarInvitacionesReemplazo();
        $this->cargarSuspensionesProfesor();
    }

    private function cargarSuspensionesProfesor(): void
    {
        $this->suspensionesProfesor = Turno::query()
            ->where('alumno_id', Auth::id())
            ->where('estado', Turno::ESTADO_SUSPENDIDO_PROFESOR)
            ->with([
                'profesor:id,name,apellido',
                'materia:materia_id,materia_nombre',
            ])
            ->orderBy('fecha')
            ->orderBy('hora_inicio')
            ->get()
            ->filter(fn (Turno $turno): bool => $turno->inicioDateTime()->isFuture())
            ->map(fn (Turno $turno): array => [
                'id' => (int) $turno->id,
                'profesor' => trim(($turno->profesor?->name ?? '') . ' ' . ($turno->profesor?->apellido ?? '')) ?: 'Profesor',
                'materia' => $turno->materia?->materia_nombre ?? '-',
                'fecha' => $turno->fecha->format('d/m/Y'),
                'horario' => substr((string) $turno->hora_inicio, 0, 5) . ' - ' . substr((string) $turno->hora_fin, 0, 5),
                'url' => ResolverSuspension::getUrl(['record' => $turno->id], panel: 'alumno'),
            ])
            ->values()
            ->all();
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
                            ->where('tipo_evaluado', MotivoCalificacion::TIPO_PROFESOR)
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
                    ->helperText('Compartí brevemente qué ocurrió durante la clase.')
                    ->placeholder('Escribí tu experiencia con el profesor...')
                    ->rows(5)
                    ->required()
                    ->maxLength(1000),
            ])
            ->action(function (
                array $data,
                array $arguments,
                CalificacionProfesorService $calificacionProfesorService,
            ) {
                $turnoId = (int) ($arguments['turno_id'] ?? 0);

                $calificacionProfesorService->calificar(
                    alumnoId: (int) Auth::id(),
                    turnoId: $turnoId,
                    estrellas: $data['estrellas'] ?? null,
                    motivoIds: $data['motivos'] ?? [],
                    comentario: $data['comentario'] ?? null,
                );

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
