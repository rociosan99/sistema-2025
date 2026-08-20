<?php

namespace App\Filament\Alumno\Pages;

use App\Filament\Alumno\Resources\Turnos\TurnoResource;
use App\Models\Turno;
use App\Services\AplicacionCreditoService;
use BackedEnum;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class CompletarPagoTurno extends Page
{
    protected static bool $shouldRegisterNavigation = false;
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-credit-card';
    protected static ?string $title = 'Completar pago';
    protected static ?string $slug = 'completar-pago/{record}';
    protected string $view = 'filament.alumno.pages.completar-pago-turno';

    public Turno $turno;

    /** @var array{precio_total:string, credito_disponible:string, credito_aplicable:string, diferencia:string, cubre_total:bool} */
    public array $resumen = [];
    public bool $usarCredito = false;

    public function mount(int|string $record): void
    {
        $this->turno = Turno::query()->findOrFail($record);
        abort_unless((int) $this->turno->alumno_id === (int) Auth::id(), 404);
        $this->actualizarResumen();
    }

    public function confirmarPagoConCredito(AplicacionCreditoService $servicio): void
    {
        if (! $this->usarCredito) {
            Notification::make()->title('Seleccioná Usar mi crédito')->warning()->send();
            return;
        }

        try {
            // Se recalcula en backend; ningún importe de la vista se usa para pagar.
            $resumenActual = $servicio->previsualizar($this->turno, (int) Auth::id());

            if (! $resumenActual['cubre_total']) {
                $this->resumen = $resumenActual;
                Notification::make()
                    ->title('El crédito disponible ya no cubre el total')
                    ->body('Actualizamos los importes. En esta etapa podés continuar pagando el total con Mercado Pago.')
                    ->warning()
                    ->send();
                return;
            }

            $servicio->pagarTotalmenteConCredito($this->turno, (int) Auth::id());

            Notification::make()
                ->title('Pago realizado con crédito')
                ->body('La clase quedó confirmada.')
                ->success()
                ->send();

            $this->redirect(TurnoResource::getUrl('index', panel: 'alumno'));
        } catch (ValidationException $exception) {
            Notification::make()
                ->title('No se pudo completar el pago')
                ->body(collect($exception->errors())->flatten()->first() ?? $exception->getMessage())
                ->danger()
                ->send();
        }
    }

    public function continuarConPagoMixto(AplicacionCreditoService $servicio): void
    {
        if (! $this->usarCredito) {
            Notification::make()->title('Seleccioná Usar mi crédito')->warning()->send();
            return;
        }

        try {
            // El servicio recalcula y congela la composición sin usar importes enviados por la vista.
            $servicio->reservarParaPagoParcial($this->turno, (int) Auth::id());
            $this->redirectRoute('mp.pagar', ['turno' => $this->turno->id]);
        } catch (ValidationException $exception) {
            $this->actualizarResumen();

            Notification::make()
                ->title('No se pudo iniciar el pago mixto')
                ->body(collect($exception->errors())->flatten()->first() ?? $exception->getMessage())
                ->danger()
                ->send();
        }
    }

    private function actualizarResumen(): void
    {
        $this->resumen = app(AplicacionCreditoService::class)
            ->previsualizar($this->turno, (int) Auth::id());
    }
}
