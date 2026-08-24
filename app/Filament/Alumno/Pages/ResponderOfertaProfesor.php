<?php

namespace App\Filament\Alumno\Pages;

use App\Filament\Alumno\Resources\Turnos\TurnoResource;
use App\Models\Turno;
use BackedEnum;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ResponderOfertaProfesor extends Page
{
    protected static bool $shouldRegisterNavigation = false;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-user-check';

    protected static ?string $title = 'Propuesta de profesor';

    protected static ?string $slug = 'responder-oferta-profesor/{record}';

    protected string $view = 'filament.alumno.pages.responder-oferta-profesor';

    public Turno $turno;

    public bool $propuestaRechazada = false;

    public string $urlTurnos;

    public string $urlBuscarProfesor;

    public function mount(int|string $record): void
    {
        $this->urlTurnos = TurnoResource::getUrl('index', panel: 'alumno');
        $this->urlBuscarProfesor = SolicitudesDisponibilidad::getUrl(panel: 'alumno');
        $this->turno = $this->consultarTurno($record);

        abort_unless((int) $this->turno->alumno_id === (int) Auth::id(), 404);

        if ($this->turno->estado === Turno::ESTADO_RECHAZADO) {
            $this->propuestaRechazada = true;

            return;
        }

        if ($this->turno->estado === Turno::ESTADO_PENDIENTE_PAGO) {
            $this->redirect(CompletarPagoTurno::getUrl(
                ['record' => $this->turno->id],
                panel: 'alumno',
            ));

            return;
        }

        if (
            $this->turno->estado !== Turno::ESTADO_ACEPTADO
            || $this->turno->inicioDateTime()->lte(now())
        ) {
            Notification::make()
                ->title('La propuesta ya no estÃ¡ disponible')
                ->warning()
                ->send();

            $this->redirect($this->urlTurnos);
        }
    }

    public function aceptar(): void
    {
        try {
            $turnoId = DB::transaction(function (): int {
                $turno = $this->buscarTurnoBloqueado();

                if ($turno->estado === Turno::ESTADO_PENDIENTE_PAGO) {
                    return (int) $turno->id;
                }

                if ($turno->estado !== Turno::ESTADO_ACEPTADO) {
                    throw ValidationException::withMessages([
                        'turno' => 'La propuesta ya fue respondida o no estÃ¡ disponible.',
                    ]);
                }

                if ($turno->inicioDateTime()->lte(now())) {
                    throw ValidationException::withMessages([
                        'turno' => 'La clase ya comenzÃ³ y la propuesta no puede aceptarse.',
                    ]);
                }

                $turno->update([
                    'estado' => Turno::ESTADO_PENDIENTE_PAGO,
                ]);

                return (int) $turno->id;
            });
        } catch (ValidationException $exception) {
            $this->notificarError($exception);

            return;
        }

        Notification::make()
            ->title('Propuesta aceptada')
            ->body('Ahora podÃ©s completar el pago de la clase.')
            ->success()
            ->send();

        $this->redirect(CompletarPagoTurno::getUrl(
            ['record' => $turnoId],
            panel: 'alumno',
        ));
    }

    public function rechazar(): void
    {
        try {
            DB::transaction(function (): void {
                $turno = $this->buscarTurnoBloqueado();

                if ($turno->estado === Turno::ESTADO_RECHAZADO) {
                    return;
                }

                if ($turno->estado !== Turno::ESTADO_ACEPTADO) {
                    throw ValidationException::withMessages([
                        'turno' => 'La propuesta ya fue respondida o no estÃ¡ disponible.',
                    ]);
                }

                if ($turno->inicioDateTime()->lte(now())) {
                    throw ValidationException::withMessages([
                        'turno' => 'La clase ya comenzÃ³ y la propuesta no puede rechazarse.',
                    ]);
                }

                $turno->update([
                    'estado' => Turno::ESTADO_RECHAZADO,
                ]);
            });
        } catch (ValidationException $exception) {
            $this->notificarError($exception);

            return;
        }

        $this->turno = $this->consultarTurno($this->turno->id);
        $this->propuestaRechazada = true;

        Notification::make()
            ->title('Propuesta rechazada')
            ->success()
            ->send();
    }

    private function consultarTurno(int|string $record): Turno
    {
        return Turno::query()
            ->with([
                'profesor:id,name,apellido',
                'materia:materia_id,materia_nombre',
                'tema:tema_id,tema_nombre',
            ])
            ->findOrFail($record);
    }

    private function buscarTurnoBloqueado(): Turno
    {
        return Turno::query()
            ->whereKey($this->turno->id)
            ->where('alumno_id', Auth::id())
            ->lockForUpdate()
            ->firstOrFail();
    }

    private function notificarError(ValidationException $exception): void
    {
        Notification::make()
            ->title('No se pudo responder la propuesta')
            ->body(collect($exception->errors())->flatten()->first())
            ->warning()
            ->send();

        $this->turno = $this->consultarTurno($this->turno->id);
        $this->propuestaRechazada = $this->turno->estado === Turno::ESTADO_RECHAZADO;
    }
}
