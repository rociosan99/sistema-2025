<x-filament-panels::page>
    @php
        $profesor = trim(($turno->profesor?->name ?? '') . ' ' . ($turno->profesor?->apellido ?? '')) ?: 'Profesor';
        $fecha = $turno->fecha?->format('d/m/Y') ?? '-';
        $horaInicio = substr((string) $turno->hora_inicio, 0, 5);
        $horaFin = substr((string) $turno->hora_fin, 0, 5);
    @endphp

    <div class="mx-auto w-full max-w-4xl">
        <x-filament::section>
            <x-slot name="heading">
                Detalle del turno
            </x-slot>

            <x-slot name="description">
                Turno #{{ $turno->id }}
            </x-slot>

            <div class="space-y-8">
                <div class="flex justify-end">
                    <x-filament::badge color="primary">
                        {{ $this->estadoVisible() }}
                    </x-filament::badge>
                </div>

                <div class="grid gap-x-8 gap-y-6 md:grid-cols-2">
                    <div>
                        <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Profesor actual</p>
                        <p class="mt-1 font-semibold text-gray-950 dark:text-white">{{ $profesor }}</p>
                    </div>

                    <div>
                        <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Estado</p>
                        <p class="mt-1 font-semibold text-gray-950 dark:text-white">{{ $this->estadoVisible() }}</p>
                    </div>

                    <div>
                        <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Materia</p>
                        <p class="mt-1 font-semibold text-gray-950 dark:text-white">{{ $turno->materia?->materia_nombre ?? '-' }}</p>
                    </div>

                    @if($turno->tema)
                        <div>
                            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Tema</p>
                            <p class="mt-1 font-semibold text-gray-950 dark:text-white">{{ $turno->tema->tema_nombre }}</p>
                        </div>
                    @endif

                    <div>
                        <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Fecha</p>
                        <p class="mt-1 font-semibold text-gray-950 dark:text-white">{{ $fecha }}</p>
                    </div>

                    <div>
                        <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Horario</p>
                        <p class="mt-1 font-semibold text-gray-950 dark:text-white">{{ $horaInicio }} - {{ $horaFin }}</p>
                    </div>
                </div>

                <div class="border-t border-gray-200 pt-6 dark:border-white/10">
                    <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
                        <x-filament::button
                            tag="a"
                            color="gray"
                            outlined
                            href="{{ \App\Filament\Alumno\Resources\Turnos\TurnoResource::getUrl('index', panel: 'alumno') }}"
                        >
                            Volver a mis turnos
                        </x-filament::button>

                        @if($this->puedeVerEnlace())
                            <x-filament::button
                                tag="a"
                                color="primary"
                                href="{{ $turno->enlace_clase }}"
                                target="_blank"
                                rel="noopener noreferrer"
                            >
                                Enlace de clase
                            </x-filament::button>
                        @endif
                    </div>
                </div>
            </div>
        </x-filament::section>
    </div>
</x-filament-panels::page>
