<x-filament-panels::page>
    @php
        $profesorOriginal = trim(($turno->profesor?->name ?? '') . ' ' . ($turno->profesor?->apellido ?? '')) ?: 'Profesor';
        $importePagado = $turno->pago?->monto;
        $fechaOriginal = $turno->fecha?->format('d/m/Y') ?? '-';
        $horaInicio = substr((string) $turno->hora_inicio, 0, 5);
        $horaFin = substr((string) $turno->hora_fin, 0, 5);
    @endphp


    <style>
        .resolver-suspension { width: min(100%, 1100px); margin-inline: auto; display: grid; gap: 20px; }
        .resolver-suspension .rs-card { min-width: 0; padding: 22px; background: white; border: 1px solid var(--gray-200); border-radius: 16px; box-shadow: 0 1px 3px rgb(0 0 0 / .04); }
        .resolver-suspension h2, .resolver-suspension h3, .resolver-suspension p, .resolver-suspension dl, .resolver-suspension dd { margin: 0; }
        .resolver-suspension h2, .resolver-suspension h3 { color: var(--gray-950); font-weight: 650; line-height: 1.4; }
        .resolver-suspension h2 { font-size: 1.125rem; }
        .resolver-suspension h3 { font-size: 1rem; }
        .resolver-suspension .rs-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 16px 24px; }
        .resolver-suspension .rs-summary { margin-top: 18px; }
        .resolver-suspension dt, .resolver-suspension .rs-label { color: var(--gray-500); font-size: .8125rem; line-height: 1.5; }
        .resolver-suspension dd { margin-top: 4px; color: var(--gray-950); font-size: .9375rem; font-weight: 550; overflow-wrap: anywhere; }
        .resolver-suspension .rs-wide { grid-column: 1 / -1; }
        .resolver-suspension .rs-reason { padding-top: 14px; border-top: 1px solid var(--gray-200); }
        .resolver-suspension .rs-payment { display: flex; align-items: center; justify-content: space-between; gap: 20px; padding: 18px 22px; border: 1px solid var(--success-200); border-radius: 14px; background: var(--success-50); color: var(--success-800); }
        .resolver-suspension .rs-payment-heading { display: flex; align-items: center; gap: 10px; }
        .resolver-suspension .rs-payment-heading svg { width: 22px; height: 22px; flex-shrink: 0; }
        .resolver-suspension .rs-payment h2 { color: inherit; font-size: 1rem; }
        .resolver-suspension .rs-payment p { margin-top: 5px; font-size: .875rem; }
        .resolver-suspension .rs-amount { text-align: right; flex-shrink: 0; }
        .resolver-suspension .rs-amount dt, .resolver-suspension .rs-amount dd { color: inherit; }
        .resolver-suspension .rs-amount dd { font-size: 1.375rem; font-weight: 700; }
        .resolver-suspension .rs-help { margin-top: 6px; color: var(--gray-500); font-size: .875rem; line-height: 1.6; }
        .resolver-suspension .rs-options { margin-top: 16px; align-items: start; }
        .resolver-suspension .rs-option-label { display: block; margin-bottom: 8px; color: var(--primary-700); font-size: .75rem; font-weight: 650; text-transform: uppercase; letter-spacing: .04em; }
        .resolver-suspension .rs-actions { margin-top: 18px; }
        .resolver-suspension .rs-actions .fi-btn { max-width: 100%; white-space: normal; }
        .resolver-suspension .rs-notice { padding: 12px 14px; border-radius: 10px; background: var(--gray-50); color: var(--gray-600); font-size: .875rem; line-height: 1.5; }
        .resolver-suspension .rs-warning { background: var(--warning-50); color: var(--warning-800); }
        .resolver-suspension .rs-error { background: var(--danger-50); color: var(--danger-700); }
        .resolver-suspension .rs-profile { margin-top: 16px; padding-top: 16px; border-top: 1px solid var(--gray-200); }
        .resolver-suspension .rs-profile-header { display: flex; align-items: start; justify-content: space-between; gap: 14px; margin: 6px 0 16px; }
        .resolver-suspension .rs-name { color: var(--gray-950); font-weight: 650; font-size: 1.0625rem; overflow-wrap: anywhere; }
        .resolver-suspension .rs-rating { flex-shrink: 0; text-align: right; color: var(--gray-700); font-size: .9375rem; }
        .resolver-suspension .rs-rating small { display: block; color: var(--gray-500); font-size: .75rem; }
        .resolver-suspension .rs-expiry { margin-top: 12px; color: var(--gray-500); font-size: .8125rem; }
        .resolver-suspension .rs-card > .rs-notice { margin-top: 14px; }
        .dark .resolver-suspension .rs-card { background: var(--gray-900); border-color: var(--gray-700); }
        .dark .resolver-suspension h2, .dark .resolver-suspension h3, .dark .resolver-suspension dd, .dark .resolver-suspension .rs-name { color: var(--gray-100); }
        .dark .resolver-suspension dt, .dark .resolver-suspension .rs-label, .dark .resolver-suspension .rs-help, .dark .resolver-suspension .rs-expiry, .dark .resolver-suspension .rs-rating small { color: var(--gray-400); }
        .dark .resolver-suspension .rs-reason, .dark .resolver-suspension .rs-profile { border-color: var(--gray-700); }
        .dark .resolver-suspension .rs-payment { background: color-mix(in srgb, var(--success-500) 10%, var(--gray-900)); border-color: color-mix(in srgb, var(--success-500) 25%, var(--gray-900)); color: var(--success-200); }
        .dark .resolver-suspension .rs-payment h2, .dark .resolver-suspension .rs-amount dt, .dark .resolver-suspension .rs-amount dd { color: inherit; }
        .dark .resolver-suspension .rs-option-label { color: var(--primary-300); }
        .dark .resolver-suspension .rs-rating { color: var(--gray-200); }
        .dark .resolver-suspension .rs-notice { background: var(--gray-800); color: var(--gray-200); }
        .dark .resolver-suspension .rs-warning { background: color-mix(in srgb, var(--warning-500) 10%, var(--gray-900)); color: var(--warning-200); }
        .dark .resolver-suspension .rs-error { background: color-mix(in srgb, var(--danger-500) 10%, var(--gray-900)); color: var(--danger-200); }
        @media (max-width: 767px) { .resolver-suspension .rs-options { grid-template-columns: minmax(0, 1fr); } }
        @media (max-width: 479px) {
            .resolver-suspension .rs-grid { grid-template-columns: minmax(0, 1fr); }
            .resolver-suspension .rs-card { padding: 18px; }
            .resolver-suspension .rs-payment { flex-direction: column; align-items: flex-start; padding: 18px; gap: 12px; }
            .resolver-suspension .rs-amount { text-align: left; }
            .resolver-suspension .rs-profile-header { flex-wrap: wrap; }
            .resolver-suspension .rs-rating { text-align: left; }
        }
    </style>

    <div class="resolver-suspension">
        <section class="rs-card" aria-labelledby="rs-clase">
            <h2 id="rs-clase">Clase suspendida</h2>
            <dl class="rs-grid rs-summary">
                <div><dt>Profesor</dt><dd>{{ $profesorOriginal }}</dd></div>
                <div><dt>Materia</dt><dd>{{ $turno->materia?->materia_nombre ?? '-' }}</dd></div>
                <div><dt>Fecha</dt><dd>{{ $fechaOriginal }}</dd></div>
                <div><dt>Horario</dt><dd>{{ $horaInicio }} - {{ $horaFin }}</dd></div>
                @if($turno->tema)
                    <div class="rs-wide"><dt>Tema</dt><dd>{{ $turno->tema->tema_nombre }}</dd></div>
                @endif
                <div class="rs-wide rs-reason"><dt>Motivo de suspensión</dt><dd>{{ $turno->suspension_motivo ?: 'No informado' }}</dd></div>
            </dl>
        </section>
        <section class="rs-payment" aria-labelledby="rs-pago">
            <div>
                <div class="rs-payment-heading">
                    <x-heroicon-o-check-circle aria-hidden="true" />
                    <h2 id="rs-pago">Tu pago continúa vigente</h2>
                </div>
                <p>No necesitás volver a pagar esta clase.</p>
            </div>
            <dl class="rs-amount"><dt>Importe original pagado</dt><dd>${{ number_format((float) $importePagado, 2, ',', '.') }}</dd></dl>
        </section>

        @if($mensajeEstado)
            <section>
                <div class="rs-notice rs-warning">
                    {{ $mensajeEstado }}
                </div>
            </section>
        @else
            <section aria-labelledby="rs-alternativas">
                <h2 id="rs-alternativas">¿Cómo querés resolver la suspensión?</h2>
                <p class="rs-help">Elegí una de las alternativas disponibles para continuar con tu clase.</p>
                <div class="rs-grid rs-options">
                    <section class="rs-card" aria-labelledby="rs-opcion-1">
                        <span class="rs-option-label">Opción 1</span>
                        <h3 id="rs-opcion-1">Reprogramar con el mismo profesor</h3>
                        <p class="rs-help">
                            Elegí otro día y horario disponible con {{ $profesorOriginal }}.
                        </p>

                        <div class="rs-actions">
                            @if($propuestaVigente)
                                <x-filament::button color="gray" disabled>
                                    Reprogramación bloqueada mientras exista una propuesta
                                </x-filament::button>
                            @else
                                <x-filament::button
                                    tag="a"
                                    color="primary"
                                    href="{{ url('/alumno/reprogramar-turno?turno=' . $turno->id) }}"
                                >
                                    Reprogramar con {{ $profesorOriginal }}
                                </x-filament::button>
                            @endif
                        </div>
                    </section>

                    <section class="rs-card" aria-labelledby="rs-opcion-2">
                        <span class="rs-option-label">Opción 2</span>
                        <h3 id="rs-opcion-2">Profesor reemplazante</h3>
                        @error('reemplazo')
                            <div class="rs-notice rs-error">{{ $message }}</div>
                        @enderror

                        @if($propuestaVigente)
                            @php
                                $profesorPropuesto = trim(($turno->profesorReemplazoPropuesto?->name ?? '') . ' ' . ($turno->profesorReemplazoPropuesto?->apellido ?? '')) ?: 'Profesor';
                            @endphp

                            <div class="rs-profile">
                                <p class="rs-label">Propuesta pendiente de respuesta</p>
                                <p class="rs-name">{{ $profesorPropuesto }}</p>
                                <p class="rs-help">
                                    {{ $turno->reemplazo_fecha?->format('d/m/Y') }} ·
                                    {{ substr((string) $turno->reemplazo_hora_inicio, 0, 5) }} -
                                    {{ substr((string) $turno->reemplazo_hora_fin, 0, 5) }}
                                </p>
                                <p class="rs-expiry">Vence: <strong>{{ $turno->reemplazo_expires_at?->format('d/m/Y H:i') }}</strong></p>
                                <div class="rs-actions">
                                    <x-filament::button
                                        color="danger"
                                        outlined
                                        wire:click="cancelarPropuesta"
                                        wire:loading.attr="disabled"
                                        wire:target="cancelarPropuesta"
                                    >
                                        Cancelar propuesta
                                    </x-filament::button>
                                </div>
                            </div>
                        @else
                            @if($propuestaAnteriorVencida)
                                <div class="rs-notice rs-warning">
                                    La propuesta anterior venció. Podés solicitar otro reemplazo.
                                </div>
                            @endif

                            @if($candidato)
                                <div class="rs-profile">
                                    <p class="rs-label">Profesor reemplazante disponible</p>
                                    <div class="rs-profile-header">
                                    <p class="rs-name">{{ $candidato['profesor_nombre'] }}</p>
                                    <p class="rs-rating">
                                        @if((int) ($candidato['rating_count'] ?? 0) > 0)
                                            {{ number_format((float) $candidato['rating_avg'], 1, ',', '.') }} ★
                                            <small>{{ (int) $candidato['rating_count'] }} {{ (int) $candidato['rating_count'] === 1 ? 'calificación' : 'calificaciones' }}</small>
                                        @else
                                            Sin calificaciones
                                        @endif
                                    </p>
                                    </div>
                                    <dl class="rs-grid">
                                        <div><dt>Fecha</dt><dd>{{ \Carbon\Carbon::parse($candidato['fecha'])->format('d/m/Y') }}</dd></div>
                                        <div><dt>Horario</dt><dd>{{ substr((string) $candidato['hora_inicio'], 0, 5) }} - {{ substr((string) $candidato['hora_fin'], 0, 5) }}</dd></div>
                                    </dl>

                                    <div class="rs-actions">
                                        <x-filament::button
                                            wire:click="solicitarReemplazo"
                                            wire:loading.attr="disabled"
                                            wire:target="solicitarReemplazo"
                                        >
                                            Solicitar reemplazo
                                        </x-filament::button>
                                    </div>
                                </div>
                            @else
                                <div class="rs-notice">
                                    No encontramos un profesor disponible para el horario original.
                                </div>
                            @endif
                        @endif
                    </section>
                </div>
            </section>
        @endif
    </div>
</x-filament-panels::page>
