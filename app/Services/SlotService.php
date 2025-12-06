<?php

namespace App\Services;

use App\Models\Disponibilidad;
use App\Models\Turno;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class SlotService
{
    /**
     * Obtiene slots disponibles para TODOS los profesores que dictan una materia
     * en una fecha dada.
     *
     * @param  int         $materiaId
     * @param  Carbon      $fecha
     * @param  int|null    $temaId    (lo dejamos para futuro)
     * @return Collection
     */
    public function obtenerSlotsPorMateria(int $materiaId, Carbon $fecha, ?int $temaId = null): Collection
    {
        $diaSemana = $fecha->dayOfWeekIso; // 1=lunes ... 7=domingo

        // 🟢 1) Buscar IDs de profesores que dictan esa materia
        //    USANDO la pivot correcta: profesor_materia
        $profesoresIds = DB::table('profesor_materia')
            ->where('materia_id', $materiaId)
            ->pluck('profesor_id')
            ->unique()
            ->toArray();

        if (empty($profesoresIds)) {
            return collect();
        }

        // 2) Traer datos básicos de esos profesores (podés filtrar por role = 'profesor' si querés)
        $profesores = User::query()
            ->whereIn('id', $profesoresIds)
            ->where('role', 'profesor')
            ->get(['id', 'name']);

        $slots = collect();

        // Duración del turno en minutos (podés moverlo a config/turnos.php)
        $duracion = config('turnos.duracion_slot', 60);
        $duracion = (int) ($duracion ?: 60); // por las dudas

        foreach ($profesores as $profesor) {

            // 3) Bloques de disponibilidad para ese profesor y ese día de semana
            $bloques = Disponibilidad::query()
                ->where('profesor_id', $profesor->id)
                ->where('dia_semana', $diaSemana)
                ->get();

            if ($bloques->isEmpty()) {
                continue;
            }

            // 4) Turnos ya reservados para ese profesor en esa fecha
            $turnos = Turno::query()
                ->where('profesor_id', $profesor->id)
                ->whereDate('fecha', $fecha->toDateString())
                ->whereIn('estado', ['pendiente', 'confirmado'])
                ->get();

            // 5) Generar slots para cada bloque de disponibilidad
            foreach ($bloques as $bloque) {
                $horaInicioBloque = $fecha->copy()->setTimeFromTimeString($bloque->hora_inicio);
                $horaFinBloque    = $fecha->copy()->setTimeFromTimeString($bloque->hora_fin);

                $cursor = $horaInicioBloque->copy();

                while ($cursor->copy()->addMinutes($duracion)->lte($horaFinBloque)) {

                    $desde = $cursor->format('H:i:s');
                    $hasta = $cursor->copy()->addMinutes($duracion)->format('H:i:s');

                    // Ver si este slot choca con algún turno ya reservado
                    $hayChoque = $turnos->contains(function (Turno $t) use ($desde, $hasta) {
                        return $t->hora_inicio < $hasta && $t->hora_fin > $desde;
                    });

                    if (! $hayChoque) {
                        $slots->push([
                            'profesor_id'      => $profesor->id,
                            'profesor_nombre'  => $profesor->name,
                            'fecha'            => $fecha->toDateString(),
                            'hora_inicio'      => $desde,
                            'hora_fin'         => $hasta,
                            'desde'            => substr($desde, 0, 5), // HH:MM
                            'hasta'            => substr($hasta, 0, 5),
                            'materia_id'       => $materiaId,
                            'tema_id'          => $temaId,
                        ]);
                    }

                    $cursor->addMinutes($duracion);
                }
            }
        }

        // 👉 Hasta acá es EXACTAMENTE la lógica que vos tenías y que funcionaba 👆

        // 🟡 6) Agregar precio_por_hora y precio_total SIN afectar la generación de slots

        if ($slots->isEmpty()) {
            // Si no hay slots, ya está.
            return $slots;
        }

        // Profesores que efectivamente tienen slots
        $profesoresConSlots = $slots->pluck('profesor_id')->unique()->toArray();

        // Traer precios desde la pivot profesor_materia (solo anotamos, no filtramos)
        $preciosPorProfesor = DB::table('profesor_materia')
            ->where('materia_id', $materiaId)
            ->whereIn('profesor_id', $profesoresConSlots)
            ->pluck('precio_por_hora', 'profesor_id')
            ->toArray();

        // Mapear slots agregando precios (si existen)
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

        // 7) Ordenar por fecha, profesor y hora inicio (igual que antes)
        return $slots
            ->sortBy([
                ['fecha', 'asc'],
                ['profesor_nombre', 'asc'],
                ['hora_inicio', 'asc'],
            ])
            ->values();
    }
}
