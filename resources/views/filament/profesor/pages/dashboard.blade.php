<x-filament-panels::page>
    <h1 class="text-2xl font-bold mb-6 text-gray-900 dark:text-white">
        Bienvenido, profesor
    </h1>

    {{-- Contenedor de las Cards con una cuadrícula de 2 columnas --}}
    <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">

        {{-- 📚 CARD: Materias que enseña --}}
        <x-filament::section>
            <x-slot name="heading">
                <div class="flex items-center space-x-2">
                    <x-filament::icon icon="heroicon-o-book-open" class="h-6 w-6 text-primary-500" />
                    <span class="text-xl font-semibold text-gray-900 dark:text-white">
                        Materias que enseña
                    </span>
                </div>
            </x-slot>

            @if(count($this->materias))
                <ul class="list-disc pl-6 space-y-2">
                    @foreach($this->materias as $materia)
                        <li class="text-gray-700 dark:text-gray-300 flex items-center">
                            <x-filament::icon icon="heroicon-m-check-circle" class="h-4 w-4 mr-2 text-success-500" />
                            {{ $materia }}
                        </li>
                    @endforeach
                </ul>
            @else
                <p class="text-gray-500 dark:text-gray-400 p-2 border-l-4 border-warning-500 bg-warning-50 dark:bg-warning-900/10 rounded-r-lg">
                    <x-filament::icon icon="heroicon-o-exclamation-triangle" class="h-5 w-5 mr-2 inline text-warning-500" />
                    Todavía no tienes materias asignadas.
                </p>
            @endif
        </x-filament::section>

        {{-- 🧠 CARD: Temas que domina --}}
        <x-filament::section>
            <x-slot name="heading">
                <div class="flex items-center space-x-2">
                    <x-filament::icon icon="heroicon-o-light-bulb" class="h-6 w-6 text-info-500" />
                    <span class="text-xl font-semibold text-gray-900 dark:text-white">
                        Temas que domina
                    </span>
                </div>
            </x-slot>

            @if(count($this->temas))
                <ul class="list-disc pl-6 space-y-2">
                    @foreach($this->temas as $tema)
                        <li class="text-gray-700 dark:text-gray-300 flex items-center">
                            <x-filament::icon icon="heroicon-m-academic-cap" class="h-4 w-4 mr-2 text-info-500" />
                            {{ $tema }}
                        </li>
                    @endforeach
                </ul>
            @else
                <p class="text-gray-500 dark:text-gray-400 p-2 border-l-4 border-warning-500 bg-warning-50 dark:bg-warning-900/10 rounded-r-lg">
                    <x-filament::icon icon="heroicon-o-exclamation-triangle" class="h-5 w-5 mr-2 inline text-warning-500" />
                    Todavía no tienes temas seleccionados.
                </p>
            @endif
        </x-filament::section>

    </div>
</x-filament-panels::page>
