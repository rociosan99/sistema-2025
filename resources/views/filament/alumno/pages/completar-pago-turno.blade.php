<x-filament-panels::page>
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

    <div class="mx-auto w-full max-w-4xl overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm dark:border-white/10 dark:bg-gray-900">
        <header class="flex flex-col gap-5 border-b border-gray-200 px-6 py-6 dark:border-white/10 sm:flex-row sm:items-start sm:justify-between sm:px-8">
            <div>
                <h1 class="text-2xl font-bold tracking-tight text-gray-950 dark:text-white">Completar pago</h1>
                <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">Resumen de tu clase</p>
                <p class="mt-1 text-xs font-medium text-gray-400 dark:text-gray-500">Turno #{{ $turno->id }}</p>
            </div>

            <x-filament::badge color="primary">
                Pendiente de pago
            </x-filament::badge>
        </header>

        <div class="space-y-8 px-6 py-7 sm:px-8 sm:py-8">
            <section aria-labelledby="datos-clase">
                <h2 id="datos-clase" class="text-base font-semibold text-gray-950 dark:text-white">Datos de la clase</h2>

                <dl class="mt-5 grid gap-x-10 gap-y-5 sm:grid-cols-2">
                    <div>
                        <dt class="text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">Profesor</dt>
                        <dd class="mt-1 text-sm font-semibold text-gray-950 dark:text-white">{{ $nombreProfesor }}</dd>
                    </div>

                    <div>
                        <dt class="text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">Materia</dt>
                        <dd class="mt-1 text-sm font-semibold text-gray-950 dark:text-white">{{ $turno->materia?->materia_nombre ?? '-' }}</dd>
                    </div>

                    @if($turno->tema)
                        <div class="sm:col-span-2">
                            <dt class="text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">Tema</dt>
                            <dd class="mt-1 text-sm font-semibold text-gray-950 dark:text-white">{{ $turno->tema->tema_nombre }}</dd>
                        </div>
                    @endif

                    <div>
                        <dt class="text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">Fecha</dt>
                        <dd class="mt-1 text-sm font-semibold text-gray-950 dark:text-white">{{ $fechaTurno }}</dd>
                    </div>

                    <div>
                        <dt class="text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">Horario</dt>
                        <dd class="mt-1 text-sm font-semibold text-gray-950 dark:text-white">{{ $horarioTurno }}</dd>
                    </div>

                    <div>
                        <dt class="text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">Duración</dt>
                        <dd class="mt-1 text-sm font-semibold text-gray-950 dark:text-white">{{ $duracionTurno }}</dd>
                    </div>
                </dl>
            </section>

            <section class="rounded-xl border border-gray-200 bg-gray-50 px-5 py-6 text-center dark:border-white/10 dark:bg-white/5" aria-label="Total de la clase">
                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Total de la clase</p>
                <p class="mt-2 text-3xl font-bold tracking-tight text-gray-950 dark:text-white">{{ $moneda($resumen['precio_total']) }}</p>
            </section>

            <section class="border-t border-gray-200 pt-8 dark:border-white/10" aria-labelledby="resumen-pago">
                <div class="text-center">
                    <h2 id="resumen-pago" class="text-lg font-semibold text-gray-950 dark:text-white">Resumen del pago</h2>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Revisá el importe antes de continuar.</p>
                </div>

                <div class="mx-auto mt-6 max-w-xl">
                    @if($hayCreditoDisponible)
                        <dl class="space-y-4">
                            <div class="flex items-center justify-between gap-6">
                                <dt class="text-sm text-gray-600 dark:text-gray-300">Crédito disponible</dt>
                                <dd class="text-sm font-semibold text-gray-950 dark:text-white">{{ $moneda($resumen['credito_disponible']) }}</dd>
                            </div>

                            <div class="flex items-center justify-between gap-6">
                                <dt class="text-sm text-gray-600 dark:text-gray-300">Crédito a utilizar</dt>
                                <dd class="text-sm font-semibold {{ $usarCredito ? 'text-success-700 dark:text-success-300' : 'text-gray-950 dark:text-white' }}">
                                    {{ $usarCredito ? '−' : '' }}{{ $moneda($creditoAUtilizar) }}
                                </dd>
                            </div>

                            <div class="border-t border-gray-200 pt-4 dark:border-white/10">
                                <div class="flex items-center justify-between gap-6">
                                    <dt class="font-semibold text-gray-950 dark:text-white">Restante a pagar</dt>
                                    <dd class="text-xl font-bold {{ $usarCredito && $resumen['cubre_total'] ? 'text-success-700 dark:text-success-300' : 'text-primary-700 dark:text-primary-300' }}">
                                        {{ $moneda($importeRestante) }}
                                    </dd>
                                </div>
                            </div>
                        </dl>

                        <label class="mt-6 flex cursor-pointer items-start gap-3 rounded-xl border border-gray-200 bg-gray-50 p-4 dark:border-white/10 dark:bg-white/5">
                            <input type="checkbox" wire:model.live="usarCredito" class="mt-0.5 rounded border-gray-300 text-primary-600 focus:ring-primary-600">
                            <span class="min-w-0">
                                <span class="block font-semibold text-gray-950 dark:text-white">Usar mi crédito</span>
                                <span class="mt-0.5 block text-sm text-gray-500 dark:text-gray-400">Se aplicarán primero los créditos que vencen antes.</span>
                            </span>
                        </label>
                    @else
                        <dl>
                            <div class="flex items-center justify-between gap-6">
                                <dt class="font-semibold text-gray-950 dark:text-white">Importe a pagar</dt>
                                <dd class="text-xl font-bold text-primary-700 dark:text-primary-300">{{ $moneda($resumen['diferencia']) }}</dd>
                            </div>
                        </dl>

                        <div class="mt-6 rounded-xl bg-gray-50 p-4 text-sm text-gray-600 dark:bg-white/5 dark:text-gray-300">
                            No tenés créditos disponibles para aplicar a esta clase.
                        </div>
                    @endif

                    @if($usarCredito && ! $resumen['cubre_total'])
                        <div class="mt-5 rounded-xl bg-primary-50 p-4 text-sm text-primary-800 dark:bg-primary-950/30 dark:text-primary-200">
                            Se reservarán {{ $moneda($resumen['credito_aplicable']) }} de tu crédito y Mercado Pago cobrará solamente {{ $moneda($resumen['diferencia']) }}.
                        </div>
                    @elseif($usarCredito && $resumen['cubre_total'])
                        <div class="mt-5 rounded-xl bg-success-50 p-4 text-success-800 dark:bg-success-950/30 dark:text-success-200">
                            <p class="font-semibold">Tu crédito cubre el total de la clase.</p>
                            <p class="mt-1 text-sm">No será necesario utilizar Mercado Pago.</p>
                        </div>
                    @endif
                </div>
            </section>

            <footer class="border-t border-gray-200 pt-7 dark:border-white/10">
                <div class="flex flex-col-reverse items-stretch gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <x-filament::button
                        tag="a"
                        color="gray"
                        outlined
                        icon="heroicon-o-arrow-left"
                        class="w-full sm:w-auto"
                        href="{{ \App\Filament\Alumno\Resources\Turnos\TurnoResource::getUrl('index', panel: 'alumno') }}"
                    >
                        Volver a turnos
                    </x-filament::button>

                    <div class="flex flex-col items-stretch gap-3 sm:flex-row sm:items-center">
                        @if(! $usarCredito)
                            <x-filament::button
                                tag="a"
                                color="primary"
                                icon="heroicon-o-credit-card"
                                class="w-full sm:w-auto"
                                href="{{ route('mp.pagar', ['turno' => $turno->id]) }}"
                            >
                                Pagar {{ $moneda($resumen['precio_total']) }} con Mercado Pago
                            </x-filament::button>
                        @elseif(! $resumen['cubre_total'])
                            <x-filament::button
                                color="primary"
                                icon="heroicon-o-credit-card"
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
                                icon="heroicon-o-check-circle"
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
            </footer>
        </div>
    </div>
</x-filament-panels::page>
