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
</x-filament-panels::page>
