<?php

namespace App\Services;

use App\Models\CalificacionProfesor;
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
        $duracion = (int) (config('turnos.duracion_slot', 60) ?: 60);

        // 1) IDs de profes que dictan esa materia
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
            ->get(['id', 'name', 'apellido']);

        if ($profesores->isEmpty()) {
            return collect();
        }

        // 3) Precios desde pivot (por profe)
        $preciosPorProfesor = DB::table('profesor_materia')
            ->where('materia_id', $materiaId)
            ->whereIn('profesor_id', $profesores->pluck('id')->all())
            ->pluck('precio_por_hora', 'profesor_id')
            ->toArray();

        // 4) Ratings por profe (avg + count)
        $ratings = CalificacionProfesor::query()
            ->selectRaw('profesor_id, AVG(estrellas) as avg_estrellas, COUNT(*) as cant')
            ->whereIn('profesor_id', $profesores->pluck('id')->all())
            ->groupBy('profesor_id')
            ->get()
            ->keyBy('profesor_id');

        $slots = collect();

        foreach ($profesores as $profesor) {

            $ratingRow = $ratings->get($profesor->id);
            $ratingAvg = $ratingRow?->avg_estrellas !== null ? round((float) $ratingRow->avg_estrellas, 1) : 0.0;
            $ratingCnt = $ratingRow?->cant !== null ? (int) $ratingRow->cant : 0;

            $nombreCompleto = trim(($profesor->name ?? '') . ' ' . ($profesor->apellido ?? ''));

            // Disponibilidades del día
            $bloques = Disponibilidad::query()
                ->where('profesor_id', $profesor->id)
                ->where('dia_semana', $diaSemana)
                ->get();

            if ($bloques->isEmpty()) {
                continue;
            }

            // Turnos ocupados
            $turnos = Turno::query()
                ->where('profesor_id', $profesor->id)
                ->whereDate('fecha', $fecha->toDateString())
                ->whereIn('estado', ['pendiente', 'aceptado', 'pendiente_pago', 'confirmado'])
                ->get(['hora_inicio', 'hora_fin']);

            foreach ($bloques as $bloque) {

                $horaInicioBloque = $fecha->copy()->setTimeFromTimeString($bloque->hora_inicio);
                $horaFinBloque    = $fecha->copy()->setTimeFromTimeString($bloque->hora_fin);

                $cursor = $horaInicioBloque->copy();

                while ($cursor->copy()->addMinutes($duracion)->lte($horaFinBloque)) {

                    $desde = $cursor->format('H:i:s');
                    $hasta = $cursor->copy()->addMinutes($duracion)->format('H:i:s');

                    // choque con turnos ya reservados
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
                        $precioPorHora = $preciosPorProfesor[$profesor->id] ?? null;
                        $precioTotal   = null;

                        if ($precioPorHora !== null) {
                            $horas       = $duracion / 60;
                            $precioTotal = $precioPorHora * $horas;
                        }

                        $slots->push([
                            'profesor_id'      => $profesor->id,
                            'profesor_nombre'  => $nombreCompleto ?: ($profesor->name ?? 'Profesor'),
                            'fecha'            => $fecha->toDateString(),
                            'hora_inicio'      => $desde,
                            'hora_fin'         => $hasta,
                            'desde'            => substr($desde, 0, 5),
                            'hasta'            => substr($hasta, 0, 5),
                            'materia_id'       => $materiaId,
                            'tema_id'          => $temaId,

                            // ✅ Rating
                            'rating_avg'       => $ratingAvg,
                            'rating_count'     => $ratingCnt,

                            // ✅ Precio
                            'precio_por_hora'  => $precioPorHora,
                            'precio_total'     => $precioTotal,
                        ]);
                    }

                    $cursor->addMinutes($duracion);
                }
            }
        }

        if ($slots->isEmpty()) {
            return $slots;
        }

        // ✅ Orden tipo Uber: mejor rating, más reviews, y después más barato
        return $slots
            ->sort(function ($a, $b) {
                $aAvg = (float) ($a['rating_avg'] ?? 0);
                $bAvg = (float) ($b['rating_avg'] ?? 0);
                if ($aAvg !== $bAvg) return $bAvg <=> $aAvg;

                $aCnt = (int) ($a['rating_count'] ?? 0);
                $bCnt = (int) ($b['rating_count'] ?? 0);
                if ($aCnt !== $bCnt) return $bCnt <=> $aCnt;

                $aPrice = (float) ($a['precio_por_hora'] ?? 999999);
                $bPrice = (float) ($b['precio_por_hora'] ?? 999999);
                return $aPrice <=> $bPrice;
            })
            ->values();
    }
}
