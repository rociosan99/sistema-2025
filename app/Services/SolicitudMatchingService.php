<?php

namespace App\Services;

use App\Models\Disponibilidad;
use App\Models\SolicitudDisponibilidad;
use App\Models\Turno;
use App\Models\User;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class SolicitudMatchingService
{
    /**
     * Genera "ofertas candidatas" por tramos (slots) dentro del rango pedido por el alumno,
     * respetando disponibilidad del profesor y ocupación por turnos existentes.
     *
     * Devuelve una colección de arrays con:
     * - profesor_id
     * - profesor_nombre
     * - precio_por_hora
     * - hora_inicio (del tramo ofrecido)
     * - hora_fin    (del tramo ofrecido)
     *
     * @return Collection<int, array{
     *     profesor_id:int,
     *     profesor_nombre:string,
     *     precio_por_hora:float|null,
     *     hora_inicio:string,
     *     hora_fin:string
     * }>
     */
    public function generarOfertasCandidatas(SolicitudDisponibilidad $solicitud): Collection
    {
        $fecha = Carbon::parse($solicitud->fecha);
        $diaSemana = $fecha->dayOfWeekIso; // 1..7

        $duracionMin = (int) (config('matching.slot_minutes', 60) ?: 60);

        $solDesde = $this->normalizarHora($solicitud->hora_inicio); // "H:i:s"
        $solHasta = $this->normalizarHora($solicitud->hora_fin);

        // 1) Profes que dictan esa materia
        $profesIds = DB::table('profesor_materia')
            ->where('materia_id', $solicitud->materia_id)
            ->pluck('profesor_id')
            ->unique()
            ->values()
            ->all();

        if (empty($profesIds)) {
            return collect();
        }

        // 2) Profesores “activos” en users
        $profesores = User::query()
            ->whereIn('id', $profesIds)
            ->where('role', 'profesor')
            ->get(['id', 'name', 'apellido']);

        if ($profesores->isEmpty()) {
            return collect();
        }

        // 3) Precios por profesor (pivot)
        $preciosPorProfesor = DB::table('profesor_materia')
            ->where('materia_id', $solicitud->materia_id)
            ->whereIn('profesor_id', $profesores->pluck('id')->all())
            ->pluck('precio_por_hora', 'profesor_id')
            ->toArray();

        // 4) Turnos ocupados por profesor ese día
        $turnosPorProfesor = Turno::query()
            ->whereIn('profesor_id', $profesores->pluck('id')->all())
            ->whereDate('fecha', $fecha->toDateString())
            ->whereIn('estado', [
                Turno::ESTADO_PENDIENTE,
                Turno::ESTADO_PENDIENTE_PAGO,
                Turno::ESTADO_CONFIRMADO,
                Turno::ESTADO_ACEPTADO, // por compatibilidad
            ])
            ->get(['profesor_id', 'hora_inicio', 'hora_fin'])
            ->groupBy('profesor_id');

        $result = collect();

        foreach ($profesores as $p) {
            $nombre = trim(($p->name ?? '') . ' ' . ($p->apellido ?? ''));
            $precioPorHora = isset($preciosPorProfesor[$p->id]) ? (float) $preciosPorProfesor[$p->id] : null;

            // 5) Disponibilidades del profesor ese día
            $bloques = Disponibilidad::query()
                ->where('profesor_id', $p->id)
                ->where('dia_semana', $diaSemana)
                ->get(['hora_inicio', 'hora_fin']);

            if ($bloques->isEmpty()) {
                continue;
            }

            // 6) Convertimos el rango de solicitud a DateTime
            $solDesdeDT = $fecha->copy()->setTimeFromTimeString($solDesde);
            $solHastaDT = $fecha->copy()->setTimeFromTimeString($solHasta);

            // 7) Turnos ocupados (para choque)
            $turnos = $turnosPorProfesor->get($p->id, collect());

            foreach ($bloques as $b) {
                $bloqDesde = $fecha->copy()->setTimeFromTimeString($this->normalizarHora((string) $b->hora_inicio));
                $bloqHasta = $fecha->copy()->setTimeFromTimeString($this->normalizarHora((string) $b->hora_fin));

                // 8) Intersección (solape) solicitud ∩ disponibilidad
                $desde = $solDesdeDT->copy()->max($bloqDesde);
                $hasta = $solHastaDT->copy()->min($bloqHasta);

                // Si no hay solape real, no sirve
                if ($desde->gte($hasta)) {
                    continue;
                }

                // 9) Generar slots dentro del solape
                $cursor = $desde->copy();

                while ($cursor->copy()->addMinutes($duracionMin)->lte($hasta)) {

                    $slotDesde = $cursor->format('H:i:s');
                    $slotHasta = $cursor->copy()->addMinutes($duracionMin)->format('H:i:s');

                    // 10) Chequear choque contra turnos ya reservados
                    $hayChoque = $turnos->contains(function ($t) use ($slotDesde, $slotHasta) {
                        $tDesde = $t->hora_inicio instanceof CarbonInterface
                            ? $t->hora_inicio->format('H:i:s')
                            : $this->normalizarHora((string) $t->hora_inicio);

                        $tHasta = $t->hora_fin instanceof CarbonInterface
                            ? $t->hora_fin->format('H:i:s')
                            : $this->normalizarHora((string) $t->hora_fin);

                        return $tDesde < $slotHasta && $tHasta > $slotDesde;
                    });

                    if (! $hayChoque) {
                        $result->push([
                            'profesor_id'     => $p->id,
                            'profesor_nombre' => $nombre ?: ($p->name ?? 'Profesor'),
                            'precio_por_hora' => $precioPorHora,
                            'hora_inicio'     => $slotDesde,
                            'hora_fin'        => $slotHasta,
                        ]);
                    }

                    $cursor->addMinutes($duracionMin);
                }
            }
        }

        // opcional: ordenar (por precio o lo que quieras)
        return $result->values();
    }

    private function normalizarHora(string $hora): string
    {
        $hora = trim($hora);

        // "14:00" -> "14:00:00"
        if (preg_match('/^\d{2}:\d{2}$/', $hora)) {
            return $hora . ':00';
        }

        // "2026-02-09 14:00:00" -> "14:00:00"
        if (preg_match('/^\d{4}-\d{2}-\d{2}\s+\d{2}:\d{2}:\d{2}$/', $hora)) {
            return Carbon::parse($hora)->format('H:i:s');
        }

        return $hora; // ya "H:i:s"
    }
}
