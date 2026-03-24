<?php

namespace App\Services;

use App\Models\CalificacionProfesor;
use App\Models\Disponibilidad;
use App\Models\SlotHold;
use App\Models\Turno;
use App\Models\User;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class SlotService
{
    public function obtenerSlotsPorMateria(int $materiaId, Carbon $fecha, ?int $temaId = null): Collection
    {
        $diaSemana = $fecha->dayOfWeekIso; // 1=lunes ... 7=domingo
        $duracion = (int) (config('turnos.duracion_slot', 60) ?: 60);

        $ahora = now();
        $esHoy = $fecha->isSameDay($ahora);

        $usaHolds = Schema::hasTable('slot_holds');

        $profesoresQuery = DB::table('profesor_materia as pm')
            ->join('users as u', 'u.id', '=', 'pm.profesor_id')
            ->where('pm.materia_id', $materiaId)
            ->where('u.role', 'profesor')
            ->where('u.activo', true);

        if ($temaId) {
            $profesoresQuery->join('profesor_tema as pt', 'pt.profesor_id', '=', 'pm.profesor_id')
                ->where('pt.tema_id', $temaId);
        }

        $profesoresIds = $profesoresQuery
            ->pluck('pm.profesor_id')
            ->unique()
            ->values()
            ->all();

        if (empty($profesoresIds)) {
            return collect();
        }

        $profesores = User::query()
            ->whereIn('id', $profesoresIds)
            ->where('role', 'profesor')
            ->where('activo', true)
            ->get(['id', 'name', 'apellido', 'activo']);

        if ($profesores->isEmpty()) {
            return collect();
        }

        $preciosPorProfesor = DB::table('profesor_materia')
            ->where('materia_id', $materiaId)
            ->whereIn('profesor_id', $profesores->pluck('id')->all())
            ->pluck('precio_por_hora', 'profesor_id')
            ->toArray();

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

            $bloques = Disponibilidad::query()
                ->where('profesor_id', $profesor->id)
                ->where('dia_semana', $diaSemana)
                ->get();

            if ($bloques->isEmpty()) {
                continue;
            }

            $turnos = Turno::query()
                ->where('profesor_id', $profesor->id)
                ->whereDate('fecha', $fecha->toDateString())
                ->whereIn('estado', [
                    Turno::ESTADO_PENDIENTE,
                    Turno::ESTADO_ACEPTADO,
                    Turno::ESTADO_PENDIENTE_PAGO,
                    Turno::ESTADO_CONFIRMADO,
                ])
                ->get(['hora_inicio', 'hora_fin']);

            foreach ($bloques as $bloque) {
                $horaInicioBloque = $fecha->copy()->setTimeFromTimeString((string) $bloque->hora_inicio);
                $horaFinBloque = $fecha->copy()->setTimeFromTimeString((string) $bloque->hora_fin);

                $cursor = $horaInicioBloque->copy();

                while ($cursor->copy()->addMinutes($duracion)->lte($horaFinBloque)) {
                    $desde = $cursor->format('H:i:s');
                    $hasta = $cursor->copy()->addMinutes($duracion)->format('H:i:s');

                    if ($esHoy) {
                        $inicioSlot = Carbon::parse($fecha->toDateString() . ' ' . $desde);

                        if ($inicioSlot->lte($ahora)) {
                            $cursor->addMinutes($duracion);
                            continue;
                        }
                    }

                    $hayChoque = $turnos->contains(function (Turno $t) use ($desde, $hasta) {
                        $inicio = $t->hora_inicio instanceof CarbonInterface
                            ? $t->hora_inicio->format('H:i:s')
                            : (string) $t->hora_inicio;

                        $fin = $t->hora_fin instanceof CarbonInterface
                            ? $t->hora_fin->format('H:i:s')
                            : (string) $t->hora_fin;

                        return $inicio < $hasta && $fin > $desde;
                    });

                    if ($hayChoque) {
                        $cursor->addMinutes($duracion);
                        continue;
                    }

                    if ($usaHolds) {
                        $holdActivo = SlotHold::query()
                            ->where('profesor_id', $profesor->id)
                            ->whereDate('fecha', $fecha->toDateString())
                            ->where('estado', SlotHold::ESTADO_ACTIVO)
                            ->where(function ($q) {
                                $q->whereNull('expires_at')
                                    ->orWhere('expires_at', '>', now());
                            })
                            ->where(function ($q) use ($desde, $hasta) {
                                $q->where('hora_inicio', '<', $hasta)
                                    ->where('hora_fin', '>', $desde);
                            })
                            ->exists();

                        if ($holdActivo) {
                            $cursor->addMinutes($duracion);
                            continue;
                        }
                    }

                    $precioPorHora = $preciosPorProfesor[$profesor->id] ?? null;
                    $precioTotal = null;

                    if ($precioPorHora !== null) {
                        $horas = $duracion / 60;
                        $precioTotal = $precioPorHora * $horas;
                    }

                    $slots->push([
                        'profesor_id' => $profesor->id,
                        'profesor_nombre' => $nombreCompleto ?: ($profesor->name ?? 'Profesor'),
                        'fecha' => $fecha->toDateString(),
                        'hora_inicio' => $desde,
                        'hora_fin' => $hasta,
                        'desde' => substr($desde, 0, 5),
                        'hasta' => substr($hasta, 0, 5),
                        'materia_id' => $materiaId,
                        'tema_id' => $temaId,
                        'rating_avg' => $ratingAvg,
                        'rating_count' => $ratingCnt,
                        'precio_por_hora' => $precioPorHora,
                        'precio_total' => $precioTotal,
                    ]);

                    $cursor->addMinutes($duracion);
                }
            }
        }

        if ($slots->isEmpty()) {
            return $slots;
        }

        return $slots
            ->sort(function ($a, $b) {
                $aAvg = (float) ($a['rating_avg'] ?? 0);
                $bAvg = (float) ($b['rating_avg'] ?? 0);
                if ($aAvg !== $bAvg) {
                    return $bAvg <=> $aAvg;
                }

                $aCnt = (int) ($a['rating_count'] ?? 0);
                $bCnt = (int) ($b['rating_count'] ?? 0);
                if ($aCnt !== $bCnt) {
                    return $bCnt <=> $aCnt;
                }

                $aPrice = (float) ($a['precio_por_hora'] ?? 999999);
                $bPrice = (float) ($b['precio_por_hora'] ?? 999999);
                return $aPrice <=> $bPrice;
            })
            ->values();
    }

    public function crearHoldDesdeTurnoCanceladoParaReemplazo(Turno $turno, array $meta = []): int
    {
        $ttlMin = (int) config('matching.replacement_invite_ttl_minutes', 30);

        $hold = SlotHold::create([
            'profesor_id' => (int) $turno->profesor_id,
            'fecha' => $turno->fecha->toDateString(),
            'hora_inicio' => (string) $turno->hora_inicio,
            'hora_fin' => (string) $turno->hora_fin,
            'motivo' => 'reemplazo',
            'estado' => SlotHold::ESTADO_ACTIVO,
            'expires_at' => now()->addMinutes($ttlMin),
            'meta' => [
                'turno_cancelado_id' => (int) $turno->id,
                'motivo_sistema' => 'buscar_reemplazo',
                ...$meta,
            ],
        ]);

        return (int) $hold->id;
    }
}