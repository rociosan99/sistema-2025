<?php

namespace App\Services;

use App\Models\Disponibilidad;
use App\Models\Turno;
use App\Models\User;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class SlotService
{
    public function obtenerSlotsPorMateria(int $materiaId, Carbon $fecha, ?int $temaId = null): Collection
    {
        $diaSemana = $fecha->dayOfWeekIso; // 1=lunes ... 7=domingo

        // 1) IDs de profesores que dictan esa materia
        $profesoresIds = DB::table('profesor_materia')
            ->where('materia_id', $materiaId)
            ->pluck('profesor_id')
            ->unique()
            ->toArray();

        if (empty($profesoresIds)) {
            return collect();
        }

        // 2) Profesores
        $profesores = User::query()
            ->whereIn('id', $profesoresIds)
            ->where('role', 'profesor')
            ->get(['id', 'name']);

        $slots = collect();

        $duracion = (int) (config('turnos.duracion_slot', 60) ?: 60);

        foreach ($profesores as $profesor) {

            // 3) Disponibilidades del día
            $bloques = Disponibilidad::query()
                ->where('profesor_id', $profesor->id)
                ->where('dia_semana', $diaSemana)
                ->get();

            if ($bloques->isEmpty()) {
                continue;
            }

            // 4) Turnos ocupando horario (IMPORTANTE: incluir aceptado y pendiente_pago)
            $turnos = Turno::query()
                ->where('profesor_id', $profesor->id)
                ->whereDate('fecha', $fecha->toDateString())
                ->whereIn('estado', ['pendiente', 'aceptado', 'pendiente_pago', 'confirmado'])
                ->get(['hora_inicio', 'hora_fin', 'estado']);

            // 5) Generar slots por bloque
            foreach ($bloques as $bloque) {
                $horaInicioBloque = $fecha->copy()->setTimeFromTimeString($bloque->hora_inicio);
                $horaFinBloque    = $fecha->copy()->setTimeFromTimeString($bloque->hora_fin);

                $cursor = $horaInicioBloque->copy();

                while ($cursor->copy()->addMinutes($duracion)->lte($horaFinBloque)) {

                    $desde = $cursor->format('H:i:s');
                    $hasta = $cursor->copy()->addMinutes($duracion)->format('H:i:s');

                    // 🔒 Ver si este slot choca con algún turno ya reservado
                    $hayChoque = $turnos->contains(function (Turno $t) use ($desde, $hasta) {

                        $inicio = $t->hora_inicio instanceof CarbonInterface
                            ? $t->hora_inicio->format('H:i:s')
                            : (string) $t->hora_inicio;

                        $fin = $t->hora_fin instanceof CarbonInterface
                            ? $t->hora_fin->format('H:i:s')
                            : (string) $t->hora_fin;

                        return $inicio < $hasta && $fin > $desde;
                    });

                    if (! $hayChoque) {
                        $slots->push([
                            'profesor_id'      => $profesor->id,
                            'profesor_nombre'  => $profesor->name,
                            'fecha'            => $fecha->toDateString(),
                            'hora_inicio'      => $desde,
                            'hora_fin'         => $hasta,
                            'desde'            => substr($desde, 0, 5),
                            'hasta'            => substr($hasta, 0, 5),
                            'materia_id'       => $materiaId,
                            'tema_id'          => $temaId,
                        ]);
                    }

                    $cursor->addMinutes($duracion);
                }
            }
        }

        if ($slots->isEmpty()) {
            return $slots;
        }

        // 6) Precios desde pivot
        $profesoresConSlots = $slots->pluck('profesor_id')->unique()->toArray();

        $preciosPorProfesor = DB::table('profesor_materia')
            ->where('materia_id', $materiaId)
            ->whereIn('profesor_id', $profesoresConSlots)
            ->pluck('precio_por_hora', 'profesor_id')
            ->toArray();

        $slots = $slots->map(function (array $slot) use ($preciosPorProfesor, $duracion) {

            $precioPorHora = $preciosPorProfesor[$slot['profesor_id']] ?? null;
            $precioTotal   = null;

            if ($precioPorHora !== null) {
                $horas       = $duracion / 60;
                $precioTotal = $precioPorHora * $horas;
            }

            $slot['precio_por_hora'] = $precioPorHora;
            $slot['precio_total']    = $precioTotal;

            return $slot;
        });

        return $slots
            ->sortBy([
                ['fecha', 'asc'],
                ['profesor_nombre', 'asc'],
                ['hora_inicio', 'asc'],
            ])
            ->values();
    }
}
