<x-filament-panels::page>
    @php
        $alumno = trim(($turno->alumno?->name ?? '') . ' ' . ($turno->alumno?->apellido ?? '')) ?: 'Alumno';
    @endphp

    <div class="mx-auto max-w-3xl">
        <x-filament::section
            heading="Propuesta de reemplazo"
            description="Revisá los datos de la clase antes de responder."
        >
            <div class="grid gap-4 md:grid-cols-2">
                <div>
                    <p class="text-sm text-gray-500">Alumno</p>
                    <p class="font-semibold">{{ $alumno }}</p>
                </div>
                <div>
                    <p class="text-sm text-gray-500">Calificación del alumno</p>
                    <p class="font-semibold">
                        @if($calificacionesCantidad > 0)
                            {{ number_format((float) $calificacionPromedio, 1, ',', '.') }} ★
                            ({{ $calificacionesCantidad }} {{ $calificacionesCantidad === 1 ? 'calificación' : 'calificaciones' }})
                        @else
                            Sin calificaciones
                        @endif
                    </p>
                </div>
                <div>
                    <p class="text-sm text-gray-500">Materia</p>
                    <p class="font-semibold">{{ $turno->materia?->materia_nombre ?? '-' }}</p>
                </div>
                @if($turno->tema)
                    <div>
                        <p class="text-sm text-gray-500">Tema</p>
                        <p class="font-semibold">{{ $turno->tema->tema_nombre }}</p>
                    </div>
                @endif
                <div>
                    <p class="text-sm text-gray-500">Fecha</p>
                    <p class="font-semibold">{{ $turno->reemplazo_fecha?->format('d/m/Y') ?? '-' }}</p>
                </div>
                <div>
                    <p class="text-sm text-gray-500">Horario</p>
                    <p class="font-semibold">
                        {{ substr((string) $turno->reemplazo_hora_inicio, 0, 5) }} -
                        {{ substr((string) $turno->reemplazo_hora_fin, 0, 5) }}
                    </p>
                </div>
                <div class="md:col-span-2">
                    <p class="text-sm text-gray-500">Vencimiento</p>
                    <p class="font-semibold">{{ $turno->reemplazo_expires_at?->format('d/m/Y H:i') ?? '-' }}</p>
                </div>
            </div>

            @if($mensajeEstado)
                <div class="mt-6 rounded-xl bg-warning-50 p-4 text-warning-800 dark:bg-warning-950/40 dark:text-warning-200">
                    {{ $mensajeEstado }}
                </div>
            @endif

            @if($propuestaVigente)
                <div class="mt-6 flex flex-wrap justify-end gap-3">
                    <x-filament::button
                        color="danger"
                        outlined
                        wire:click="rechazar"
                        wire:loading.attr="disabled"
                        wire:target="aceptar,rechazar"
                        wire:confirm="¿Querés rechazar esta propuesta de reemplazo?"
                    >
                        Rechazar
                    </x-filament::button>

                    <x-filament::button
                        wire:click="aceptar"
                        wire:loading.attr="disabled"
                        wire:target="aceptar,rechazar"
                        wire:confirm="¿Querés aceptar esta propuesta de reemplazo?"
                    >
                        Aceptar
                    </x-filament::button>
                </div>
            @endif
        </x-filament::section>
    </div>

    @if($mostrarAlertaCorregible)
        <div
            role="dialog"
            aria-modal="true"
            aria-labelledby="alerta-corregible-titulo"
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
                        background: #fef3c7;
                        color: #d97706;
                        font-size: 38px;
                        font-weight: 800;
                    "
                >
                    ⚠
                </div>

                <h2
                    id="alerta-corregible-titulo"
                    style="
                        margin: 0;
                        color: #111827;
                        font-size: 22px;
                        font-weight: 800;
                        line-height: 1.3;
                    "
                >
                    Atención
                </h2>

                <p
                    style="
                        margin: 14px 0 24px;
                        color: #4b5563;
                        font-size: 15px;
                        line-height: 1.6;
                        overflow-wrap: anywhere;
                    "
                >
                    {{ $mensajeAlertaCorregible }}
                </p>

                <x-filament::button
                    type="button"
                    color="warning"
                    wire:click="cerrarAlertaCorregible"
                    wire:loading.attr="disabled"
                    wire:target="cerrarAlertaCorregible"
                >
                    Entendido
                </x-filament::button>
            </div>
        </div>
    @endif

    @if($mostrarModalExito)
        <div
            role="dialog"
            aria-modal="true"
            aria-labelledby="propuesta-aceptada-titulo"
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
                        font-size: 40px;
                        font-weight: 800;
                    "
                >
                    ✓
                </div>

                <h2
                    id="propuesta-aceptada-titulo"
                    style="
                        margin: 0;
                        color: #111827;
                        font-size: 22px;
                        font-weight: 800;
                        line-height: 1.3;
                    "
                >
                    Propuesta aceptada
                </h2>

                <p
                    style="
                        margin: 14px 0 24px;
                        color: #4b5563;
                        font-size: 15px;
                        line-height: 1.6;
                    "
                >
                    La clase ya fue incorporada a tus turnos confirmados.
                </p>

                <x-filament::button
                    type="button"
                    color="success"
                    wire:click="continuarDespuesDeAceptar"
                    wire:loading.attr="disabled"
                    wire:target="continuarDespuesDeAceptar"
                >
                    Entendido
                </x-filament::button>
            </div>
        </div>
    @endif
</x-filament-panels::page>
