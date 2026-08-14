<?php

namespace App\Filament\Alumno\Pages;

use App\Mail\PropuestaReemplazoProfesor;
use App\Models\Pago;
use App\Models\Turno;
use App\Services\ReemplazoProfesorService;
use App\Services\SlotService;
use BackedEnum;
use Carbon\Carbon;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;

class ResolverSuspension extends Page
{
    protected static bool $shouldRegisterNavigation = false;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-arrow-path';
    protected static ?string $title = 'Resolver suspensión';
    protected static ?string $slug = 'resolver-suspension/{record}';

    protected string $view = 'filament.alumno.pages.resolver-suspension';

    public ?Turno $turno = null;

    /** @var array<string, mixed>|null */
    public ?array $candidato = null;

    public bool $propuestaVigente = false;
    public bool $propuestaAnteriorVencida = false;
    public ?string $mensajeEstado = null;

    public function mount(int|string $record): void
    {
        $this->turno = Turno::query()
            ->with([
                'profesor:id,name,apellido',
                'materia:materia_id,materia_nombre',
                'tema:tema_id,tema_nombre',
                'pago',
                'profesorReemplazoPropuesto:id,name,apellido',
            ])
            ->findOrFail($record);

        abort_unless((int) $this->turno->alumno_id === (int) Auth::id(), 404);

        $this->cargarEstado();
    }

    public function solicitarReemplazo(
        SlotService $slotService,
        ReemplazoProfesorService $reemplazoService,
    ): void {
        $this->recargarTurno();
        $this->validarTurnoResoluble();

        if ($reemplazoService->tienePropuestaVigente($this->turno)) {
            $this->cargarEstado();

            Notification::make()
                ->title('Ya existe una propuesta vigente')
                ->warning()
                ->send();

            return;
        }

        $candidato = $this->buscarCandidato($slotService);

        if ($candidato === null) {
            $this->candidato = null;

            Notification::make()
                ->title('El reemplazante ya no está disponible')
                ->body('Volvimos a comprobar el horario original y no encontramos otro profesor disponible.')
                ->warning()
                ->send();

            return;
        }

        $turnoConPropuesta = $reemplazoService->proponer(
            $this->turno,
            (int) Auth::id(),
            $candidato,
        );

        $correoEnviado = false;

        try {
            $turnoConPropuesta->loadMissing('profesorReemplazoPropuesto');
            $emailProfesor = $turnoConPropuesta->profesorReemplazoPropuesto?->email;

            if ($emailProfesor) {
                Mail::to($emailProfesor)->send(new PropuestaReemplazoProfesor($turnoConPropuesta));
                $correoEnviado = true;
            }
        } catch (\Throwable $exception) {
            report($exception);
        }

        $this->recargarTurno();
        $this->cargarEstado();

        $notificacion = Notification::make()
            ->title('Propuesta enviada')
            ->body($correoEnviado
                ? 'El profesor recibió un correo y deberá responder antes del vencimiento.'
                : 'La propuesta quedó disponible en el Dashboard del profesor, pero no se pudo enviar el correo.');

        $correoEnviado ? $notificacion->success() : $notificacion->warning();
        $notificacion->send();
    }

    public function cancelarPropuesta(ReemplazoProfesorService $reemplazoService): void
    {
        $this->recargarTurno();

        $reemplazoService->cancelarPropuesta($this->turno, (int) Auth::id());

        $this->recargarTurno();
        $this->cargarEstado();

        Notification::make()
            ->title('Propuesta cancelada')
            ->body('Ya podés reprogramar con el profesor original o solicitar otro reemplazo.')
            ->success()
            ->send();
    }

