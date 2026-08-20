<x-filament-panels::page>
    @php
        $moneda = static fn (string|float|int $importe): string => '$' . number_format((float) $importe, 2, ',', '.');
        $hayCreditoDisponible = (float) $resumen['credito_disponible'] > 0;
        $nombreProfesor = trim(($turno->profesor?->name ?? '') . ' ' . ($turno->profesor?->apellido ?? '')) ?: '-';
        $fechaTurno = $turno->fecha?->format('d/m/Y') ?? '-';
        $horarioTurno = substr((string) $turno->hora_inicio, 0, 5) . ' - ' . substr((string) $turno->hora_fin, 0, 5);
    @endphp

    <div class="mx-auto w-full max-w-4xl">
        <x-filament::section>
            <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                <div>
                    <h1 class="text-xl font-semibold text-gray-950 dark:text-white">Completar pago</h1>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Turno #{{ $turno->id }}</p>
                </div>

                <x-filament::badge color="primary">
                    Pendiente de pago
                </x-filament::badge>
            </div>

            <div class="mt-10 grid gap-x-10 gap-y-5 sm:grid-cols-2">
                <div class="flex flex-wrap items-baseline gap-x-2 gap-y-1">
                    <span class="text-sm font-semibold text-gray-700 dark:text-gray-200">Materia:</span>
                    <span class="text-sm text-gray-600 dark:text-gray-300">{{ $turno->materia?->materia_nombre ?? '-' }}</span>
                </div>

                <div class="flex flex-wrap items-baseline gap-x-2 gap-y-1">
                    <span class="text-sm font-semibold text-gray-700 dark:text-gray-200">Profesor:</span>
                    <span class="text-sm text-gray-600 dark:text-gray-300">{{ $nombreProfesor }}</span>
                </div>

                <div class="flex flex-wrap items-baseline gap-x-2 gap-y-1">
                    <span class="text-sm font-semibold text-gray-700 dark:text-gray-200">Fecha:</span>
                    <span class="text-sm text-gray-600 dark:text-gray-300">{{ $fechaTurno }}</span>
                </div>

                <div class="flex flex-wrap items-baseline gap-x-2 gap-y-1">
                    <span class="text-sm font-semibold text-gray-700 dark:text-gray-200">Horario:</span>
                    <span class="text-sm text-gray-600 dark:text-gray-300">{{ $horarioTurno }}</span>
                </div>
            </div>

            <div class="my-10 border-t border-gray-200 dark:border-white/10"></div>

            <div>
                <h2 class="text-base font-semibold text-gray-950 dark:text-white">Resumen del pago</h2>

                <dl class="mt-7 space-y-5">
                    @if(! $hayCreditoDisponible)
                        <div class="flex items-center justify-between gap-6">
                            <dt class="font-semibold text-gray-950 dark:text-white">Importe a pagar</dt>
                            <dd class="text-xl font-bold text-primary-700 dark:text-primary-300">{{ $moneda($resumen['diferencia']) }}</dd>
                        </div>
                    @else
                        <div class="flex items-center justify-between gap-6">
                            <dt class="text-sm text-gray-600 dark:text-gray-300">Precio de la clase</dt>
                            <dd class="text-lg font-semibold text-gray-950 dark:text-white">{{ $moneda($resumen['precio_total']) }}</dd>
                        </div>

                        @if($usarCredito)
                            <div class="flex items-center justify-between gap-6">
                                <dt class="text-sm text-gray-600 dark:text-gray-300">Crédito aplicado</dt>
                                <dd class="text-lg font-semibold text-success-700 dark:text-success-300">−{{ $moneda($resumen['credito_aplicable']) }}</dd>
                            </div>

                            <div class="border-t border-gray-200 pt-4 dark:border-white/10">
                                <div class="flex items-center justify-between gap-6">
                                    <dt class="font-semibold text-gray-950 dark:text-white">Importe a pagar</dt>
                                    <dd class="text-xl font-bold {{ $resumen['cubre_total'] ? 'text-success-700 dark:text-success-300' : 'text-primary-700 dark:text-primary-300' }}">
                                        {{ $moneda($resumen['diferencia']) }}
                                    </dd>
                                </div>
                            </div>
                        @else
                            <div class="flex items-center justify-between gap-6">
                                <dt class="text-sm text-gray-600 dark:text-gray-300">Crédito disponible</dt>
                                <dd class="text-lg font-semibold text-success-700 dark:text-success-300">{{ $moneda($resumen['credito_disponible']) }}</dd>
                            </div>
                        @endif
                    @endif
                </dl>
            </div>

            <div class="mt-10">
                @if($hayCreditoDisponible)
                    <label class="flex cursor-pointer items-start gap-3 rounded-xl border border-gray-200 bg-gray-50 p-4 dark:border-white/10 dark:bg-white/5">
                        <input type="checkbox" wire:model.live="usarCredito" class="mt-0.5 rounded border-gray-300 text-primary-600 focus:ring-primary-600">
                        <span class="min-w-0">
                            <span class="block font-semibold text-gray-950 dark:text-white">Usar mi crédito</span>
                            <span class="mt-0.5 block text-sm text-gray-500 dark:text-gray-400">Se aplicarán primero los créditos que vencen antes.</span>
                        </span>
                    </label>
                @else
                    <div class="rounded-xl bg-gray-50 p-4 text-sm text-gray-600 dark:bg-white/5 dark:text-gray-300">
                        No tenés créditos disponibles para aplicar a esta clase.
                    </div>
                @endif
            </div>

            @if($usarCredito && ! $resumen['cubre_total'])
                <div class="mt-6 rounded-xl bg-primary-50 p-4 text-sm text-primary-800 dark:bg-primary-950/30 dark:text-primary-200">
                    Se reservarán {{ $moneda($resumen['credito_aplicable']) }} de tu crédito y Mercado Pago cobrará solamente {{ $moneda($resumen['diferencia']) }}.
                </div>
            @elseif($usarCredito && $resumen['cubre_total'])
                <div class="mt-6 rounded-xl bg-success-50 p-4 text-success-800 dark:bg-success-950/30 dark:text-success-200">
                    <p class="font-semibold">Tu crédito cubre el total de la clase.</p>
                    <p class="mt-1 text-sm">No será necesario utilizar Mercado Pago.</p>
                </div>
            @endif

            <div class="mt-10 border-t border-gray-200 pt-7 dark:border-white/10">
                <div class="flex flex-col-reverse items-stretch gap-3 sm:flex-row sm:items-center sm:justify-end">
                    <x-filament::button
                        tag="a"
                        color="gray"
                        outlined
                        class="w-full sm:w-auto"
                        href="{{ \App\Filament\Alumno\Resources\Turnos\TurnoResource::getUrl('index', panel: 'alumno') }}"
                    >
                        Volver a turnos
                    </x-filament::button>

                    @if(! $usarCredito)
                        <x-filament::button
                            tag="a"
                            color="primary"
                            class="w-full sm:w-auto"
                            href="{{ route('mp.pagar', ['turno' => $turno->id]) }}"
                        >
                            Pagar {{ $moneda($resumen['precio_total']) }} con Mercado Pago
                        </x-filament::button>
                    @elseif(! $resumen['cubre_total'])
                        <x-filament::button
                            color="primary"
                            class="w-full sm:w-auto"
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
                            class="w-full sm:w-auto"
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
            </div>
        </x-filament::section>
    </div>
</x-filament-panels::page>
