<x-filament-panels::page x-data x-on:pageshow.window="if ($event.persisted) window.location.reload()">
    @php
        $moneda = static fn (string|float|int $importe): string => '$' . number_format((float) $importe, 2, ',', '.');
        $hayCreditoDisponible = (float) $resumen['credito_disponible'] > 0;
        $nombreProfesor = trim(($turno->profesor?->name ?? '') . ' ' . ($turno->profesor?->apellido ?? '')) ?: '-';
        $fechaTurno = $turno->fecha?->format('d/m/Y') ?? '-';
        $horarioTurno = substr((string) $turno->hora_inicio, 0, 5) . ' - ' . substr((string) $turno->hora_fin, 0, 5);
        $duracionMinutos = (int) $turno->inicioDateTime()->diffInMinutes($turno->finDateTime());
        $horasDuracion = intdiv($duracionMinutos, 60);
        $minutosDuracion = $duracionMinutos % 60;
        $duracionTurno = collect([
            $horasDuracion > 0 ? $horasDuracion . ' ' . ($horasDuracion === 1 ? 'hora' : 'horas') : null,
            $minutosDuracion > 0 ? $minutosDuracion . ' min' : null,
        ])->filter()->implode(' ') ?: '-';
        $creditoAUtilizar = $usarCredito ? $resumen['credito_aplicable'] : 0;
        $importeRestante = $usarCredito ? $resumen['diferencia'] : $resumen['precio_total'];
    @endphp

    <style>
        .completar-pago-page {
            width: min(100%, 950px);
            margin-inline: auto;
        }

        .completar-pago-card {
            overflow: hidden;
            background: white;
            border: 1px solid var(--gray-200);
            border-radius: 16px;
            box-shadow: 0 1px 3px rgb(0 0 0 / 0.08);
        }

        .completar-pago-header {
            padding: 20px 28px;
            border-bottom: 1px solid var(--gray-200);
        }

        .completar-pago-header h2,
        .completar-pago-section h3 {
            margin: 0;
            color: var(--gray-950);
            font-weight: 650;
        }

        .completar-pago-header h2 {
            font-size: 1.125rem;
            line-height: 1.5rem;
        }

        .completar-pago-turno-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            margin-top: 10px;
        }

        .completar-pago-turno-id {
            color: var(--gray-500);
            font-size: 0.875rem;
            font-weight: 600;
        }

        .completar-pago-body {
            display: flex;
            flex-direction: column;
            gap: 24px;
            padding: 24px 28px;
        }

        .completar-pago-section + .completar-pago-section {
            padding-top: 24px;
            border-top: 1px solid var(--gray-200);
        }

        .completar-pago-section h3 {
            font-size: 1rem;
            line-height: 1.5rem;
        }

        .completar-pago-help {
            margin: 4px 0 0;
            color: var(--gray-500);
            font-size: 0.875rem;
            line-height: 1.35rem;
        }

        .completar-pago-class-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 18px 40px;
            margin-top: 18px;
        }

        .completar-pago-field {
            min-width: 0;
        }

        .completar-pago-field.is-wide {
            grid-column: 1 / -1;
        }

        .completar-pago-label {
            display: block;
            margin: 0;
            color: var(--gray-500);
            font-size: 0.75rem;
            font-weight: 600;
            line-height: 1rem;
            letter-spacing: 0.04em;
            text-transform: uppercase;
        }

        .completar-pago-value {
            display: block;
            margin-top: 5px;
            color: var(--gray-950);
            font-size: 0.9375rem;
            font-weight: 650;
            line-height: 1.35rem;
            overflow-wrap: anywhere;
        }

        .completar-pago-total {
            padding: 12px 16px;
            background: var(--gray-50);
            border-radius: 12px;
        }

        .completar-pago-total .completar-pago-value {
            font-size: 1.25rem;
            line-height: 1.5rem;
        }

        .completar-pago-summary {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 12px 24px;
            margin-top: 18px;
        }

        .completar-pago-summary-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            min-width: 0;
            padding: 12px 14px;
            background: var(--gray-50);
            border-radius: 10px;
        }

        .completar-pago-summary-row dt {
            color: var(--gray-600);
            font-size: 0.875rem;
        }

        .completar-pago-summary-row dd {
            margin: 0;
            color: var(--gray-950);
            font-size: 0.875rem;
            font-weight: 650;
            white-space: nowrap;
        }

        .completar-pago-summary-row.is-final {
            grid-column: 1 / -1;
            padding: 14px 0 0;
            background: transparent;
            border-top: 1px solid var(--gray-200);
            border-radius: 0;
        }

        .completar-pago-summary-row.is-final dt {
            color: var(--gray-950);
            font-weight: 650;
        }

        .completar-pago-summary-row.is-final dd {
            color: var(--primary-700);
            font-size: 1.25rem;
        }

        .completar-pago-summary-row.is-final.is-covered dd,
        .completar-pago-credit-used {
            color: var(--success-700);
        }

        .completar-pago-credit-option {
            display: flex;
            align-items: flex-start;
            gap: 12px;
            margin-top: 16px;
            padding: 14px 16px;
            cursor: pointer;
            background: var(--gray-50);
            border: 1px solid var(--gray-200);
            border-radius: 12px;
        }

        .completar-pago-credit-option input {
            flex: 0 0 auto;
            margin-top: 3px;
        }

        .completar-pago-credit-option strong,
        .completar-pago-credit-option span {
            display: block;
        }

        .completar-pago-credit-option strong {
            color: var(--gray-950);
            font-size: 0.9375rem;
        }

        .completar-pago-credit-option span {
            margin-top: 3px;
            color: var(--gray-500);
            font-size: 0.8125rem;
            line-height: 1.2rem;
        }

        .completar-pago-note {
            margin: 16px 0 0;
            color: var(--gray-500);
            font-size: 0.875rem;
            line-height: 1.35rem;
        }

        .completar-pago-alert {
            margin-top: 16px;
            padding: 13px 15px;
            color: var(--primary-800);
            font-size: 0.875rem;
            line-height: 1.35rem;
            background: var(--primary-50);
            border-radius: 12px;
        }

        .completar-pago-alert.is-success {
            color: var(--success-800);
            background: var(--success-50);
        }

        .completar-pago-alert p {
            margin: 0;
        }

        .completar-pago-alert p + p {
            margin-top: 4px;
        }

        .completar-pago-actions {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            padding-top: 20px;
            border-top: 1px solid var(--gray-200);
        }

        .completar-pago-primary-actions {
            display: flex;
            align-items: center;
            justify-content: flex-end;
            gap: 12px;
        }

        .dark .completar-pago-card {
            background: var(--gray-900);
            border-color: color-mix(in srgb, white 10%, transparent);
        }

        .dark .completar-pago-header,
        .dark .completar-pago-section + .completar-pago-section,
        .dark .completar-pago-summary-row.is-final,
        .dark .completar-pago-actions {
            border-color: color-mix(in srgb, white 10%, transparent);
        }

        .dark .completar-pago-header h2,
        .dark .completar-pago-section h3,
        .dark .completar-pago-value,
        .dark .completar-pago-summary-row dd,
        .dark .completar-pago-summary-row.is-final dt,
        .dark .completar-pago-credit-option strong {
            color: white;
        }

        .dark .completar-pago-total,
        .dark .completar-pago-summary-row,
        .dark .completar-pago-credit-option {
            background: color-mix(in srgb, white 5%, transparent);
        }

        .dark .completar-pago-credit-option {
            border-color: color-mix(in srgb, white 10%, transparent);
        }

        @media (max-width: 639px) {
            .completar-pago-header,
            .completar-pago-body {
                padding-inline: 18px;
            }

            .completar-pago-turno-row {
                align-items: flex-start;
                flex-direction: column;
                gap: 8px;
            }

            .completar-pago-class-grid,
            .completar-pago-summary {
                grid-template-columns: minmax(0, 1fr);
            }

            .completar-pago-field.is-wide,
            .completar-pago-summary-row.is-final {
                grid-column: auto;
            }

            .completar-pago-actions,
            .completar-pago-primary-actions {
                align-items: stretch;
                flex-direction: column;
                width: 100%;
            }

            .completar-pago-actions > *,
            .completar-pago-primary-actions > * {
                width: 100%;
            }
        }
    </style>

    <div class="completar-pago-page">
        <article class="completar-pago-card">
            <header class="completar-pago-header">
                <h2>Resumen de tu clase</h2>

                <div class="completar-pago-turno-row">
                    <span class="completar-pago-turno-id">Turno #{{ $turno->id }}</span>
                    <x-filament::badge color="primary">Pendiente de pago</x-filament::badge>
                </div>
            </header>

            <div class="completar-pago-body">
                <section class="completar-pago-section" aria-labelledby="datos-clase">
                    <h3 id="datos-clase">Datos de la clase</h3>

                    <dl class="completar-pago-class-grid">
                        <div class="completar-pago-field">
                            <dt class="completar-pago-label">Profesor</dt>
                            <dd class="completar-pago-value">{{ $nombreProfesor }}</dd>
                        </div>

                        <div class="completar-pago-field">
                            <dt class="completar-pago-label">Materia</dt>
                            <dd class="completar-pago-value">{{ $turno->materia?->materia_nombre ?? '-' }}</dd>
                        </div>

                        @if($turno->tema)
                            <div class="completar-pago-field is-wide">
                                <dt class="completar-pago-label">Tema</dt>
                                <dd class="completar-pago-value">{{ $turno->tema->tema_nombre }}</dd>
                            </div>
                        @endif

                        <div class="completar-pago-field">
                            <dt class="completar-pago-label">Fecha</dt>
                            <dd class="completar-pago-value">{{ $fechaTurno }}</dd>
                        </div>

                        <div class="completar-pago-field">
                            <dt class="completar-pago-label">Horario</dt>
                            <dd class="completar-pago-value">{{ $horarioTurno }}</dd>
                        </div>

                        <div class="completar-pago-field">
                            <dt class="completar-pago-label">Duración</dt>
                            <dd class="completar-pago-value">{{ $duracionTurno }}</dd>
                        </div>

                        <div class="completar-pago-field completar-pago-total">
                            <dt class="completar-pago-label">Total de la clase</dt>
                            <dd class="completar-pago-value">{{ $moneda($resumen['precio_total']) }}</dd>
                        </div>
                    </dl>
                </section>

                <section class="completar-pago-section" aria-labelledby="resumen-pago">
                    <h3 id="resumen-pago">Resumen del pago</h3>
                    <p class="completar-pago-help">Revisá el importe antes de continuar.</p>

                    @if($hayCreditoDisponible)
                        <dl class="completar-pago-summary">
                            <div class="completar-pago-summary-row">
                                <dt>Crédito disponible</dt>
                                <dd>{{ $moneda($resumen['credito_disponible']) }}</dd>
                            </div>

                            <div class="completar-pago-summary-row">
                                <dt>Crédito a utilizar</dt>
                                <dd class="{{ $usarCredito ? 'completar-pago-credit-used' : '' }}">
                                    {{ $usarCredito ? '−' : '' }}{{ $moneda($creditoAUtilizar) }}
                                </dd>
                            </div>

                            <div class="completar-pago-summary-row is-final {{ $usarCredito && $resumen['cubre_total'] ? 'is-covered' : '' }}">
                                <dt>Restante a pagar</dt>
                                <dd>{{ $moneda($importeRestante) }}</dd>
                            </div>
                        </dl>

                        <label class="completar-pago-credit-option">
                            <input type="checkbox" wire:model.live="usarCredito">
                            <span>
                                <strong>Usar mi crédito</strong>
                                <span>Se aplicarán primero los créditos que vencen antes.</span>
                            </span>
                        </label>
                    @else
                        <dl class="completar-pago-summary">
                            <div class="completar-pago-summary-row is-final">
                                <dt>Importe a pagar</dt>
                                <dd>{{ $moneda($resumen['diferencia']) }}</dd>
                            </div>
                        </dl>

                        <p class="completar-pago-note">No tenés créditos disponibles para aplicar a esta clase.</p>
                    @endif

                    @if($usarCredito && ! $resumen['cubre_total'])
                        <div class="completar-pago-alert">
                            Se reservarán {{ $moneda($resumen['credito_aplicable']) }} de tu crédito y Mercado Pago cobrará solamente {{ $moneda($resumen['diferencia']) }}.
                        </div>
                    @elseif($usarCredito && $resumen['cubre_total'])
                        <div class="completar-pago-alert is-success">
                            <p><strong>Tu crédito cubre el total de la clase.</strong></p>
                            <p>No será necesario utilizar Mercado Pago.</p>
                        </div>
                    @endif
                </section>

                <footer class="completar-pago-actions">
                    <x-filament::button
                        tag="a"
                        color="gray"
                        outlined
                        icon="heroicon-o-arrow-left"
                        href="{{ \App\Filament\Alumno\Resources\Turnos\TurnoResource::getUrl('index', panel: 'alumno') }}"
                    >
                        Volver a turnos
                    </x-filament::button>

                    <div class="completar-pago-primary-actions">
                        @if(! $usarCredito)
                            <x-filament::button
                                tag="a"
                                color="primary"
                                icon="heroicon-o-credit-card"
                                href="{{ route('mp.pagar', ['turno' => $turno->id]) }}"
                            >
                                Pagar {{ $moneda($resumen['precio_total']) }} con Mercado Pago
                            </x-filament::button>
                        @elseif(! $resumen['cubre_total'])
                            <x-filament::button
                                color="primary"
                                icon="heroicon-o-credit-card"
                                wire:click="continuarConPagoMixto"
                                wire:loading.attr="disabled"
                                wire:target="continuarConPagoMixto"
                            >
                                <span wire:loading.remove wire:target="continuarConPagoMixto">Pagar {{ $moneda($resumen['diferencia']) }} con Mercado Pago</span>
                                <span wire:loading wire:target="continuarConPagoMixto">Reservando crédito...</span>
                            </x-filament::button>
                        @endif

                        @if($resumen['cubre_total'])
                            <x-filament::button
                                color="success"
                                icon="heroicon-o-check-circle"
                                wire:click="confirmarPagoConCredito"
                                wire:loading.attr="disabled"
                                wire:target="confirmarPagoConCredito"
                                :disabled="! $usarCredito"
                            >
                                <span wire:loading.remove wire:target="confirmarPagoConCredito">Confirmar pago con crédito</span>
                                <span wire:loading wire:target="confirmarPagoConCredito">Procesando...</span>
                            </x-filament::button>
                        @endif
                    </div>
                </footer>
            </div>
        </article>
    </div>
    @if ($mostrarModalExito)
        <div
            x-data
            x-init="$nextTick(() => $refs.aceptar.focus())"
            x-trap.inert.noscroll="true"
            role="dialog"
            aria-modal="true"
            aria-labelledby="modal-exito-titulo"
            style="
                position: fixed;
                inset: 0;
                z-index: 9999;
                display: flex;
                align-items: center;
                justify-content: center;
                padding: 20px;
                background: rgba(15, 23, 42, 0.68);
            "
        >
            <div
                style="
                    width: 100%;
                    max-width: 440px;
                    padding: 32px 28px 26px;
                    border-radius: 18px;
                    background: #ffffff;
                    text-align: center;
                    box-shadow: 0 24px 60px rgba(15, 23, 42, 0.35);
                "
            >
                <div
                    aria-hidden="true"
                    style="
                        width: 72px;
                        height: 72px;
                        margin: 0 auto 20px;
                        display: flex;
                        align-items: center;
                        justify-content: center;
                        border-radius: 9999px;
                        background: #dcfce7;
                        color: #16a34a;
                    "
                >
                    <svg
                        xmlns="http://www.w3.org/2000/svg"
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="2.5"
                        style="width: 40px; height: 40px;"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            d="m5 12 4 4L19 6"
                        />
                    </svg>
                </div>

                <h2
                    id="modal-exito-titulo"
                    style="
                        margin: 0;
                        color: #111827;
                        font-size: 22px;
                        font-weight: 800;
                        line-height: 1.3;
                    "
                >
                    ¡Pago realizado con éxito!
                </h2>

                <p
                    style="
                        margin: 14px 0 24px;
                        color: #4b5563;
                        font-size: 15px;
                        line-height: 1.6;
                    "
                >
                    Tu clase quedó confirmada.
                </p>

                <x-filament::button
                    x-ref="aceptar"
                    type="button"
                    wire:click="cerrarModalExito"
                    wire:loading.attr="disabled"
                    wire:target="cerrarModalExito"
                >
                    Aceptar
                </x-filament::button>
            </div>
        </div>
    @endif
</x-filament-panels::page>
