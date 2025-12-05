<x-filament-panels::page>
    <div class="space-y-6">

        {{-- Encabezado --}}
        <div class="rounded-xl border border-gray-200 dark:border-gray-700 p-4 space-y-2">
            <div class="text-lg font-semibold">
                Solicitar un turno de clase particular
            </div>
            <p class="text-sm text-gray-600 dark:text-gray-400">
                Estás solicitando un turno
                @if($this->materia)
                    para la materia <span class="font-semibold">{{ $this->materia->materia_nombre }}</span>
                @endif
                @if($this->tema)
                    — tema: <span class="font-semibold">{{ $this->tema->tema_nombre }}</span>
                @endif
                .
                Te mostraremos los profesores que dictan esta materia y sus horarios disponibles.
            </p>
        </div>

        {{-- Buscador de materia/tema + fecha --}}
        <div class="rounded-xl border border-gray-200 dark:border-gray-700 p-4 space-y-4">

            {{-- Buscador --}}
            <div class="space-y-1">
                <label class="text-sm font-medium">Buscar materia o tema</label>
                <div class="flex gap-2">
                    <div class="relative flex-1">
                        <input
                            type="text"
                            class="w-full rounded-lg border-gray-300 dark:bg-gray-900 dark:border-gray-700 pr-10"
                            placeholder="Ej.: Álgebra, Derivadas, Programación..."
                            wire:model.live="busqueda"
                        >
                        <button
                            type="button"
                            wire:click="consultarAhora"
                            wire:target="consultarAhora"
                            wire:loading.attr="disabled"
                            class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-500"
                        >
                            <x-heroicon-o-magnifying-glass class="w-5 h-5" />
                        </button>
                    </div>
                </div>

                {{-- Sugerencias (autocompletar) --}}
                @if(!empty($sugerenciasMaterias) || !empty($sugerenciasTemas))
                    <div class="mt-2 bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-lg shadow-sm p-2 space-y-1 text-sm">
                        @foreach($sugerenciasMaterias as $m)
                            <button
                                type="button"
                                class="w-full text-left px-2 py-1 rounded hover:bg-gray-100 dark:hover:bg-gray-800"
                                wire:click="seleccionarMateria({{ $m['materia_id'] }}, '{{ addslashes($m['materia_nombre']) }}')"
                            >
                                📘 Materia: {{ $m['materia_nombre'] }}
                            </button>
                        @endforeach

                        @foreach($sugerenciasTemas as $t)
                            <button
                                type="button"
                                class="w-full text-left px-2 py-1 rounded hover:bg-gray-100 dark:hover:bg-gray-800"
                                wire:click="seleccionarTema({{ $t['tema_id'] }}, '{{ addslashes($t['tema_nombre']) }}')"
                            >
                                🧩 Tema: {{ $t['tema_nombre'] }}
                            </button>
                        @endforeach
                    </div>
                @endif
            </div>

            {{-- Fecha --}}
            <div class="space-y-1">
                <label class="text-sm font-medium">Fecha de la clase</label>
                <input
                    type="date"
                    class="w-full md:w-64 rounded-lg border-gray-300 dark:bg-gray-900 dark:border-gray-700"
                    wire:model.live="fecha"
                >
                <p class="text-xs text-gray-500 mt-1">
                    Seleccioná el día en el que querés consultar los horarios disponibles.
                </p>
            </div>

            {{-- Botón "Consultar ahora" separado --}}
            <div class="pt-2">
                <x-filament::button
                    color="primary"
                    wire:click="consultarAhora"
                    wire:target="consultarAhora"
                    wire:loading.attr="disabled"
                    icon="heroicon-o-magnifying-glass"
                >
                    <span wire:loading.remove wire:target="consultarAhora">Consultar ahora</span>
                    <span wire:loading wire:target="consultarAhora">Buscando…</span>
                </x-filament::button>
                <span class="text-xs text-gray-500 ml-2">
                    Te mostraremos los horarios disponibles para la materia/tema elegido.
                </span>
            </div>
        </div>

        {{-- Resultado: slots en cards --}}
        @if($fecha)
            <div class="rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden">
                <div class="px-4 py-3 bg-gray-50 dark:bg-gray-800/60 flex items-center justify-between">
                    <h3 class="font-semibold">
                        Turnos disponibles
                        <span class="text-sm font-normal opacity-70">
                            — {{ \Carbon\Carbon::parse($fecha)->isoFormat('dddd D [de] MMMM YYYY') }}
                        </span>
                    </h3>
                    <span class="text-sm opacity-70">{{ count($slots) }} resultado(s)</span>
                </div>

                <div class="p-4">
                    @php
                        $fechaSel = \Carbon\Carbon::parse($fecha ?? now());
                        $esPasado = $fechaSel->isPast() && !$fechaSel->isToday();
                        $esHoy    = $fechaSel->isToday();
                        $leadEdge = now()->addMinutes(30)->format('H:i'); // margen de 30 min
                    @endphp

                    @if($esPasado)
                        <div class="text-sm text-red-600">
                            No podés reservar en fechas pasadas.
                        </div>
                    @elseif(empty($slots))
                        <p class="text-sm text-gray-500">
                            No hay horarios disponibles para esta materia en la fecha seleccionada.
                        </p>
                    @else
                        <div class="grid sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-3">
                            @foreach($slots as $i => $s)
                                @php
                                    $disabled = $esPasado || ($esHoy && ($s['desde'] < $leadEdge));
                                @endphp
                                <div class="rounded-lg border border-gray-200 dark:border-gray-700 p-3 flex flex-col justify-between bg-white/70 dark:bg-gray-900">
                                    <div class="text-sm space-y-1">
                                        <div class="font-medium">
                                            {{ $s['desde'] }} – {{ $s['hasta'] }}
                                        </div>
                                        <div class="text-xs opacity-70">
                                            Profesor: {{ $s['profesor_nombre'] ?? 'Profesor' }}
                                        </div>
                                        @if($this->materia)
                                            <div class="text-xs opacity-70">
                                                Materia: {{ $this->materia->materia_nombre }}
                                            </div>
                                        @endif
                                        @if($this->tema)
                                            <div class="text-xs opacity-70">
                                                Tema: {{ $this->tema->tema_nombre }}
                                            </div>
                                        @endif
                                    </div>
                                    <x-filament::button
                                        size="sm"
                                        class="mt-3"
                                        :disabled="$disabled"
                                        wire:click="reservar({{ $i }})"
                                        wire:target="reservar({{ $i }})"
                                        wire:loading.attr="disabled"
                                    >
                                        <span wire:loading.remove wire:target="reservar({{ $i }})">Reservar</span>
                                        <span wire:loading wire:target="reservar({{ $i }})">Reservando…</span>
                                    </x-filament::button>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>
        @endif
    </div>
</x-filament-panels::page>
