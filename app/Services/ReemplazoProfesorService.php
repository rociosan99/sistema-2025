<?php

namespace App\Services;

use App\Mail\AlumnoReemplazoProfesorConfirmado;
use App\Models\Pago;
use App\Models\Turno;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;

class ReemplazoProfesorService
{
    public function __construct(
        private readonly SlotService $slotService,
        private readonly AuditLogger $audit,
        private readonly EnlaceClaseProfesorService $enlaceClaseProfesorService,
    ) {
    }

    /**
     * @param array{profesor_id:int,fecha:string,hora_inicio:string,hora_fin:string} $slot
     */
    public function proponer(Turno $turno, int $alumnoId, array $slot): Turno
    {
        return DB::transaction(function () use ($turno, $alumnoId, $slot): Turno {
            $turnoBloqueado = Turno::query()
                ->whereKey($turno->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            $this->validarTurnoSuspendidoPagado($turnoBloqueado, $alumnoId);

            if ($this->propuestaVigente($turnoBloqueado)) {
                throw ValidationException::withMessages([
                    'reemplazo' => 'Ya existe una propuesta de reemplazo pendiente.',
                ]);
            }

            $profesorId = (int) ($slot['profesor_id'] ?? 0);
            $fecha = (string) ($slot['fecha'] ?? '');
            $horaInicio = $this->normalizarHora((string) ($slot['hora_inicio'] ?? ''));
            $horaFin = $this->normalizarHora((string) ($slot['hora_fin'] ?? ''));

            if ($profesorId <= 0 || $profesorId === (int) $turnoBloqueado->profesor_id) {
                throw ValidationException::withMessages([
                    'reemplazo' => 'Seleccioná un profesor distinto al que suspendió la clase.',
                ]);
            }

            $this->validarSlotDisponible($turnoBloqueado, $profesorId, $fecha, $horaInicio, $horaFin);

            $ttlMinutos = (int) config('matching.professor_replacement_proposal_ttl_minutes');

            if ($ttlMinutos <= 0) {
                throw ValidationException::withMessages([
                    'reemplazo' => 'El vencimiento de las propuestas de reemplazo no está configurado correctamente.',
                ]);
            }

            $solicitadoAt = now();
            $inicioPropuesto = Carbon::parse("{$fecha} {$horaInicio}");
            $expiresAt = $solicitadoAt->copy()->addMinutes($ttlMinutos);

            if ($inicioPropuesto->lt($expiresAt)) {
                $expiresAt = $inicioPropuesto;
            }

            if ($expiresAt->lte($solicitadoAt)) {
                throw ValidationException::withMessages([
                    'reemplazo' => 'El horario propuesto ya comenzó o venció.',
                ]);
            }

            $this->limpiarPropuesta($turnoBloqueado);

            $turnoBloqueado->update([
                'reemplazo_profesor_propuesto_id' => $profesorId,
                'reemplazo_fecha' => $fecha,
                'reemplazo_hora_inicio' => $horaInicio,
                'reemplazo_hora_fin' => $horaFin,
                'reemplazo_solicitado_at' => $solicitadoAt,
                'reemplazo_expires_at' => $expiresAt,
            ]);

            $this->audit->log('turno.reemplazo_profesor_propuesto', $turnoBloqueado, [
                'turno_id' => $turnoBloqueado->id,
                'alumno_id' => $alumnoId,
                'profesor_original_id' => $turnoBloqueado->profesor_id,
                'profesor_propuesto_id' => $profesorId,
                'fecha_propuesta' => $fecha,
                'hora_inicio_propuesta' => $horaInicio,
                'hora_fin_propuesta' => $horaFin,
                'importe_fijo' => (float) $turnoBloqueado->precio_total,
                'expires_at' => $expiresAt->toDateTimeString(),
            ], $alumnoId);

            return $turnoBloqueado->fresh();
        });
    }

    public function aceptar(Turno $turno, int $profesorId): Turno
    {
        return DB::transaction(function () use ($turno, $profesorId): Turno {
            $turnoBloqueado = Turno::query()
                ->whereKey($turno->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            $this->validarPropuestaParaProfesor($turnoBloqueado, $profesorId);
            $this->validarPagoAprobado($turnoBloqueado);

            $fecha = $turnoBloqueado->reemplazo_fecha->toDateString();
            $horaInicio = $this->normalizarHora((string) $turnoBloqueado->reemplazo_hora_inicio);
            $horaFin = $this->normalizarHora((string) $turnoBloqueado->reemplazo_hora_fin);
            $profesorAnteriorId = (int) $turnoBloqueado->profesor_id;

            $this->validarSlotDisponible(
                $turnoBloqueado,
                $profesorId,
                $fecha,
                $horaInicio,
                $horaFin,
            );

            $enlaceClase = $this->enlaceClaseProfesorService->obtenerPredeterminado($profesorId);

            $turnoBloqueado->update([
                'profesor_id' => $profesorId,
                'fecha' => $fecha,
                'hora_inicio' => $horaInicio,
                'hora_fin' => $horaFin,
                'estado' => Turno::ESTADO_CONFIRMADO,
                'enlace_clase' => $enlaceClase,
                ...$this->camposPropuestaVacios(),
            ]);

            $this->audit->log('turno.reemplazo_profesor_aceptado', $turnoBloqueado, [
                'turno_id' => $turnoBloqueado->id,
                'alumno_id' => $turnoBloqueado->alumno_id,
                'profesor_anterior_id' => $profesorAnteriorId,
                'profesor_nuevo_id' => $profesorId,
                'fecha_nueva' => $fecha,
                'hora_inicio_nueva' => $horaInicio,
                'hora_fin_nueva' => $horaFin,
                'precio_por_hora_conservado' => (float) $turnoBloqueado->precio_por_hora,
                'precio_total_conservado' => (float) $turnoBloqueado->precio_total,
                'pago_id' => $turnoBloqueado->pago?->pago_id,
            ], $profesorId);

            $turnoId = (int) $turnoBloqueado->getKey();

            DB::afterCommit(function () use ($turnoId): void {
                $turnoConfirmado = Turno::query()
                    ->with(['alumno', 'profesor', 'materia', 'tema'])
                    ->find($turnoId);

                if ($turnoConfirmado?->alumno?->email) {
                    Mail::to($turnoConfirmado->alumno->email)
                        ->send(new AlumnoReemplazoProfesorConfirmado($turnoConfirmado));
                }
            });

            return $turnoBloqueado->fresh();
        });
    }

    public function rechazar(Turno $turno, int $profesorId): Turno
    {
        return DB::transaction(function () use ($turno, $profesorId): Turno {
            $turnoBloqueado = Turno::query()
                ->whereKey($turno->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            $this->validarPropuestaParaProfesor($turnoBloqueado, $profesorId);

            $this->audit->log('turno.reemplazo_profesor_rechazado', $turnoBloqueado, [
                'turno_id' => $turnoBloqueado->id,
                'alumno_id' => $turnoBloqueado->alumno_id,
                'profesor_original_id' => $turnoBloqueado->profesor_id,
                'profesor_propuesto_id' => $profesorId,
            ], $profesorId);

            $turnoBloqueado->update($this->camposPropuestaVacios());

            return $turnoBloqueado->fresh();
        });
    }

    public function cancelarPropuesta(Turno $turno, int $alumnoId): Turno
    {
        return DB::transaction(function () use ($turno, $alumnoId): Turno {
            $turnoBloqueado = Turno::query()
                ->whereKey($turno->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            $this->validarTurnoSuspendidoPagado($turnoBloqueado, $alumnoId);

            if ($turnoBloqueado->reemplazo_profesor_propuesto_id === null) {
                return $turnoBloqueado;
            }

            $profesorPropuestoId = (int) $turnoBloqueado->reemplazo_profesor_propuesto_id;

            $turnoBloqueado->update($this->camposPropuestaVacios());

            $this->audit->log('turno.reemplazo_profesor_cancelado_alumno', $turnoBloqueado, [
                'turno_id' => $turnoBloqueado->id,
                'alumno_id' => $alumnoId,
                'profesor_propuesto_id' => $profesorPropuestoId,
            ], $alumnoId);

            return $turnoBloqueado->fresh();
        });
    }

    public function tienePropuestaVigente(Turno $turno): bool
    {
        return $this->propuestaVigente($turno);
    }

    private function validarTurnoSuspendidoPagado(Turno $turno, int $alumnoId): void
    {
        if ((int) $turno->alumno_id !== $alumnoId) {
            abort(404);
        }

        if ($turno->estado !== Turno::ESTADO_SUSPENDIDO_PROFESOR) {
            throw ValidationException::withMessages([
                'reemplazo' => 'El turno no está suspendido por el profesor.',
            ]);
        }

        $this->validarPagoAprobado($turno);
    }

    private function validarPagoAprobado(Turno $turno): void
    {
        $pago = Pago::query()
            ->where('turno_id', $turno->id)
            ->lockForUpdate()
            ->first();

        if (! $pago || $pago->estado !== Pago::ESTADO_APROBADO) {
            throw ValidationException::withMessages([
                'reemplazo' => 'El turno no tiene un pago aprobado para conservar.',
            ]);
        }
    }

    private function validarPropuestaParaProfesor(Turno $turno, int $profesorId): void
    {
        if (
            $turno->estado !== Turno::ESTADO_SUSPENDIDO_PROFESOR
            || (int) $turno->reemplazo_profesor_propuesto_id !== $profesorId
        ) {
            abort(404);
        }

        if (! $this->propuestaVigente($turno)) {
            if ($turno->reemplazo_profesor_propuesto_id !== null) {
                $turno->update($this->camposPropuestaVacios());
            }

            throw ValidationException::withMessages([
                'reemplazo' => 'La propuesta de reemplazo venció.',
            ]);
        }
    }

    private function validarSlotDisponible(
        Turno $turno,
        int $profesorId,
        string $fecha,
        string $horaInicio,
        string $horaFin,
    ): void {
        try {
            $fechaCarbon = Carbon::createFromFormat('Y-m-d', $fecha)->startOfDay();
        } catch (\Throwable) {
            throw ValidationException::withMessages([
                'reemplazo' => 'La fecha propuesta no es válida.',
            ]);
        }

        $profesor = User::query()
            ->whereKey($profesorId)
            ->where('role', 'profesor')
            ->where('activo', true)
            ->lockForUpdate()
            ->first();

        if (! $profesor) {
            throw ValidationException::withMessages([
                'reemplazo' => 'El profesor propuesto ya no está disponible.',
            ]);
        }

        $slotExiste = $this->slotService
            ->obtenerSlotsPorMateria(
                (int) $turno->materia_id,
                $fechaCarbon,
                $turno->tema_id ? (int) $turno->tema_id : null,
            )
            ->contains(fn (array $slot): bool =>
                (int) ($slot['profesor_id'] ?? 0) === $profesorId
                && (string) ($slot['fecha'] ?? '') === $fecha
                && $this->normalizarHora((string) ($slot['hora_inicio'] ?? '')) === $horaInicio
                && $this->normalizarHora((string) ($slot['hora_fin'] ?? '')) === $horaFin
            );

        if (! $slotExiste) {
            throw ValidationException::withMessages([
                'reemplazo' => 'El horario propuesto ya no está disponible.',
            ]);
        }

        $hayChoque = Turno::query()
            ->where('profesor_id', $profesorId)
            ->whereDate('fecha', $fecha)
            ->whereKeyNot($turno->id)
            ->whereIn('estado', [
                Turno::ESTADO_PENDIENTE,
                Turno::ESTADO_ACEPTADO,
                Turno::ESTADO_PENDIENTE_PAGO,
                Turno::ESTADO_CONFIRMADO,
            ])
            ->where(function ($query) use ($horaInicio, $horaFin) {
                $query->where('hora_inicio', '<', $horaFin)
                    ->where('hora_fin', '>', $horaInicio);
            })
            ->lockForUpdate()
            ->exists();

        if ($hayChoque) {
            throw ValidationException::withMessages([
                'reemplazo' => 'El profesor ya tiene otro turno en ese horario.',
            ]);
        }
    }

    private function propuestaVigente(Turno $turno): bool
    {
        return $turno->reemplazo_profesor_propuesto_id !== null
            && $turno->reemplazo_expires_at !== null
            && $turno->reemplazo_expires_at->isFuture();
    }

    private function limpiarPropuesta(Turno $turno): void
    {
        if ($turno->reemplazo_profesor_propuesto_id !== null) {
            $turno->update($this->camposPropuestaVacios());
        }
    }

    /** @return array<string, null> */
    private function camposPropuestaVacios(): array
    {
        return [
            'reemplazo_profesor_propuesto_id' => null,
            'reemplazo_fecha' => null,
            'reemplazo_hora_inicio' => null,
            'reemplazo_hora_fin' => null,
            'reemplazo_solicitado_at' => null,
            'reemplazo_expires_at' => null,
        ];
    }

    private function normalizarHora(string $hora): string
    {
        $hora = trim($hora);

        if (preg_match('/^\d{2}:\d{2}$/', $hora)) {
            return $hora.':00';
        }

        return $hora;
    }
}
