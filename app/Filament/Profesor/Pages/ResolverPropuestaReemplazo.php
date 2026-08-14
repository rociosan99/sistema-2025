<?php

namespace App\Filament\Profesor\Pages;

use App\Models\CalificacionAlumno;
use App\Models\Turno;
use App\Services\ReemplazoProfesorService;
use BackedEnum;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

class ResolverPropuestaReemplazo extends Page
{
    protected static bool $shouldRegisterNavigation = false;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-arrow-path-rounded-square';
    protected static ?string $title = 'Propuesta de reemplazo';
    protected static ?string $slug = 'propuesta-reemplazo/{record}';

    protected string $view = 'filament.profesor.pages.resolver-propuesta-reemplazo';

    public ?Turno $turno = null;
    public ?float $calificacionPromedio = null;
    public int $calificacionesCantidad = 0;
    public bool $propuestaVigente = false;
    public ?string $mensajeEstado = null;

    public function mount(int|string $record): void
    {
        $this->turno = $this->consultarTurno($record);

        abort_unless(
            (int) $this->turno->reemplazo_profesor_propuesto_id === (int) Auth::id(),
            404,
        );

        $this->cargarResumenCalificacion();
        $this->actualizarEstadoVisual();
    }

    public function aceptar(ReemplazoProfesorService $reemplazoService): void
    {
        try {
            $turno = Turno::query()->findOrFail($this->turno->getKey());

            $reemplazoService->aceptar($turno, (int) Auth::id());
        } catch (ValidationException|HttpExceptionInterface) {
            $this->marcarNoDisponible();

            return;
        }

        Notification::make()
            ->title('Propuesta aceptada')
            ->body('La clase ya fue incorporada a tus turnos confirmados.')
            ->success()
            ->send();

        $this->redirect(Dashboard::getUrl(panel: 'profesor'));
    }

    public function rechazar(ReemplazoProfesorService $reemplazoService): void
    {
        try {
            $turno = Turno::query()->findOrFail($this->turno->getKey());

            $reemplazoService->rechazar($turno, (int) Auth::id());
        } catch (ValidationException|HttpExceptionInterface) {
            $this->marcarNoDisponible();

            return;
        }

        Notification::make()
            ->title('Propuesta rechazada')
            ->body('El alumno podrá solicitar otro profesor reemplazante.')
            ->success()
            ->send();

        $this->redirect(Dashboard::getUrl(panel: 'profesor'));
    }

    private function actualizarEstadoVisual(): void
    {
        $this->propuestaVigente = app(ReemplazoProfesorService::class)
            ->tienePropuestaVigente($this->turno);

        $this->mensajeEstado = $this->propuestaVigente
            ? null
            : 'Esta propuesta venció o ya no está disponible.';
    }

    private function cargarResumenCalificacion(): void
    {
        $resumen = CalificacionAlumno::query()
            ->where('alumno_id', $this->turno->alumno_id)
            ->selectRaw('AVG(estrellas) as promedio, COUNT(*) as cantidad')
            ->first();

        $this->calificacionesCantidad = (int) ($resumen?->cantidad ?? 0);
        $this->calificacionPromedio = $this->calificacionesCantidad > 0
            ? round((float) $resumen->promedio, 1)
            : null;
    }

    private function marcarNoDisponible(): void
    {
        $this->propuestaVigente = false;
        $this->mensajeEstado = 'Esta propuesta venció, fue cancelada o ya no está disponible.';

        Notification::make()
            ->title('Propuesta no disponible')
            ->body('No se realizó ningún cambio en el turno.')
            ->warning()
            ->send();
    }

    private function consultarTurno(int|string $record): Turno
    {
        return Turno::query()
            ->with([
                'alumno:id,name,apellido',
                'materia:materia_id,materia_nombre',
                'tema:tema_id,tema_nombre',
            ])
            ->findOrFail($record);
    }
}
