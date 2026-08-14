<x-filament-panels::page>
    @php
        $profesorOriginal = trim(($turno->profesor?->name ?? '') . ' ' . ($turno->profesor?->apellido ?? '')) ?: 'Profesor';
        $importePagado = $turno->pago?->monto;
        $fechaOriginal = $turno->fecha?->format('d/m/Y') ?? '-';
        $horaInicio = substr((string) $turno->hora_inicio, 0, 5);
        $horaFin = substr((string) $turno->hora_fin, 0, 5);
    @endphp

    <div class="mx-auto max-w-4xl space-y-6">
        <x-filament::section heading="Clase suspendida por el profesor">
            <div class="grid gap-4 md:grid-cols-2">
                <div><span class="text-sm text-gray-500">Profesor que suspendió</span><p class="font-semibold">{{ $profesorOriginal }}</p></div>
                <div><span class="text-sm text-gray-500">Materia</span><p class="font-semibold">{{ $turno->materia?->materia_nombre ?? '-' }}</p></div>
                @if($turno->tema)
                    <div><span class="text-sm text-gray-500">Tema</span><p class="font-semibold">{{ $turno->tema->tema_nombre }}</p></div>
                @endif
                <div><span class="text-sm text-gray-500">Fecha y horario originales</span><p class="font-semibold">{{ $fechaOriginal }} · {{ $horaInicio }} - {{ $horaFin }}</p></div>
                <div class="md:col-span-2"><span class="text-sm text-gray-500">Motivo de suspensión</span><p class="font-semibold">{{ $turno->suspension_motivo ?: 'No informado' }}</p></div>
            </div>

            <div class="mt-5 rounded-xl bg-success-50 p-4 text-sm text-success-800 dark:bg-success-950/40 dark:text-success-200">
                <p class="font-semibold">El pago continúa vigente.</p>
                <p>Importe original ya pagado: <strong>${{ number_format((float) $importePagado, 2, ',', '.') }}</strong></p>
            </div>
        </x-filament::section>

        @if($mensajeEstado)
            <x-filament::section>
                <div class="rounded-xl bg-warning-50 p-4 text-warning-800 dark:bg-warning-950/40 dark:text-warning-200">
                    {{ $mensajeEstado }}
                </div>
            </x-filament::section>
        @else
            <x-filament::section heading="Opción 1: reprogramar con el mismo profesor">
                <p class="text-sm text-gray-600 dark:text-gray-300">
                    Elegí otro día y horario disponible con {{ $profesorOriginal }}. El pago seguirá asociado al mismo turno.
                </p>

                <div class="mt-4">
                    @if($propuestaVigente)
                        <x-filament::button color="gray" disabled>
                            Reprogramación bloqueada mientras exista una propuesta
                        </x-filament::button>
                    @else
                        <x-filament::button
                            tag="a"
                            color="gray"
                            href="{{ url('/alumno/reprogramar-turno?turno=' . $turno->id) }}"
                        >
                            Reprogramar con {{ $profesorOriginal }}
                        </x-filament::button>
                    @endif
                </div>
            </x-filament::section>

            <x-filament::section heading="Opción 2: profesor reemplazante">
                @error('reemplazo')
                    <div class="mb-4 rounded-xl bg-danger-50 p-3 text-sm text-danger-700 dark:bg-danger-950/40 dark:text-danger-200">{{ $message }}</div>
                @enderror

                @if($propuestaVigente)
                    @php
                        $profesorPropuesto = trim(($turno->profesorReemplazoPropuesto?->name ?? '') . ' ' . ($turno->profesorReemplazoPropuesto?->apellido ?? '')) ?: 'Profesor';
                    @endphp

                    <div class="rounded-xl border border-primary-200 p-5 dark:border-primary-800">
                        <p class="text-sm font-semibold text-primary-700 dark:text-primary-300">Propuesta pendiente de respuesta</p>
                        <p class="mt-2 text-xl font-bold">{{ $profesorPropuesto }}</p>
                        <p class="mt-1 text-sm text-gray-600 dark:text-gray-300">
                            {{ $turno->reemplazo_fecha?->format('d/m/Y') }} ·
                            {{ substr((string) $turno->reemplazo_hora_inicio, 0, 5) }} -
                            {{ substr((string) $turno->reemplazo_hora_fin, 0, 5) }}
                        </p>
                        <p class="mt-2 text-sm">Vence: <strong>{{ $turno->reemplazo_expires_at?->format('d/m/Y H:i') }}</strong></p>
                        <div class="mt-4">
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
                        <div class="mb-4 rounded-xl bg-warning-50 p-3 text-sm text-warning-800 dark:bg-warning-950/40 dark:text-warning-200">
                            La propuesta anterior venció. Podés solicitar otro reemplazo.
                        </div>
                    @endif

                    @if($candidato)
                        <div class="rounded-xl border border-gray-200 p-5 dark:border-gray-700">
                            <p class="text-sm font-semibold text-success-700 dark:text-success-300">Profesor reemplazante disponible</p>
                            <p class="mt-2 text-xl font-bold">{{ $candidato['profesor_nombre'] }}</p>
                            <p class="mt-1 text-sm text-gray-600 dark:text-gray-300">
                                @if((int) ($candidato['rating_count'] ?? 0) > 0)
                                    {{ number_format((float) $candidato['rating_avg'], 1, ',', '.') }} ★
                                    ({{ (int) $candidato['rating_count'] }} {{ (int) $candidato['rating_count'] === 1 ? 'calificación' : 'calificaciones' }})
                                @else
                                    Sin calificaciones
                                @endif
                            </p>
                            <p class="mt-3 font-semibold">{{ \Carbon\Carbon::parse($candidato['fecha'])->format('d/m/Y') }}</p>
                            <p>{{ substr((string) $candidato['hora_inicio'], 0, 5) }} - {{ substr((string) $candidato['hora_fin'], 0, 5) }}</p>

                            <div class="mt-4">
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
                        <div class="rounded-xl bg-gray-50 p-4 text-gray-700 dark:bg-gray-800 dark:text-gray-200">
                            No encontramos un profesor disponible para el horario original.
                        </div>
                    @endif
                @endif
            </x-filament::section>
        @endif
    </div>
</x-filament-panels::page>
