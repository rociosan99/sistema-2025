<x-filament-panels::page>
    <div
        style="
            display: flex;
            flex-direction: column;
            gap: 24px;
        "
    >

        {{-- Encabezado --}}
        <div
            style="
                border: 1px solid #e5e7eb;
                border-radius: 12px;
                padding: 20px;
                background-color: #ffffff;
                display: flex;
                flex-direction: column;
                gap: 12px;
                box-shadow: 0 1px 2px rgba(15, 23, 42, 0.06);
            "
        >
            <div style="font-size: 18px; font-weight: 600;">
                Solicitar un turno de clase particular
            </div>
            <p
                style="
                    font-size: 13px;
                    color: #4b5563;
                    line-height: 1.6;
                    margin: 0;
                "
            >
                Estás solicitando un turno
                @if($this->materia)
                    para la materia <span style="font-weight: 600;">{{ $this->materia->materia_nombre }}</span>
                @endif
                @if($this->tema)
                    — tema: <span style="font-weight: 600;">{{ $this->tema->tema_nombre }}</span>
                @endif
                .
                Te mostraremos los profesores que dictan esta materia y sus horarios disponibles.
            </p>
        </div>

        {{-- Buscador de materia/tema + fecha --}}
        <div
            style="
                border: 1px solid #e5e7eb;
                border-radius: 12px;
                padding: 20px;
                background-color: #ffffff;
                display: flex;
                flex-direction: column;
                gap: 20px;
                box-shadow: 0 1px 2px rgba(15, 23, 42, 0.06);
            "
        >

            {{-- Buscador --}}
            <div
                style="
                    display: flex;
                    flex-direction: column;
                    gap: 8px;
                "
            >
                <label
                    style="
                        font-size: 13px;
                        font-weight: 500;
                    "
                >
                    Buscar materia o tema
                </label>

                <div
                    style="
                        display: flex;
                        gap: 8px;
                    "
                >
                    <div style="position: relative; flex: 1;">
                        <input
                            type="text"
                            style="
                                width: 100%;
                                border-radius: 8px;
                                border: 1px solid #d1d5db;
                                padding: 8px 32px 8px 10px;
                                font-size: 14px;
                            "
                            placeholder="Ej.: Álgebra, Derivadas, Programación..."
                            wire:model.live="busqueda"
                        >
                        <button
                            type="button"
                            wire:click="consultarAhora"
                            wire:target="consultarAhora"
                            wire:loading.attr="disabled"
                            style="
                                position: absolute;
                                top: 0;
                                right: 0;
                                height: 100%;
                                padding-right: 10px;
                                padding-left: 6px;
                                border: none;
                                background: transparent;
                                display: flex;
                                align-items: center;
                                cursor: pointer;
                                color: #6b7280;
                            "
                        >
                            <x-heroicon-o-magnifying-glass style="width: 20px; height: 20px;" />
                        </button>
                    </div>
                </div>

                {{-- Sugerencias (autocompletar) --}}
                @if(!empty($sugerenciasMaterias) || !empty($sugerenciasTemas))
                    <div
                        style="
                            margin-top: 8px;
                            background-color: #ffffff;
                            border: 1px solid #e5e7eb;
                            border-radius: 8px;
                            padding: 6px;
                            font-size: 13px;
                            display: flex;
                            flex-direction: column;
                            gap: 4px;
                            box-shadow: 0 2px 6px rgba(15, 23, 42, 0.12);
                        "
                    >
                        @foreach($sugerenciasMaterias as $m)
                            <button
                                type="button"
                                style="
                                    width: 100%;
                                    text-align: left;
                                    padding: 4px 6px;
                                    border-radius: 6px;
                                    border: none;
                                    background: transparent;
                                    cursor: pointer;
                                "
                                onmouseover="this.style.backgroundColor='#f3f4f6'"
                                onmouseout="this.style.backgroundColor='transparent'"
                                wire:click="seleccionarMateria({{ $m['materia_id'] }}, '{{ addslashes($m['materia_nombre']) }}')"
                            >
                                📘 Materia: {{ $m['materia_nombre'] }}
                            </button>
                        @endforeach

                        @foreach($sugerenciasTemas as $t)
                            <button
                                type="button"
                                style="
                                    width: 100%;
                                    text-align: left;
                                    padding: 4px 6px;
                                    border-radius: 6px;
                                    border: none;
                                    background: transparent;
                                    cursor: pointer;
                                "
                                onmouseover="this.style.backgroundColor='#f3f4f6'"
                                onmouseout="this.style.backgroundColor='transparent'"
                                wire:click="seleccionarTema({{ $t['tema_id'] }}, '{{ addslashes($t['tema_nombre']) }}')"
                            >
                                🧩 Tema: {{ $t['tema_nombre'] }}
                            </button>
                        @endforeach
                    </div>
                @endif
            </div>

            {{-- Fecha --}}
            <div
                style="
                    display: flex;
                    flex-direction: column;
                    gap: 6px;
                    padding-top: 12px;
                    border-top: 1px solid #f3f4f6;
                "
            >
                <label
                    style="
                        font-size: 13px;
                        font-weight: 500;
                    "
                >
                    Fecha de la clase
                </label>
                <input
                    type="date"
                    style="
                        width: 260px;
                        max-width: 100%;
                        border-radius: 8px;
                        border: 1px solid #d1d5db;
                        padding: 6px 10px;
                        font-size: 14px;
                    "
                    wire:model.live="fecha"
                >
                <p
                    style="
                        font-size: 11px;
                        color: #6b7280;
                        margin: 4px 0 0 0;
                        line-height: 1.5;
                    "
                >
                    Seleccioná el día en el que querés consultar los horarios disponibles.
                </p>
            </div>

            {{-- Botón "Consultar ahora" --}}
            <div style="padding-top: 8px;">
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
                <span
                    style="
                        font-size: 11px;
                        color: #6b7280;
                        margin-left: 6px;
                    "
                >
                    Te mostraremos los horarios disponibles para la materia/tema elegido.
                </span>
            </div>
        </div>

        {{-- Resultado: slots en cards --}}
        @if($fecha)
            <div
                style="
                    border: 1px solid #e5e7eb;
                    border-radius: 12px;
                    overflow: hidden;
                    background-color: #f9fafb;
                    box-shadow: 0 1px 2px rgba(15, 23, 42, 0.06);
                "
            >
                <div
                    style="
                        padding: 12px 20px;
                        background-color: #f3f4f6;
                        display: flex;
                        align-items: center;
                        justify-content: space-between;
                    "
                >
                    <h3
                        style="
                            font-size: 15px;
                            font-weight: 600;
                            margin: 0;
                        "
                    >
                        Turnos disponibles
                        <span
                            style="
                                font-size: 13px;
                                font-weight: 400;
                                opacity: 0.7;
                            "
                        >
                            — {{ \Carbon\Carbon::parse($fecha)->isoFormat('dddd D [de] MMMM YYYY') }}
                        </span>
                    </h3>
                    <span
                        style="
                            font-size: 13px;
                            opacity: 0.7;
                        "
                    >
                        {{ count($slots) }} resultado(s)
                    </span>
                </div>

                <div style="padding: 20px;">
                    @php
                        $fechaSel = \Carbon\Carbon::parse($fecha ?? now());
                        $esPasado = $fechaSel->isPast() && !$fechaSel->isToday();
                        $esHoy    = $fechaSel->isToday();
                        $leadEdge = now()->addMinutes(30)->format('H:i'); // margen de 30 min
                    @endphp

                    @if($esPasado)
                        <div
                            style="
                                font-size: 13px;
                                color: #b91c1c;
                            "
                        >
                            No podés reservar en fechas pasadas.
                        </div>
                    @elseif(empty($slots))
                        <p
                            style="
                                font-size: 13px;
                                color: #6b7280;
                                margin: 0;
                            "
                        >
                            No hay horarios disponibles para esta materia en la fecha seleccionada.
                        </p>
                    @else
                        {{-- GRID de cards --}}
                        <div
                            style="
                                display: grid;
                                grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
                                gap: 18px;
                            "
                        >
                            @foreach($slots as $i => $s)
                                @php
                                    $disabled = $esPasado || ($esHoy && ($s['desde'] < $leadEdge));
                                @endphp

                                <div
                                    style="
                                        border: 1px solid #e5e7eb;
                                        border-radius: 12px;
                                        padding: 14px;
                                        background-color: #ffffff;
                                        display: flex;
                                        flex-direction: column;
                                        justify-content: space-between;
                                        box-shadow: 0 1px 3px rgba(15, 23, 42, 0.12);
                                    "
                                >
                                    <div
                                        style="
                                            display: flex;
                                            flex-direction: column;
                                            gap: 4px;
                                            font-size: 13px;
                                            line-height: 1.5;
                                        "
                                    >
                                        <div
                                            style="
                                                font-weight: 600;
                                                font-size: 14px;
                                                color: #111827;
                                            "
                                        >
                                            {{ $s['desde'] }} – {{ $s['hasta'] }}
                                        </div>

                                        <div
                                            style="
                                                font-size: 12px;
                                                opacity: 0.75;
                                            "
                                        >
                                            Profesor: {{ $s['profesor_nombre'] ?? 'Profesor' }}
                                        </div>

                                        @if($this->materia)
                                            <div
                                                style="
                                                    font-size: 12px;
                                                    opacity: 0.75;
                                                "
                                            >
                                                Materia: {{ $this->materia->materia_nombre }}
                                            </div>
                                        @endif

                                        @if($this->tema)
                                            <div
                                                style="
                                                    font-size: 12px;
                                                    opacity: 0.75;
                                                "
                                            >
                                                Tema: {{ $this->tema->tema_nombre }}
                                            </div>
                                        @endif

                                        {{-- 💰 Precios --}}
                                        @if(!empty($s['precio_por_hora']))
                                            <div
                                                style="
                                                    font-size: 12px;
                                                    margin-top: 4px;
                                                "
                                            >
                                                Precio por hora:
                                                <span style="font-weight: 600;">
                                                    ${{ number_format((float) $s['precio_por_hora'], 0, ',', '.') }}
                                                </span>
                                            </div>
                                        @endif

                                        @if(!empty($s['precio_total']))
                                            <div
                                                style="
                                                    font-size: 12px;
                                                "
                                            >
                                                Precio de este turno:
                                                <span style="font-weight: 600;">
                                                    ${{ number_format((float) $s['precio_total'], 0, ',', '.') }}
                                                </span>
                                            </div>
                                        @endif
                                    </div>

                                    <div style="margin-top: 12px;">
                                        <x-filament::button
                                            size="sm"
                                            class="w-full"
                                            :disabled="$disabled"
                                            wire:click="reservar({{ $i }})"
                                            wire:target="reservar({{ $i }})"
                                            wire:loading.attr="disabled"
                                        >
                                            <span wire:loading.remove wire:target="reservar({{ $i }})">Reservar</span>
                                            <span wire:loading wire:target="reservar({{ $i }})">Reservando…</span>
                                        </x-filament::button>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>
        @endif
    </div>
</x-filament-panels::page>
