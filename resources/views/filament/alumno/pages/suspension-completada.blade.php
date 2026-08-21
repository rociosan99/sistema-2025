<x-filament-panels::page>
    @php
        $importeCredito = (float) ($credito?->importe_credito ?? 0);
        $saldoDisponible = (float) ($credito?->saldo_disponible ?? 0);
        $creditoPendiente = $credito?->estado === \App\Models\Credito::ESTADO_ESPERANDO_PAGO;
        $creditoVencido = $credito?->vence_at?->isPast() ?? false;
        $creditoUtilizado = $credito?->estado === \App\Models\Credito::ESTADO_DISPONIBLE
            && $saldoDisponible <= 0
            && $importeCredito > 0;
        $creditoDisponible = $credito?->estado === \App\Models\Credito::ESTADO_DISPONIBLE
            && $importeCredito > 0
            && $saldoDisponible > 0
            && ! $creditoVencido;
        $nombreProfesor = trim(
            ($turno->profesor?->name ?? '') . ' ' . ($turno->profesor?->apellido ?? '')
        );
        $accionPrincipalUrl = $puedeReprogramar
            ? url('/alumno/reprogramar-turno?turno=' . $turno->id)
            : url('/alumno/solicitar-turno');
        $accionPrincipalTexto = $puedeReprogramar
            ? 'Reprogramar clase'
            : 'Reservar otra clase';
    @endphp

    <div class="mx-auto w-full max-w-3xl">
        <x-filament::section>
            <x-slot name="heading">Clase suspendida correctamente</x-slot>

            <x-slot name="description">
                La reserva anterior finalizó. Podés reservar otra clase cuando quieras.
            </x-slot>

            <div class="space-y-10">
                <div class="space-y-5">
                    <h3 class="text-base font-semibold text-gray-950 dark:text-white">Datos de la clase</h3>

                    <dl class="space-y-4">
                    <div class="flex items-baseline gap-2">
                        <dt class="shrink-0 text-sm font-medium text-gray-700 dark:text-gray-200">Materia:</dt>
                        <dd class="min-w-0 text-sm text-gray-950 dark:text-white">
                            {{ $turno->materia?->materia_nombre ?? '-' }}
                        </dd>
                    </div>

                    <div class="flex items-baseline gap-2">
                        <dt class="shrink-0 text-sm font-medium text-gray-700 dark:text-gray-200">Profesor:</dt>
                        <dd class="min-w-0 text-sm text-gray-950 dark:text-white">
                            {{ $nombreProfesor !== '' ? $nombreProfesor : '-' }}
                        </dd>
                    </div>

                    <div class="flex items-baseline gap-2">
                        <dt class="shrink-0 text-sm font-medium text-gray-700 dark:text-gray-200">Fecha original:</dt>
                        <dd class="min-w-0 text-sm text-gray-950 dark:text-white">
                            {{ $turno->fecha?->format('d/m/Y') ?? '-' }}
                        </dd>
                    </div>

                    <div class="flex items-baseline gap-2">
                        <dt class="shrink-0 text-sm font-medium text-gray-700 dark:text-gray-200">Horario original:</dt>
                        <dd class="min-w-0 text-sm text-gray-950 dark:text-white">
                            {{ substr((string) $turno->hora_inicio, 0, 5) }} - {{ substr((string) $turno->hora_fin, 0, 5) }}
                        </dd>
                    </div>
                    </dl>
                </div>

                <div class="space-y-5 border-t border-gray-200 pt-8 dark:border-white/10">
                    <h3 class="text-base font-semibold text-gray-950 dark:text-white">Crédito</h3>

                    @if($creditoDisponible)
                        <div class="space-y-3">
                            <div class="flex items-baseline gap-2">
                                <span class="shrink-0 text-sm font-medium text-gray-700 dark:text-gray-200">Crédito generado:</span>
                                <span class="text-sm font-bold text-success-600 dark:text-success-400">
                                    ${{ number_format($importeCredito, 2, ',', '.') }}
                                </span>
                            </div>

                            @if($saldoDisponible !== $importeCredito)
                                <div class="flex items-baseline gap-2">
                                    <span class="shrink-0 text-sm font-medium text-gray-700 dark:text-gray-200">Saldo disponible:</span>
                                    <span class="text-sm font-semibold text-gray-950 dark:text-white">
                                        ${{ number_format($saldoDisponible, 2, ',', '.') }}
                                    </span>
                                </div>
                            @endif

                            @if($credito->vence_at)
                                <div class="flex items-baseline gap-2">
                                    <span class="shrink-0 text-sm font-medium text-gray-700 dark:text-gray-200">Disponible hasta:</span>
                                    <span class="text-sm font-semibold text-gray-950 dark:text-white">
                                        {{ $credito->vence_at->format('d/m/Y') }}
                                    </span>
                                </div>
                            @endif

                            <p class="rounded-lg bg-success-50 px-4 py-3 text-sm text-success-700 dark:bg-success-400/10 dark:text-success-300">
                                Podés utilizar este crédito para pagar una próxima clase.
                            </p>
                        </div>
                    @elseif($creditoPendiente)
                        <p class="rounded-lg bg-warning-50 px-4 py-3 text-sm text-warning-700 dark:bg-warning-400/10 dark:text-warning-300">
                            El crédito todavía no fue acreditado porque el pago asociado continúa pendiente.
                        </p>
                    @elseif($creditoVencido && $importeCredito > 0)
                        <div class="space-y-2">
                            <p class="text-sm text-gray-700 dark:text-gray-300">
                                Se generó un crédito de <strong>${{ number_format($importeCredito, 2, ',', '.') }}</strong>.
                            </p>
                            <p class="rounded-lg bg-gray-50 px-4 py-3 text-sm text-gray-600 dark:bg-white/5 dark:text-gray-300">
                                Este crédito venció el {{ $credito->vence_at->format('d/m/Y') }}.
                            </p>
                        </div>
                    @elseif($creditoUtilizado)
                        <div class="space-y-2">
                            <p class="text-sm text-gray-700 dark:text-gray-300">
                                Se generó un crédito de <strong>${{ number_format($importeCredito, 2, ',', '.') }}</strong>.
                            </p>
                            <p class="rounded-lg bg-gray-50 px-4 py-3 text-sm text-gray-600 dark:bg-white/5 dark:text-gray-300">
                                Este crédito ya fue utilizado.
                            </p>
                        </div>
                    @else
                        <p class="rounded-lg bg-gray-50 px-4 py-3 text-sm text-gray-600 dark:bg-white/5 dark:text-gray-300">
                            No se generó crédito para esta suspensión.
                        </p>
                    @endif
                </div>

                <div class="flex flex-col gap-3 border-t border-gray-200 pt-8 sm:flex-row sm:items-center sm:gap-4 dark:border-white/10">
                    <x-filament::button
                        tag="a"
                        :href="\App\Filament\Alumno\Resources\Turnos\TurnoResource::getUrl('index', panel: 'alumno')"
                        color="gray"
                        outlined
                    >
                        Volver a mis turnos
                    </x-filament::button>

                    <x-filament::button
                        tag="a"
                        :href="$accionPrincipalUrl"
                        :color="$puedeReprogramar ? 'success' : 'primary'"
                    >
                        {{ $accionPrincipalTexto }}
                    </x-filament::button>
                </div>
            </div>
        </x-filament::section>
    </div>
</x-filament-panels::page>
