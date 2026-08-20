<x-filament-panels::page>
    @php
        $moneda = static fn (string|float|int $importe): string => '$' . number_format((float) $importe, 2, ',', '.');
    @endphp

    <div class="mx-auto w-full max-w-3xl space-y-6">
        <x-filament::section heading="Completar pago" description="Revisá el importe de la clase y elegí cómo continuar.">
            <div class="grid gap-4 sm:grid-cols-2">
                <div class="rounded-xl bg-gray-50 p-4 dark:bg-white/5">
                    <p class="text-sm text-gray-500 dark:text-gray-400">Precio total de la clase</p>
                    <p class="mt-1 text-xl font-bold text-gray-950 dark:text-white">{{ $moneda($resumen['precio_total']) }}</p>
                </div>
                <div class="rounded-xl bg-gray-50 p-4 dark:bg-white/5">
                    <p class="text-sm text-gray-500 dark:text-gray-400">Crédito disponible</p>
                    <p class="mt-1 text-xl font-bold text-gray-950 dark:text-white">{{ $moneda($resumen['credito_disponible']) }}</p>
                </div>
                <div>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Crédito que se aplicaría</p>
                    <p class="font-semibold text-gray-950 dark:text-white">{{ $moneda($resumen['credito_aplicable']) }}</p>
                </div>
                <div>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Diferencia</p>
                    <p class="font-semibold text-gray-950 dark:text-white">{{ $moneda($resumen['diferencia']) }}</p>
                </div>
            </div>

            <div class="mt-6 border-t border-gray-200 pt-5 dark:border-white/10">
                @if((float) $resumen['credito_disponible'] > 0)
                    <label class="flex items-start gap-3">
                        <input type="checkbox" wire:model.live="usarCredito" class="mt-0.5 rounded border-gray-300 text-primary-600 focus:ring-primary-600">
                        <span>
                            <span class="block font-semibold text-gray-950 dark:text-white">Usar mi crédito</span>
                            <span class="block text-sm text-gray-500 dark:text-gray-400">Se aplicarán primero los créditos que vencen antes.</span>
                        </span>
                    </label>
                @else
                    <p class="text-sm text-gray-600 dark:text-gray-300">
                        No tenés créditos disponibles para aplicar a esta clase.
                    </p>
                @endif
            </div>

            @if($usarCredito && ! $resumen['cubre_total'])
                <div class="mt-5 rounded-xl bg-primary-50 p-4 text-sm text-primary-800 dark:bg-primary-950/40 dark:text-primary-200">
                    Se reservarán {{ $moneda($resumen['credito_aplicable']) }} de tu crédito y Mercado Pago cobrará solamente {{ $moneda($resumen['diferencia']) }}.
                </div>
            @endif

            <div class="mt-6 flex flex-wrap items-center justify-between gap-3">
                <x-filament::button tag="a" color="gray" href="{{ \App\Filament\Alumno\Resources\Turnos\TurnoResource::getUrl('index', panel: 'alumno') }}">
                    Volver a turnos
                </x-filament::button>

                <div class="flex flex-wrap items-center justify-end gap-3">
                    @if(! $usarCredito)
                        <x-filament::button tag="a" color="gray" href="{{ route('mp.pagar', ['turno' => $turno->id]) }}">
                            Pagar total con Mercado Pago
                        </x-filament::button>
                    @elseif(! $resumen['cubre_total'])
                        <x-filament::button
                            wire:click="continuarConPagoMixto"
                            wire:loading.attr="disabled"
                            wire:target="continuarConPagoMixto"
                        >
                            <span wire:loading.remove wire:target="continuarConPagoMixto">
                                Continuar y pagar {{ $moneda($resumen['diferencia']) }} con Mercado Pago
                            </span>
                            <span wire:loading wire:target="continuarConPagoMixto">Reservando crédito...</span>
                        </x-filament::button>
                    @endif

                    @if($resumen['cubre_total'])
                        <x-filament::button
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
