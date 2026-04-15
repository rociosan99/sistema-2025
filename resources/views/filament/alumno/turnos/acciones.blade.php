@php
    /** @var \App\Models\Turno $record */

    use App\Models\Turno;
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

        if (preg_match('/^\d{2}:\d{2}$/', $horaInicioStr)) {
            $horaInicioStr .= ':00';
        }

        if (preg_match('/^\d{2}:\d{2}$/', $horaFinStr)) {
            $horaFinStr .= ':00';
        }

        if ($horaInicioStr !== '') {
            $inicioTurno = $fecha->copy()->setTimeFromTimeString($horaInicioStr);
        }

        if ($horaFinStr !== '') {
            $finTurno = $fecha->copy()->setTimeFromTimeString($horaFinStr);
        }
    } catch (\Throwable $e) {
        $inicioTurno = null;
        $finTurno = null;
    }

    $ahora = now();

    $yaEmpezo = $inicioTurno ? $ahora->gte($inicioTurno) : false;
    $yaFinalizo = $finTurno ? $ahora->gte($finTurno) : false;

    $estado = (string) $record->estado;
    $enlaceClase = trim((string) ($record->enlace_clase ?? ''));

    $estaCancelado = $estado === Turno::ESTADO_CANCELADO;
    $estaRechazado = $estado === Turno::ESTADO_RECHAZADO;
    $estaConfirmado = $estado === Turno::ESTADO_CONFIRMADO;
    $estaVencidoEstado = $estado === Turno::ESTADO_VENCIDO;

    $vencidoPorHora = in_array($estado, [
        Turno::ESTADO_PENDIENTE,
        Turno::ESTADO_ACEPTADO,
        Turno::ESTADO_PENDIENTE_PAGO,
    ], true) && $yaEmpezo;

    $estaVencido = $estaVencidoEstado || $vencidoPorHora;

    $puedePagar = ($estado === Turno::ESTADO_PENDIENTE_PAGO) && ! $yaEmpezo && ! $estaVencido;

    $puedeCancelar = in_array($estado, [
        Turno::ESTADO_PENDIENTE,
        Turno::ESTADO_ACEPTADO,
        Turno::ESTADO_PENDIENTE_PAGO,
        Turno::ESTADO_CONFIRMADO,
    ], true) && ! $yaEmpezo && ! $estaVencido;

    $horasRegla = (int) config('turnos.cancelacion_sin_cargo_horas', 24);
    $horasHastaInicio = $inicioTurno ? $ahora->diffInHours($inicioTurno, false) : -999;

    $puedeReprogramar = in_array($estado, [
        Turno::ESTADO_PENDIENTE,
        Turno::ESTADO_PENDIENTE_PAGO,
        Turno::ESTADO_CONFIRMADO,
    ], true) && ! $yaEmpezo && ! $estaVencido && ($horasHastaInicio >= $horasRegla);

    $puedeVerEnlace = $estaConfirmado && $enlaceClase !== '';
@endphp

<div style="display:flex; gap:8px; flex-wrap:wrap; align-items:center;">

    @if($estaCancelado)
        <span style="font-size:12px; font-weight:800; color:#991b1b;">❌ Cancelaste esta clase</span>

    @elseif($estaRechazado)
        <span style="font-size:12px; font-weight:800; color:#991b1b;">❌ Solicitud rechazada</span>

    @elseif($estaVencido)
        <span style="font-size:12px; font-weight:700; color:#6b7280;">⏰ Turno vencido</span>

    @elseif($estaConfirmado && $yaEmpezo && ! $yaFinalizo)
        <span style="font-size:12px; font-weight:700; color:#6b7280;">⏳ Clase en curso</span>

    @elseif($estaConfirmado && $yaFinalizo)
        <span style="font-size:12px; font-weight:700; color:#166534;">✅ Clase finalizada</span>
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

    @if($puedeVerEnlace)
        <a
            href="{{ $enlaceClase }}"
            target="_blank"
            rel="noopener noreferrer"
            style="display:inline-flex; align-items:center; gap:6px; padding:6px 10px; border-radius:10px; background:#7c3aed; color:#fff; font-size:12px; font-weight:700; text-decoration:none;"
        >
            🔗 Enlace de clase
        </a>
    @elseif($estado === Turno::ESTADO_PENDIENTE_PAGO && $enlaceClase !== '')
        <span style="font-size:12px; font-weight:700; color:#6b7280;">
            🔒 El enlace se habilita cuando se acredita el pago
        </span>
    @endif
</div>