<?php

namespace App\Filament\Alumno\Pages;

use App\Jobs\ProcesarReemplazoTurnoCanceladoJob;
use App\Mail\ProfesorTurnoReprogramado;
use App\Models\Turno;
use App\Models\User;
use App\Services\AuditLogger;
use App\Services\SlotService;
use BackedEnum;
use Carbon\Carbon;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;

class ReprogramarTurno extends Page
{
    protected static bool $shouldRegisterNavigation = false;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-calendar-days';
    protected static ?string $navigationLabel = 'Reprogramar turno';
    protected static ?string $title = 'Reprogramar turno';
    protected static ?string $slug = 'reprogramar-turno';

    protected string $view = 'filament.alumno.pages.reprogramar-turno';

    public ?Turno $turnoOriginal = null;

    public ?string $fecha = null;
    public array $slots = [];
    public ?string $errorMensaje = null;

    protected SlotService $slotService;

    public function boot(SlotService $slotService): void
    {
        $this->slotService = $slotService;
    }

    public function mount(): void
    {
        $turnoId = (int) request()->query('turno', 0);

        if ($turnoId <= 0) {
            $this->errorMensaje = 'Falta el parámetro "turno" en la URL.';
            return;
        }

        $turno = Turno::with(['profesor', 'materia', 'tema'])->find($turnoId);

        if (! $turno) {
            $this->errorMensaje = 'El turno no existe.';
            return;
        }

        if ((int) $turno->alumno_id !== (int) Auth::id()) {
            abort(403);
        }

        if ($turno->inicioDateTime()->isPast()) {
            $this->errorMensaje = 'No podés reprogramar una clase que ya empezó o pasó.';
            return;
        }

        if (! in_array((string) $turno->estado, [
            Turno::ESTADO_PENDIENTE,
            Turno::ESTADO_PENDIENTE_PAGO,
            Turno::ESTADO_CONFIRMADO,
        ], true)) {
            $this->errorMensaje = 'Este turno no se puede reprogramar.';
            return;
        }

        $horasRegla = (int) config('turnos.cancelacion_sin_cargo_horas', 24);
        $horasHastaInicio = now()->diffInHours($turno->inicioDateTime(), false);

        if ($horasHastaInicio < $horasRegla) {
            $this->errorMensaje = 'Solo podés reprogramar con al menos 24 horas de anticipación.';
            return;
        }

        $this->turnoOriginal = $turno;
    }

    public function consultar(): void
    {
        if (! $this->turnoOriginal) {
            throw ValidationException::withMessages([
                'turno' => $this->errorMensaje ?? 'No se pudo cargar el turno.',
            ]);
        }

        if (! $this->fecha) {
            throw ValidationException::withMessages([
                'fecha' => 'Seleccioná una fecha.',
            ]);
        }

        $fecha = Carbon::createFromFormat('Y-m-d', $this->fecha);

        if ($fecha->isPast() && ! $fecha->isToday()) {
            throw ValidationException::withMessages([
                'fecha' => 'No podés elegir fechas pasadas.',
            ]);
        }

        $slots = $this->slotService
            ->obtenerSlotsPorMateria(
                (int) $this->turnoOriginal->materia_id,
                $fecha,
                $this->turnoOriginal->tema_id ? (int) $this->turnoOriginal->tema_id : null
            )
            ->toArray();

        $this->slots = $this->filtrarSlotsProfesoresActivos($slots);
    }