    private function cargarEstado(): void
    {
        $this->candidato = null;
        $this->propuestaVigente = false;
        $this->propuestaAnteriorVencida = false;
        $this->mensajeEstado = null;

        if ($this->turno->estado !== Turno::ESTADO_SUSPENDIDO_PROFESOR) {
            $this->mensajeEstado = 'Esta suspensión ya fue resuelta o el turno cambió de estado.';

            return;
        }

        if (! $this->turno->pago || $this->turno->pago->estado !== Pago::ESTADO_APROBADO) {
            $this->mensajeEstado = 'No se encontró un pago aprobado asociado a este turno.';

            return;
        }

        if ($this->turno->inicioDateTime()->isPast()) {
            $this->mensajeEstado = 'El horario original ya comenzó o finalizó.';

            return;
        }

        $reemplazoService = app(ReemplazoProfesorService::class);
        $this->propuestaVigente = $reemplazoService->tienePropuestaVigente($this->turno);

        if ($this->propuestaVigente) {
            return;
        }

        $this->propuestaAnteriorVencida = $this->turno->reemplazo_profesor_propuesto_id !== null
            && $this->turno->reemplazo_expires_at !== null
            && $this->turno->reemplazo_expires_at->isPast();

        $this->candidato = $this->buscarCandidato(app(SlotService::class));
    }

    /** @return array<string, mixed>|null */
    private function buscarCandidato(SlotService $slotService): ?array
    {
        $fecha = $this->turno->fecha instanceof Carbon
            ? $this->turno->fecha->copy()
            : Carbon::parse($this->turno->fecha);

        $horaInicioOriginal = $this->normalizarHora((string) $this->turno->hora_inicio);
        $horaFinOriginal = $this->normalizarHora((string) $this->turno->hora_fin);

        return $slotService
            ->obtenerSlotsPorMateria(
                (int) $this->turno->materia_id,
                $fecha,
                $this->turno->tema_id ? (int) $this->turno->tema_id : null,
            )
            ->filter(fn (array $slot): bool =>
                (int) ($slot['profesor_id'] ?? 0) !== (int) $this->turno->profesor_id
                && (string) ($slot['fecha'] ?? '') === $fecha->toDateString()
                && $this->normalizarHora((string) ($slot['hora_inicio'] ?? '')) === $horaInicioOriginal
                && $this->normalizarHora((string) ($slot['hora_fin'] ?? '')) === $horaFinOriginal
            )
            ->sort(function (array $a, array $b): int {
                $porPromedio = (float) ($b['rating_avg'] ?? 0) <=> (float) ($a['rating_avg'] ?? 0);

                if ($porPromedio !== 0) {
                    return $porPromedio;
                }

                $porCantidad = (int) ($b['rating_count'] ?? 0) <=> (int) ($a['rating_count'] ?? 0);

                if ($porCantidad !== 0) {
                    return $porCantidad;
                }

                return (int) ($a['profesor_id'] ?? 0) <=> (int) ($b['profesor_id'] ?? 0);
            })
            ->first();
    }

    private function validarTurnoResoluble(): void
    {
        abort_unless((int) $this->turno->alumno_id === (int) Auth::id(), 404);

        if ($this->turno->estado !== Turno::ESTADO_SUSPENDIDO_PROFESOR) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'reemplazo' => 'Esta suspensión ya no se puede resolver.',
            ]);
        }

        if ($this->turno->inicioDateTime()->isPast()) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'reemplazo' => 'El horario original ya comenzó o finalizó.',
            ]);
        }
    }

    private function recargarTurno(): void
    {
        $this->turno = Turno::query()
            ->with([
                'profesor:id,name,apellido',
                'materia:materia_id,materia_nombre',
                'tema:tema_id,tema_nombre',
                'pago',
                'profesorReemplazoPropuesto:id,name,apellido',
            ])
            ->findOrFail($this->turno->getKey());

        abort_unless((int) $this->turno->alumno_id === (int) Auth::id(), 404);
    }

    private function normalizarHora(string $hora): string
    {
        $hora = trim($hora);

        return preg_match('/^\d{2}:\d{2}$/', $hora) ? $hora.':00' : $hora;
    }
}
