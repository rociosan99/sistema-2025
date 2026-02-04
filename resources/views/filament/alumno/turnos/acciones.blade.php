{{-- resources/views/filament/alumno/turnos/acciones.blade.php --}}

@php
    /** @var \App\Models\Turno $record */

    use Carbon\Carbon;
    use Carbon\CarbonInterface;

    // ===== Calcular si el turno ya terminó (fecha + hora_fin) =====
    $yaPasoElTurno = false;

    try {
        $fecha = $record->fecha instanceof CarbonInterface
            ? $record->fecha->copy()
            : Carbon::parse($record->fecha);

        $horaFinStr = (string) ($record->hora_fin ?? '');

        // Si viene con fecha incluida: "YYYY-MM-DD HH:MM:SS"
        if (preg_match('/^\d{4}-\d{2}-\d{2}\s+\d{2}:\d{2}:\d{2}$/', $horaFinStr)) {
            $horaFinStr = Carbon::parse($horaFinStr)->format('H:i:s');
        }

        // Si viene "HH:MM", convertir a "HH:MM:SS"
        if (preg_match('/^\d{2}:\d{2}$/', $horaFinStr)) {
            $horaFinStr .= ':00';
        }

        if ($horaFinStr !== '') {
            $finTurno = $fecha->copy()->setTimeFromTimeString($horaFinStr);
            $yaPasoElTurno = $finTurno->isPast();
        }
    } catch (\Throwable $e) {
        $yaPasoElTurno = false;
    }

    // ===== Reglas de botones =====

    // ✅ Pagar: solo si el profesor lo aceptó (pendiente_pago) y el turno NO pasó
    $puedePagar = ($record->estado === \App\Models\Turno::ESTADO_PENDIENTE_PAGO) && ! $yaPasoElTurno;

    // ✅ Cancelar: solo si NO pasó y está en estados "abiertos"
    $puedeCancelar = in_array($record->estado, [
        \App\Models\Turno::ESTADO_PENDIENTE,
        \App\Models\Turno::ESTADO_ACEPTADO,
        \App\Models\Turno::ESTADO_PENDIENTE_PAGO,
    ], true) && ! $yaPasoElTurno;

@endphp

<div style="display:flex; gap:8px; flex-wrap:wrap; align-items:center;">

    @if($puedePagar)
        <a
            href="{{ route('mp.pagar', ['turno' => $record->id]) }}"
            target="_blank"
            style="display:inline-flex; align-items:center; gap:6px; padding:6px 10px; border-radius:10px; background:#2563eb; color:#fff; font-size:12px; font-weight:700; text-decoration:none;"
        >
            💳 Pagar
        </a>
    @endif

    @if($puedeCancelar)
        <form
            method="POST"
            action="{{ route('turnos.cancelar-panel', ['turno' => $record->id]) }}"
            onsubmit="return confirm('¿Seguro que querés cancelar este turno?');"
            style="margin:0;"
        >
            @csrf
            <button
                type="submit"
                style="display:inline-flex; align-items:center; gap:6px; padding:6px 10px; border-radius:10px; background:#ef4444; color:#fff; font-size:12px; font-weight:700; border:none; cursor:pointer;"
            >
                ❌ Cancelar
            </button>
        </form>
    @endif

    @if($yaPasoElTurno && in_array($record->estado, [
        \App\Models\Turno::ESTADO_PENDIENTE,
        \App\Models\Turno::ESTADO_ACEPTADO,
        \App\Models\Turno::ESTADO_PENDIENTE_PAGO,
        \App\Models\Turno::ESTADO_VENCIDO,
    ], true))
        <span style="font-size:12px; font-weight:700; color:#6b7280;">
            ⏰ Turno finalizado
        </span>
    @endif

</div>
