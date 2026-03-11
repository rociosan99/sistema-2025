@php
    /** @var \App\Models\Turno $record */

    use Carbon\Carbon;
    use Carbon\CarbonInterface;

    $inicioTurno = null;
    $finTurno = null;

    try {
        $fecha = $record->fecha instanceof CarbonInterface
            ? $record->fecha->copy()
            : Carbon::parse($record->fecha);

        $horaInicioStr = (string) ($record->hora_inicio ?? '');
        $horaFinStr    = (string) ($record->hora_fin ?? '');

        if (preg_match('/^\d{2}:\d{2}$/', $horaInicioStr)) $horaInicioStr .= ':00';
        if (preg_match('/^\d{2}:\d{2}$/', $horaFinStr)) $horaFinStr .= ':00';

        if ($horaInicioStr !== '') $inicioTurno = $fecha->copy()->setTimeFromTimeString($horaInicioStr);
        if ($horaFinStr !== '') $finTurno = $fecha->copy()->setTimeFromTimeString($horaFinStr);
    } catch (\Throwable $e) {
        $inicioTurno = null;
        $finTurno = null;
    }

    $yaEmpezo   = $inicioTurno ? $inicioTurno->isPast() : false;
    $yaFinalizo = $finTurno ? $finTurno->isPast() : false;

    $puedePagar = ($record->estado === \App\Models\Turno::ESTADO_PENDIENTE_PAGO) && ! $yaEmpezo;

    $puedeCancelar = in_array($record->estado, [
        \App\Models\Turno::ESTADO_PENDIENTE,
        \App\Models\Turno::ESTADO_ACEPTADO,
        \App\Models\Turno::ESTADO_PENDIENTE_PAGO,
        \App\Models\Turno::ESTADO_CONFIRMADO,
    ], true) && ! $yaEmpezo;

    // ✅ Reprogramar solo si >= 24h
    $horasRegla = (int) config('turnos.cancelacion_sin_cargo_horas', 24);
    $horasHastaInicio = $inicioTurno ? now()->diffInHours($inicioTurno, false) : -999;

    $puedeReprogramar = in_array($record->estado, [
        \App\Models\Turno::ESTADO_PENDIENTE,
        \App\Models\Turno::ESTADO_PENDIENTE_PAGO,
        \App\Models\Turno::ESTADO_CONFIRMADO,
    ], true) && ! $yaEmpezo && ($horasHastaInicio >= $horasRegla);

    $estaCancelado = ($record->estado === \App\Models\Turno::ESTADO_CANCELADO);
@endphp

<div style="display:flex; gap:8px; flex-wrap:wrap; align-items:center;">

    @if($estaCancelado)
        <span style="font-size:12px; font-weight:800; color:#991b1b;">❌ Cancelaste esta clase</span>
    @elseif($yaFinalizo)
        <span style="font-size:12px; font-weight:700; color:#6b7280;">✅ Clase finalizada</span>
    @elseif($yaEmpezo)
        <span style="font-size:12px; font-weight:700; color:#6b7280;">⏳ Clase en curso</span>
    @endif

    @if($puedePagar)
        <a
            href="{{ route('mp.pagar', ['turno' => $record->id]) }}"
            target="_blank"
            style="display:inline-flex; align-items:center; gap:6px; padding:6px 10px; border-radius:10px; background:#2563eb; color:#fff; font-size:12px; font-weight:700; text-decoration:none;"
        >
            💳 Pagar
        </a>
    @endif

    @if($puedeReprogramar)
        <a
            href="{{ url('/alumno/reprogramar-turno?turno=' . $record->id) }}"
            style="display:inline-flex; align-items:center; gap:6px; padding:6px 10px; border-radius:10px; background:#10b981; color:#fff; font-size:12px; font-weight:800; text-decoration:none;"
        >
            🗓️ Reprogramar
        </a>
    @endif

    @if($puedeCancelar)
        <form
            method="POST"
            action="{{ route('turnos.cancelar-panel', ['turno' => $record->id]) }}"
            onsubmit="return confirm('¿Seguro que querés cancelar esta clase?');"
            style="margin:0;"
        >
            @csrf
            <button
                type="submit"
                style="display:inline-flex; align-items:center; gap:6px; padding:6px 10px; border-radius:10px; background:#ef4444; color:#fff; font-size:12px; font-weight:700; border:none; cursor:pointer;"
            >
                ❌ Cancelar clase
            </button>
        </form>
    @endif
</div>