    public function reprogramar(int $index): void
    {
        if (! $this->turnoOriginal) {
            throw ValidationException::withMessages([
                'turno' => $this->errorMensaje ?? 'No se pudo cargar el turno.',
            ]);
        }

        if (! isset($this->slots[$index])) {
            throw ValidationException::withMessages([
                'slot' => 'Horario inválido.',
            ]);
        }

        /** @var AuditLogger $audit */
        $audit = app(AuditLogger::class);

        $slot = $this->slots[$index];

        $turnoOriginalId = (int) $this->turnoOriginal->id;
        $nuevoTurnoId = null;
        $slotHoldOriginalId = null;

        $estadoOriginalAntes = (string) $this->turnoOriginal->estado;
        $profesorOriginalId = (int) $this->turnoOriginal->profesor_id;
        $fechaOriginal = $this->turnoOriginal->fecha ? $this->turnoOriginal->fecha->toDateString() : null;
        $horaInicioOriginal = (string) $this->turnoOriginal->hora_inicio;
        $horaFinOriginal = (string) $this->turnoOriginal->hora_fin;
        $alumnoExcluidoId = (int) $this->turnoOriginal->alumno_id;

        DB::transaction(function () use (
            $slot,
            &$nuevoTurnoId,
            &$slotHoldOriginalId,
            $turnoOriginalId,
            $alumnoExcluidoId
        ) {
            $horasRegla = (int) config('turnos.cancelacion_sin_cargo_horas', 24);
            $horasHastaInicio = now()->diffInHours($this->turnoOriginal->inicioDateTime(), false);

            if ($horasHastaInicio < $horasRegla) {
                throw ValidationException::withMessages([
                    'turno' => 'Ya no se puede reprogramar (faltan menos de 24 horas).',
                ]);
            }

            $profesorActivo = User::query()
                ->whereKey($slot['profesor_id'])
                ->where('role', 'profesor')
                ->where('activo', true)
                ->lockForUpdate()
                ->first();

            if (! $profesorActivo) {
                throw ValidationException::withMessages([
                    'slot' => 'El profesor de ese horario ya no está disponible.',
                ]);
            }

            $hayChoque = Turno::query()
                ->where('profesor_id', $slot['profesor_id'])
                ->whereDate('fecha', $slot['fecha'])
                ->where(function ($q) use ($slot) {
                    $q->where('hora_inicio', '<', $slot['hora_fin'])
                        ->where('hora_fin', '>', $slot['hora_inicio']);
                })
                ->whereIn('estado', [
                    Turno::ESTADO_PENDIENTE,
                    Turno::ESTADO_ACEPTADO,
                    Turno::ESTADO_PENDIENTE_PAGO,
                    Turno::ESTADO_CONFIRMADO,
                ])
                ->lockForUpdate()
                ->exists();

            if ($hayChoque) {
                throw ValidationException::withMessages([
                    'slot' => 'Ese horario ya no está disponible.',
                ]);
            }

            $estadoNuevo = $this->turnoOriginal->estado === Turno::ESTADO_CONFIRMADO
                ? Turno::ESTADO_CONFIRMADO
                : Turno::ESTADO_PENDIENTE_PAGO;

            $nuevo = Turno::create([
                'alumno_id' => $this->turnoOriginal->alumno_id,
                'profesor_id' => $profesorActivo->id,
                'materia_id' => $this->turnoOriginal->materia_id,
                'tema_id' => $this->turnoOriginal->tema_id,
                'fecha' => $slot['fecha'],
                'hora_inicio' => $slot['hora_inicio'],
                'hora_fin' => $slot['hora_fin'],
                'estado' => $estadoNuevo,
                'precio_por_hora' => $slot['precio_por_hora'] ?? $this->turnoOriginal->precio_por_hora,
                'precio_total' => $slot['precio_total'] ?? $this->turnoOriginal->precio_total,
            ]);

            $nuevoTurnoId = (int) $nuevo->id;

            $this->turnoOriginal->refresh();

            $this->turnoOriginal->update([
                'estado' => Turno::ESTADO_CANCELADO,
                'cancelado_at' => now(),
                'cancelacion_tipo' => 'sin_cargo',
                'reprogramado_por_turno_id' => $nuevo->id,
                'reprogramado_at' => now(),
            ]);

            $turnoOriginalCancelado = $this->turnoOriginal->fresh();

            $slotHoldOriginalId = $this->slotService->crearHoldDesdeTurnoCanceladoParaReemplazo(
                $turnoOriginalCancelado,
                [
                    'origen' => 'reprogramacion_sin_cargo',
                    'turno_nuevo_id' => $nuevo->id,
                    'excluded_alumno_id' => $alumnoExcluidoId,
                    'turno_original_id' => $turnoOriginalId,
                ]
            );

            DB::afterCommit(function () use ($turnoOriginalId, $alumnoExcluidoId) {
                ProcesarReemplazoTurnoCanceladoJob::dispatch(
                    $turnoOriginalId,
                    $alumnoExcluidoId
                );
            });
        });

        $turnoOriginal = Turno::with(['profesor', 'alumno', 'materia', 'tema'])
            ->whereKey($turnoOriginalId)
            ->first();

        $turnoNuevo = $nuevoTurnoId
            ? Turno::with(['profesor', 'alumno', 'materia', 'tema'])
                ->whereKey($nuevoTurnoId)
                ->first()
            : null;

        if ($turnoOriginal && $turnoNuevo) {
            $audit->log('turno.reprogramado', $turnoOriginal, [
                'turno_original_id' => $turnoOriginal->id,
                'turno_nuevo_id' => $turnoNuevo->id,
                'alumno_id' => $turnoOriginal->alumno_id,
                'profesor_original_id' => $profesorOriginalId,
                'profesor_nuevo_id' => $turnoNuevo->profesor_id,
                'materia_id' => $turnoOriginal->materia_id,
                'tema_id' => $turnoOriginal->tema_id,
                'estado_original_anterior' => $estadoOriginalAntes,
                'estado_original_nuevo' => Turno::ESTADO_CANCELADO,
                'estado_turno_nuevo' => $turnoNuevo->estado,
                'fecha_original' => $fechaOriginal,
                'hora_inicio_original' => $horaInicioOriginal,
                'hora_fin_original' => $horaFinOriginal,
                'fecha_nueva' => $turnoNuevo->fecha ? $turnoNuevo->fecha->toDateString() : null,
                'hora_inicio_nueva' => (string) $turnoNuevo->hora_inicio,
                'hora_fin_nueva' => (string) $turnoNuevo->hora_fin,
                'cancelacion_tipo' => 'sin_cargo',
                'slot_hold_original_id' => $slotHoldOriginalId,
                'reemplazo_busqueda_disparada' => true,
                'alumno_excluido_reemplazo_id' => $alumnoExcluidoId,
                'motivo_recuperacion_hueco' => 'reprogramacion_sin_cargo',
            ]);
        }

        if ($turnoOriginal && $turnoNuevo && $turnoNuevo->profesor?->email) {
            Mail::to($turnoNuevo->profesor->email)
                ->send(new ProfesorTurnoReprogramado($turnoOriginal, $turnoNuevo));
        }

        Notification::make()
            ->title('Turno reprogramado')
            ->body('Se creó el nuevo turno y se puso en búsqueda el horario original liberado.')
            ->success()
            ->send();

        if ($this->fecha) {
            $this->consultar();
        }
    }

    private function filtrarSlotsProfesoresActivos(array $slots): array
    {
        $profesoresIds = collect($slots)
            ->pluck('profesor_id')
            ->filter()
            ->unique()
            ->values()
            ->all();

        if (empty($profesoresIds)) {
            return [];
        }

        $activos = User::query()
            ->whereIn('id', $profesoresIds)
            ->where('role', 'profesor')
            ->where('activo', true)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();

        return array_values(array_filter($slots, function (array $slot) use ($activos) {
            return in_array((int) ($slot['profesor_id'] ?? 0), $activos, true);
        }));
    }
}