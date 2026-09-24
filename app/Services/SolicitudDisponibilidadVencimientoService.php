<?php

namespace App\Services;

use App\Models\OfertaSolicitud;
use App\Models\SolicitudDisponibilidad;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;

class SolicitudDisponibilidadVencimientoService
{
    /**
     * @return array<int, array{0:string, 1:string}>
     */
    public function slotsFuturosOfertables(
        SolicitudDisponibilidad $solicitud,
        ?CarbonInterface $ahora = null,
    ): array {
        $ahora ??= now();

        if ($solicitud->expires_at && $solicitud->expires_at->lte($ahora)) {
            return [];
        }

        $fecha = $solicitud->fecha->toDateString();

        return array_values(array_filter(
            $this->generarSlotsDeUnaHora(
                (string) $solicitud->hora_inicio,
                (string) $solicitud->hora_fin,
            ),
            fn (array $slot): bool => Carbon::parse("{$fecha} {$slot[0]}")->gt($ahora),
        ));
    }

    public function tieneSlotFuturoOfertable(
        SolicitudDisponibilidad $solicitud,
        ?CarbonInterface $ahora = null,
    ): bool {
        return $this->slotsFuturosOfertables($solicitud, $ahora) !== [];
    }

    public function sincronizarActivas(?int $alumnoId = null, ?int $solicitudId = null): void
    {
        $ids = SolicitudDisponibilidad::query()
            ->where('estado', SolicitudDisponibilidad::ESTADO_ACTIVA)
            ->when($alumnoId !== null, fn ($query) => $query->where('alumno_id', $alumnoId))
            ->when($solicitudId !== null, fn ($query) => $query->whereKey($solicitudId))
            ->pluck('id');

        foreach ($ids as $id) {
            $this->expirarSiCorresponde((int) $id);
        }
    }

    public function expirarSiCorresponde(int $solicitudId): bool
    {
        return DB::transaction(function () use ($solicitudId): bool {
            $solicitud = SolicitudDisponibilidad::query()
                ->whereKey($solicitudId)
                ->lockForUpdate()
                ->first();

            if (
                ! $solicitud
                || $solicitud->estado !== SolicitudDisponibilidad::ESTADO_ACTIVA
                || $this->tieneSlotFuturoOfertable($solicitud)
            ) {
                return false;
            }

            $solicitud->update([
                'estado' => SolicitudDisponibilidad::ESTADO_EXPIRADA,
            ]);

            OfertaSolicitud::query()
                ->where('solicitud_id', $solicitud->id)
                ->where('estado', OfertaSolicitud::ESTADO_PENDIENTE)
                ->update([
                    'estado' => OfertaSolicitud::ESTADO_EXPIRADA,
                ]);

            return true;
        }, 3);
    }

    /**
     * @return array<int, array{0:string, 1:string}>
     */
    private function generarSlotsDeUnaHora(string $horaInicio, string $horaFin): array
    {
        $inicio = Carbon::createFromFormat('H:i:s', $this->normalizarHora($horaInicio));
        $fin = Carbon::createFromFormat('H:i:s', $this->normalizarHora($horaFin));

        if ($inicio->gte($fin)) {
            return [];
        }

        $slots = [];
        $cursor = $inicio->copy();

        while ($cursor->lt($fin)) {
            $siguiente = $cursor->copy()->addHour();

            if ($siguiente->gt($fin)) {
                break;
            }

            $slots[] = [$cursor->format('H:i:s'), $siguiente->format('H:i:s')];
            $cursor = $siguiente;
        }

        return $slots;
    }

    private function normalizarHora(string $hora): string
    {
        $hora = trim($hora);

        if (preg_match('/^\d{4}-\d{2}-\d{2}\s+(\d{2}:\d{2}:\d{2})$/', $hora, $coincidencia)) {
            return $coincidencia[1];
        }

        return preg_match('/^\d{2}:\d{2}$/', $hora) ? "{$hora}:00" : $hora;
    }
}
