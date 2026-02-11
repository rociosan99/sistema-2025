<?php

namespace App\Services;

use App\Models\Disponibilidad;
use App\Models\SolicitudDisponibilidad;
use App\Models\Turno;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class SolicitudMatchingService
{
    /**
     * Devuelve profesores compatibles para un SLOT específico (hora_inicio/hora_fin).
     * - dicta materia
     * - tiene disponibilidad que cubra el slot ese día
     * - no tiene turnos solapados en esa fecha/hora (pendiente/pendiente_pago/confirmado/aceptado)
     *
     * @return Collection<int, array{profesor_id:int, profesor_nombre:string, precio_por_hora:float|null}>
     */
    public function profesoresCompatibles(SolicitudDisponibilidad $solicitud, string $slotInicio, string $slotFin): Collection
    {
        $fecha = Carbon::parse($solicitud->fecha);
        $diaSemana = $fecha->dayOfWeekIso; // 1..7

        $slotInicio = $this->normalizarHora($slotInicio);
        $slotFin    = $this->normalizarHora($slotFin);

        // 1) Profes que dictan la materia
        $profesIds = DB::table('profesor_materia')
            ->where('materia_id', $solicitud->materia_id)
            ->pluck('profesor_id')
            ->unique()
            ->values()
            ->all();

        if (empty($profesIds)) {
            return collect();
        }

        // 2) Profes con disponibilidad que cubra el slot
        $profesConDisponibilidad = Disponibilidad::query()
            ->whereIn('profesor_id', $profesIds)
            ->where('dia_semana', $diaSemana)
            ->where('hora_inicio', '<=', $slotInicio)
            ->where('hora_fin', '>=', $slotFin)
            ->pluck('profesor_id')
            ->unique()
            ->values()
            ->all();

        if (empty($profesConDisponibilidad)) {
            return collect();
        }

        // 3) Profes ocupados (turnos solapados en esa fecha y slot)
        $ocupados = Turno::query()
            ->whereIn('profesor_id', $profesConDisponibilidad)
            ->whereDate('fecha', $fecha->toDateString())
            ->whereIn('estado', [
                Turno::ESTADO_PENDIENTE,
                Turno::ESTADO_PENDIENTE_PAGO,
                Turno::ESTADO_CONFIRMADO,
                Turno::ESTADO_ACEPTADO, // por compatibilidad si aún existe
            ])
            ->where(function ($q) use ($slotInicio, $slotFin) {
                // solape: inicio < finSlot AND fin > inicioSlot
                $q->where('hora_inicio', '<', $slotFin)
                  ->where('hora_fin', '>', $slotInicio);
            })
            ->pluck('profesor_id')
            ->unique()
            ->values()
            ->all();

        $disponibles = array_values(array_diff($profesConDisponibilidad, $ocupados));

        if (empty($disponibles)) {
            return collect();
        }

        // 4) Datos del profesor + precio por hora (pivot)
        $profesores = User::query()
            ->whereIn('id', $disponibles)
            ->where('role', 'profesor')
            ->get(['id', 'name', 'apellido']);

        $preciosPorProfesor = DB::table('profesor_materia')
            ->where('materia_id', $solicitud->materia_id)
            ->whereIn('profesor_id', $profesores->pluck('id')->all())
            ->pluck('precio_por_hora', 'profesor_id')
            ->toArray();

        return $profesores->map(function (User $p) use ($preciosPorProfesor) {
            $nombre = trim(($p->name ?? '') . ' ' . ($p->apellido ?? ''));
            return [
                'profesor_id'     => (int) $p->id,
                'profesor_nombre' => $nombre ?: ($p->name ?? 'Profesor'),
                'precio_por_hora' => isset($preciosPorProfesor[$p->id]) ? (float) $preciosPorProfesor[$p->id] : null,
            ];
        })->values();
    }

    public function normalizarHora(string $hora): string
    {
        return preg_match('/^\d{2}:\d{2}$/', $hora) ? $hora . ':00' : $hora;
    }
}
