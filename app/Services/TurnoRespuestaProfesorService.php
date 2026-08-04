<?php

namespace App\Services;

use App\Mail\ProfesorRespondioTurno;
use App\Models\Turno;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;

class TurnoRespuestaProfesorService
{
    public function __construct(private readonly AuditLogger $audit)
    {
    }

    public function puedeResponder(Turno $turno, int $profesorId): bool
    {
        return (int) $turno->profesor_id === $profesorId
            && $turno->estado === Turno::ESTADO_PENDIENTE
            && ! $this->estaVencido($turno);
    }

    public function estaVencido(Turno $turno): bool
    {
        if (in_array((string) $turno->estado, [
            Turno::ESTADO_CONFIRMADO,
            Turno::ESTADO_CANCELADO,
            Turno::ESTADO_RECHAZADO,
            Turno::ESTADO_VENCIDO,
        ], true)) {
            return false;
        }

        return now()->gte($turno->inicioDateTime());
    }

    public function marcarComoVencidoSiCorresponde(Turno $turno, int $profesorId): bool
    {
        $fueMarcado = DB::transaction(function () use ($turno, $profesorId): bool {
            $turnoBloqueado = $this->buscarTurnoBloqueado((int) $turno->getKey(), $profesorId);

            return $this->marcarComoVencidoBloqueado($turnoBloqueado);
        });

        $turno->refresh();

        return $fueMarcado;
    }

    public function aceptar(Turno $turno, int $profesorId, string $enlaceClase): ?Turno
    {
        $datos = Validator::make(
            ['enlace_clase' => $enlaceClase],
            ['enlace_clase' => ['required', 'url', 'max:2048']],
        )->validate();

        $turnoActualizado = DB::transaction(function () use ($turno, $profesorId, $datos): ?Turno {
            $turnoBloqueado = $this->buscarTurnoBloqueado((int) $turno->getKey(), $profesorId);

            if ($this->marcarComoVencidoBloqueado($turnoBloqueado)) {
                return null;
            }

            if ($turnoBloqueado->estado !== Turno::ESTADO_PENDIENTE) {
                return null;
            }

            $estadoAntes = (string) $turnoBloqueado->estado;

            $turnoBloqueado->update([
                'estado' => Turno::ESTADO_PENDIENTE_PAGO,
                'enlace_clase' => trim((string) $datos['enlace_clase']),
            ]);

            $turnoBloqueado->loadMissing(['alumno', 'profesor', 'materia', 'tema']);

            $this->audit->log('turno.aceptado_profesor', $turnoBloqueado, [
                'turno_id' => $turnoBloqueado->id,
                'profesor_id' => $turnoBloqueado->profesor_id,
                'alumno_id' => $turnoBloqueado->alumno_id,
                'estado_anterior' => $estadoAntes,
                'estado_nuevo' => Turno::ESTADO_PENDIENTE_PAGO,
                'enlace_clase' => $turnoBloqueado->enlace_clase,
                'fecha' => (string) $turnoBloqueado->fecha,
                'hora_inicio' => (string) $turnoBloqueado->hora_inicio,
                'hora_fin' => (string) $turnoBloqueado->hora_fin,
            ]);

            return $turnoBloqueado;
        });

        if ($turnoActualizado?->alumno?->email) {
            Mail::to($turnoActualizado->alumno->email)
                ->send(new ProfesorRespondioTurno($turnoActualizado));
        }

        return $turnoActualizado;
    }

    public function rechazar(Turno $turno, int $profesorId): ?Turno
    {
        $turnoActualizado = DB::transaction(function () use ($turno, $profesorId): ?Turno {
            $turnoBloqueado = $this->buscarTurnoBloqueado((int) $turno->getKey(), $profesorId);

            if ($this->marcarComoVencidoBloqueado($turnoBloqueado)) {
                return null;
            }

            if ($turnoBloqueado->estado !== Turno::ESTADO_PENDIENTE) {
                return null;
            }

            $estadoAntes = (string) $turnoBloqueado->estado;

            $turnoBloqueado->update([
                'estado' => Turno::ESTADO_RECHAZADO,
            ]);

            $turnoBloqueado->loadMissing(['alumno', 'profesor', 'materia', 'tema']);

            $this->audit->log('turno.rechazado_profesor', $turnoBloqueado, [
                'turno_id' => $turnoBloqueado->id,
                'profesor_id' => $turnoBloqueado->profesor_id,
                'alumno_id' => $turnoBloqueado->alumno_id,
                'estado_anterior' => $estadoAntes,
                'estado_nuevo' => Turno::ESTADO_RECHAZADO,
                'fecha' => (string) $turnoBloqueado->fecha,
                'hora_inicio' => (string) $turnoBloqueado->hora_inicio,
                'hora_fin' => (string) $turnoBloqueado->hora_fin,
            ]);

            return $turnoBloqueado;
        });

        if ($turnoActualizado?->alumno?->email) {
            Mail::to($turnoActualizado->alumno->email)
                ->send(new ProfesorRespondioTurno($turnoActualizado));
        }

        return $turnoActualizado;
    }

    private function buscarTurnoBloqueado(int $turnoId, int $profesorId): Turno
    {
        $turno = Turno::query()
            ->whereKey($turnoId)
            ->where('profesor_id', $profesorId)
            ->lockForUpdate()
            ->first();

        if (! $turno) {
            throw (new ModelNotFoundException())->setModel(Turno::class, [$turnoId]);
        }

        return $turno;
    }

    private function marcarComoVencidoBloqueado(Turno $turno): bool
    {
        if (
            $turno->estado !== Turno::ESTADO_PENDIENTE
            || ! $this->estaVencido($turno)
        ) {
            return false;
        }

        $estadoAntes = (string) $turno->estado;

        $turno->update([
            'estado' => Turno::ESTADO_VENCIDO,
        ]);

        $this->audit->log('turno.vencido', $turno, [
            'turno_id' => $turno->id,
            'motivo' => 'respuesta_profesor_fuera_de_hora',
            'estado_anterior' => $estadoAntes,
            'estado_nuevo' => Turno::ESTADO_VENCIDO,
            'fecha' => (string) $turno->fecha,
            'hora_inicio' => (string) $turno->hora_inicio,
            'hora_fin' => (string) $turno->hora_fin,
        ]);

        return true;
    }
}
