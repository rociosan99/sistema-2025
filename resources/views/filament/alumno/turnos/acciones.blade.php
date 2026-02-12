{{-- resources/views/filament/alumno/turnos/acciones.blade.php --}}

@php
    /** @var \App\Models\Turno $record */

    use Carbon\Carbon;
    use Carbon\CarbonInterface;

    // ===== Calcular inicio y fin del turno (fecha + hora_inicio / hora_fin) =====
    $inicioTurno = null;
    $finTurno = null;

    try {
        $fecha = $record->fecha instanceof CarbonInterface
            ? $record->fecha->copy()
            : Carbon::parse($record->fecha);

        $horaInicioStr = (string) ($record->hora_inicio ?? '');
        $horaFinStr    = (string) ($record->hora_fin ?? '');

        // Si viene con fecha incluida: "YYYY-MM-DD HH:MM:SS"
        if (preg_match('/^\d{4}-\d{2}-\d{2}\s+\d{2}:\d{2}:\d{2}$/', $horaInicioStr)) {
            $horaInicioStr = Carbon::parse($horaInicioStr)->format('H:i:s');
        }
        if (preg_match('/^\d{4}-\d{2}-\d{2}\s+\d{2}:\d{2}:\d{2}$/', $horaFinStr)) {
            $horaFinStr = Carbon::parse($horaFinStr)->format('H:i:s');
        }

        // Si viene "HH:MM", convertir a "HH:MM:SS"
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

    $yaEmpezo   = $inicioTurno ? $inicioTurno->isPast() : false;
    $yaFinalizo = $finTurno ? $finTurno->isPast() : false;

    // ===== Reglas de botones =====

    // ✅ Pagar: solo si está pendiente_pago y NO empezó la clase
    $puedePagar = ($record->estado === \App\Models\Turno::ESTADO_PENDIENTE_PAGO) && ! $yaEmpezo;

    // ✅ Cancelar clase: hasta que EMPIECE la clase (incluye pagada)
    $puedeCancelar = in_array($record->estado, [
        \App\Models\Turno::ESTADO_PENDIENTE,
        \App\Models\Turno::ESTADO_ACEPTADO,
        \App\Models\Turno::ESTADO_PENDIENTE_PAGO,
        \App\Models\Turno::ESTADO_CONFIRMADO, // 👈 pagada
    ], true) && ! $yaEmpezo;

    $estaCancelado = ($record->estado === \App\Models\Turno::ESTADO_CANCELADO);
@endphp

<div style="display:flex; gap:8px; flex-wrap:wrap; align-items:center;">

    {{-- ✅ Etiquetas --}}
    @if($estaCancelado)
        <span style="font-size:12px; font-weight:800; color:#991b1b;">
            ❌ Cancelaste esta clase
        </span>
    @elseif($yaFinalizo)
        <span style="font-size:12px; font-weight:700; color:#6b7280;">
            ✅ Clase finalizada
        </span>
    @elseif($yaEmpezo)
        <span style="font-size:12px; font-weight:700; color:#6b7280;">
            ⏳ Clase en curso
        </span>
    @endif

    {{-- ✅ Botón pagar --}}
    @if($puedePagar)
        <a
            href="{{ route('mp.pagar', ['turno' => $record->id]) }}"
            target="_blank"
            style="display:inline-flex; align-items:center; gap:6px; padding:6px 10px; border-radius:10px; background:#2563eb; color:#fff; font-size:12px; font-weight:700; text-decoration:none;"
        >
            💳 Pagar
        </a>
    @endif

    {{-- ✅ Botón cancelar (hasta que empiece) --}}
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
