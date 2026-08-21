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
    $estaReprogramado = $estaCancelado && !empty($record->reprogramado_por_turno_id);
    $estaRechazado = $estado === Turno::ESTADO_RECHAZADO;
    $estaConfirmado = $estado === Turno::ESTADO_CONFIRMADO;
    $estaSuspendidoProfesor = $estado === Turno::ESTADO_SUSPENDIDO_PROFESOR;
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
        Turno::ESTADO_CONFIRMADO,
    ], true) && ! $yaEmpezo && ! $estaVencido && ($horasHastaInicio >= $horasRegla);

    $puedeResolverSuspension = $estaSuspendidoProfesor && ! $yaEmpezo;

    $puedeVerEnlace = $estaConfirmado && ! $yaFinalizo && !empty($enlaceClase);
@endphp

<div style="display:flex; max-width:280px; gap:6px; flex-wrap:wrap; align-items:center;">

    @if($estaCancelado)
        <span style="font-size:12px; font-weight:800; color:#991b1b;">
            {{ $estaReprogramado ? '🔄 Reprogramado' : '❌ Suspendida por el alumno' }}
        </span>

    @elseif($estaRechazado)
        <span style="font-size:12px; font-weight:800; color:#991b1b;">❌ Solicitud rechazada</span>

    @elseif($estaVencido)
        <span style="font-size:12px; font-weight:700; color:#6b7280;">⏰ Turno vencido</span>

    @elseif($estaConfirmado && $yaEmpezo && ! $yaFinalizo)
        <span style="font-size:12px; font-weight:700; color:#6b7280;">⏳ Clase en curso</span>

    @elseif($estaConfirmado && $yaFinalizo)
        <span style="font-size:12px; font-weight:700; color:#166534;">✅ Clase finalizada</span>

    @elseif($estaSuspendidoProfesor)
        <span style="font-size:12px; font-weight:700; color:#b45309;">⚠️ Clase suspendida por el profesor</span>
    @endif

    @if($estaSuspendidoProfesor)
        <span style="font-size:12px; font-weight:700; color:#525252; width:100%; max-width:260px;">
            El pago sigue vigente. Reprogramá con el mismo profesor sin necesidad de pagar de nuevo.
        </span>
    @endif

    @if($puedePagar)
        <a href="{{ \App\Filament\Alumno\Pages\CompletarPagoTurno::getUrl(['record' => $record->id], panel: 'alumno') }}"
           style="display:inline-flex; align-items:center; gap:6px; padding:6px 10px; border-radius:10px; background:#2563eb; color:#fff; font-size:12px; font-weight:700; text-decoration:none;">
            💳 Pagar
        </a>
    @endif

    @if($puedeReprogramar)
        <a href="{{ url('/alumno/reprogramar-turno?turno=' . $record->id) }}"
           style="display:inline-flex; align-items:center; gap:6px; padding:6px 10px; border-radius:10px; background:#10b981; color:#fff; font-size:12px; font-weight:800; text-decoration:none;">
            🗓️ Reprogramar
        </a>
    @endif

    @if($puedeCancelar)
        <x-filament::modal id="cancelar-turno-{{ $record->id }}" width="xl">
            <x-slot name="trigger">
                <button type="button"
                        style="display:inline-flex; align-items:center; gap:6px; padding:6px 10px; border-radius:10px; background:#ef4444; color:#fff; font-size:12px; font-weight:700; border:none; cursor:pointer;">
                    ❌ Suspender clase
                </button>
            </x-slot>

            <x-slot name="heading">
                Términos y condiciones de suspensión
            </x-slot>

            <form method="POST"
                  action="{{ route('turnos.cancelar-panel', ['turno' => $record->id]) }}"
                  x-data="{ aceptado: false }"
                  style="display:flex; width:100%; min-width:0; flex-direction:column; gap:18px; overflow-wrap:anywhere; white-space:normal;">
                @csrf

                @if($politicaCancelacion)
                    @php
                        $formatearPorcentaje = static fn ($valor) => rtrim(
                            rtrim(number_format((float) $valor, 2, ',', '.'), '0'),
                            ','
                        );
                    @endphp

                    <input type="hidden"
                           name="politica_version"
                           value="{{ $politicaCancelacion['version'] }}">

                    <div style="display:flex; min-width:0; flex-direction:column; gap:10px; color:#475569; font-size:14px; line-height:1.6; white-space:normal;">
                        <p style="margin:0; max-width:100%; white-space:normal; overflow-wrap:anywhere;">
                            Si suspendés con <strong>{{ $politicaCancelacion['horas_sin_penalizacion'] }} horas o más</strong> de anticipación, se acreditará el <strong>{{ $formatearPorcentaje($politicaCancelacion['porcentaje_credito_anticipado']) }}%</strong> del importe pagado.
                        </p>
                        <p style="margin:0; max-width:100%; white-space:normal; overflow-wrap:anywhere;">
                            Si suspendés con menos de <strong>{{ $politicaCancelacion['horas_sin_penalizacion'] }} horas</strong>, se acreditará el <strong>{{ $formatearPorcentaje($politicaCancelacion['porcentaje_credito_tardio']) }}%</strong> y se aplicará una penalización del <strong>{{ $formatearPorcentaje($politicaCancelacion['porcentaje_penalizacion']) }}%</strong>.
                        </p>
                        <p style="margin:0; max-width:100%; white-space:normal; overflow-wrap:anywhere;">
                            Los créditos tendrán una vigencia de <strong>{{ $politicaCancelacion['vigencia_creditos_dias'] }} días</strong> desde su acreditación. Permanecen dentro de la plataforma y no se reintegran al medio de pago.
                        </p>
                        <p style="margin:0; max-width:100%; white-space:normal; overflow-wrap:anywhere;">
                            La suspensión es definitiva una vez confirmada.
                        </p>
                    </div>

                    <label style="display:flex; width:100%; min-width:0; align-items:flex-start; gap:10px; color:#111827; font-size:14px; font-weight:600; cursor:pointer; white-space:normal;">
                        <input type="checkbox"
                               name="acepta_terminos"
                               value="1"
                               required
                               x-model="aceptado"
                               style="width:16px; height:16px; margin-top:2px; flex-shrink:0;">
                        <span style="min-width:0; flex:1; white-space:normal; overflow-wrap:anywhere;">He leído y acepto los términos y condiciones de suspensión.</span>
                    </label>
                @else
                    <div style="padding:12px; border:1px solid #fecaca; border-radius:10px; background:#fef2f2; color:#991b1b; font-size:14px; white-space:normal; overflow-wrap:anywhere;">
                        {{ $politicaCancelacionError ?? 'La política de suspensión no está disponible o es inválida.' }}
                    </div>
                @endif

                <div style="display:flex; width:100%; flex-wrap:wrap; justify-content:flex-end; align-items:center; gap:10px; white-space:normal;">
                    <button type="button"
                            x-on:click="$dispatch('close-modal', { id: 'cancelar-turno-{{ $record->id }}' })"
                            style="display:inline-flex; align-items:center; justify-content:center; padding:8px 14px; border-radius:10px; background:#f1f5f9; color:#475569; font-size:13px; font-weight:700; border:1px solid #cbd5e1; cursor:pointer;">
                        Volver
                    </button>

                    <button type="submit"
                            @disabled(! $politicaCancelacion)
                            x-bind:disabled="! aceptado"
                            x-bind:style="! aceptado ? 'opacity:0.5; cursor:not-allowed;' : 'opacity:1; cursor:pointer;'"
                            style="display:inline-flex; align-items:center; justify-content:center; padding:8px 14px; border-radius:10px; background:#dc2626; color:#fff; font-size:13px; font-weight:800; border:none;">
                        Confirmar suspensión
                    </button>
                </div>
            </form>
        </x-filament::modal>
    @endif

    @if($puedeResolverSuspension)
        <a href="{{ \App\Filament\Alumno\Pages\ResolverSuspension::getUrl(['record' => $record->id], panel: 'alumno') }}"
           style="display:inline-flex; align-items:center; gap:6px; padding:6px 10px; border-radius:10px; background:#10b981; color:#fff; font-size:12px; font-weight:800; text-decoration:none;">
            Resolver suspensión
        </a>
    @endif

    @if($puedeVerEnlace)
        <a href="{{ $enlaceClase }}"
           target="_blank"
           rel="noopener noreferrer"
           style="display:inline-flex; align-items:center; gap:6px; padding:6px 10px; border-radius:10px; background:#7c3aed; color:#fff; font-size:12px; font-weight:700; text-decoration:none;">
            🔗 Enlace de clase
        </a>
    @endif

</div>
