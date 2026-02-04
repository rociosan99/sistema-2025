<x-filament-panels::page>
    <div style="display:flex; flex-direction:column; gap:24px;">

        {{-- Encabezado --}}
        <div style="
            border:1px solid #e5e7eb;
            border-radius:14px;
            padding:22px;
            background:linear-gradient(135deg,#eef2ff 0%, #ffffff 55%, #ecfeff 100%);
            box-shadow:0 10px 24px rgba(15,23,42,.08);
        ">
            <div style="display:flex; align-items:center; justify-content:space-between; gap:12px;">
                <div style="font-size:18px; font-weight:900; color:#111827;">
                    Solicitar un turno de clase particular
                </div>

                <span style="
                    display:inline-block;
                    padding:7px 12px;
                    border-radius:999px;
                    background:#111827;
                    color:#fff;
                    font-size:12px;
                    font-weight:800;
                    letter-spacing:.2px;
                ">
                    Ordenado por rating ⭐
                </span>
            </div>

            <p style="font-size:13px; color:#374151; line-height:1.7; margin:12px 0 0 0;">
                Estás solicitando un turno
                @if($this->materia)
                    para la materia <span style="font-weight:900;">{{ $this->materia->materia_nombre }}</span>
                @endif
                @if($this->tema)
                    — tema: <span style="font-weight:900;">{{ $this->tema->tema_nombre }}</span>
                @endif
                . Te mostraremos profesores con horarios disponibles y mejor puntuación primero.
            </p>
        </div>

        {{-- Buscador + fecha --}}
        <div style="
            border:1px solid #e5e7eb;
            border-radius:14px;
            padding:20px;
            background:#fff;
            display:flex;
            flex-direction:column;
            gap:18px;
            box-shadow:0 10px 22px rgba(15,23,42,.06);
        ">

            {{-- Institución + Carrera --}}
            <div style="display:flex; gap:12px; flex-wrap:wrap;">

                <div style="display:flex; flex-direction:column; gap:8px; min-width:260px; flex:1;">
                    <label style="font-size:13px; font-weight:900; color:#111827;">
                        Institución
                    </label>

                    <select
                        wire:model.live="institucionId"
                        style="
                            width:100%;
                            border-radius:12px;
                            border:1px solid #d1d5db;
                            padding:10px 12px;
                            font-size:14px;
                            background:#fff;
                        "
                    >
                        <option value="">Seleccioná una institución…</option>
                        @foreach($institucionesOptions as $id => $nombre)
                            <option value="{{ $id }}">{{ $nombre }}</option>
                        @endforeach
                    </select>
                </div>

                <div style="display:flex; flex-direction:column; gap:8px; min-width:260px; flex:1;">
                    <label style="font-size:13px; font-weight:900; color:#111827;">
                        Carrera
                    </label>

                    <select
                        wire:model.live="carreraId"
                        @disabled(empty($carrerasOptions))
                        style="
                            width:100%;
                            border-radius:12px;
                            border:1px solid #d1d5db;
                            padding:10px 12px;
                            font-size:14px;
                            background:#fff;
                        "
                    >
                        <option value="">Seleccioná una carrera…</option>
                        @foreach($carrerasOptions as $id => $nombre)
                            <option value="{{ $id }}">{{ $nombre }}</option>
                        @endforeach
                    </select>

                    @if(empty($carrerasOptions) && $this->institucionId)
                        <p style="font-size:11px; color:#6b7280; margin:0;">
                            Esta institución no tiene carreras cargadas.
                        </p>
                    @endif
                </div>

            </div>

            {{-- Buscador --}}
            <div style="display:flex; flex-direction:column; gap:8px;">
                <label style="font-size:13px; font-weight:900; color:#111827;">
                    Buscar materia o tema
                </label>

                <div style="display:flex; gap:10px; align-items:center;">
                    <div style="position:relative; flex:1;">
                        <input
                            type="text"
                            style="
                                width:100%;
                                border-radius:12px;
                                border:1px solid #d1d5db;
                                padding:11px 44px 11px 14px;
                                font-size:14px;
                                outline:none;
                                background:#ffffff;
                                box-shadow:0 1px 0 rgba(15,23,42,.04) inset;
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
                                position:absolute;
                                top:50%;
                                transform:translateY(-50%);
                                right:10px;
                                width:34px;
                                height:34px;
                                border-radius:10px;
                                border:1px solid #e5e7eb;
                                background:#f9fafb;
                                display:flex;
                                align-items:center;
                                justify-content:center;
                                cursor:pointer;
                                color:#374151;
                            "
                            title="Buscar"
                            onmouseover="this.style.backgroundColor='#eef2ff'"
                            onmouseout="this.style.backgroundColor='#f9fafb'"
                        >
                            <x-heroicon-o-magnifying-glass style="width:18px; height:18px;" />
                        </button>
                    </div>
                </div>

                {{-- Mensaje si escribe y no eligió carrera --}}
                @if(mb_strlen($busqueda ?? '') >= 2 && !$this->carreraId)
                    <div style="
                        margin-top:8px;
                        border:1px solid #fde68a;
                        background:#fffbeb;
                        color:#92400e;
                        padding:10px 12px;
                        border-radius:12px;
                        font-size:13px;
                        font-weight:700;
                    ">
                        Elegí una carrera para poder buscar materias y temas.
                    </div>
                @endif

                {{-- Sugerencias --}}
                @if(!empty($sugerenciasMaterias) || !empty($sugerenciasTemas))
                    <div style="
                        margin-top:8px;
                        background:#ffffff;
                        border:1px solid #e5e7eb;
                        border-radius:12px;
                        padding:8px;
                        font-size:13px;
                        display:flex;
                        flex-direction:column;
                        gap:6px;
                        box-shadow:0 14px 28px rgba(15,23,42,.14);
                    ">
                        @foreach($sugerenciasMaterias as $m)
                            <button
                                type="button"
                                style="
                                    width:100%;
                                    text-align:left;
                                    padding:10px 12px;
                                    border-radius:10px;
                                    border:1px solid #eef2ff;
                                    background:linear-gradient(135deg,#eef2ff 0%, #ffffff 70%);
                                    cursor:pointer;
                                "
                                onmouseover="this.style.filter='brightness(0.98)'"
                                onmouseout="this.style.filter='none'"
                                wire:click="seleccionarMateria({{ $m['materia_id'] }}, '{{ addslashes($m['materia_nombre']) }}')"
                            >
                                📘 <strong>Materia:</strong> {{ $m['materia_nombre'] }}
                            </button>
                        @endforeach

                        @foreach($sugerenciasTemas as $t)
                            <button
                                type="button"
                                style="
                                    width:100%;
                                    text-align:left;
                                    padding:10px 12px;
                                    border-radius:10px;
                                    border:1px solid #ecfeff;
                                    background:linear-gradient(135deg,#ecfeff 0%, #ffffff 70%);
                                    cursor:pointer;
                                "
                                onmouseover="this.style.filter='brightness(0.98)'"
                                onmouseout="this.style.filter='none'"
                                wire:click="seleccionarTema({{ $t['tema_id'] }}, '{{ addslashes($t['tema_nombre']) }}')"
                            >
                                🧩 <strong>Tema:</strong> {{ $t['tema_nombre'] }}
                            </button>
                        @endforeach
                    </div>
                @endif
            </div>

            {{-- Fecha --}}
            <div style="
                display:flex;
                flex-direction:column;
                gap:8px;
                padding-top:14px;
                border-top:1px solid #f3f4f6;
            ">
                <label style="font-size:13px; font-weight:900; color:#111827;">
                    Fecha de la clase
                </label>

                <input
                    type="date"
                    style="
                        width:260px;
                        max-width:100%;
                        border-radius:12px;
                        border:1px solid #d1d5db;
                        padding:10px 12px;
                        font-size:14px;
                        background:#fff;
                    "
                    wire:model.live="fecha"
                >

                <p style="font-size:11px; color:#6b7280; margin:0; line-height:1.6;">
                    Seleccioná el día para consultar horarios disponibles.
                </p>
            </div>

            {{-- Botón Consultar --}}
            <div style="padding-top:4px;">
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
            </div>
        </div>

        {{-- Resultado --}}
        @if($fecha)
            <div style="
                border:1px solid #e5e7eb;
                border-radius:14px;
                overflow:hidden;
                background:#ffffff;
                box-shadow:0 10px 22px rgba(15,23,42,.06);
            ">
                <div style="
                    padding:14px 20px;
                    background:linear-gradient(135deg,#111827 0%, #1d4ed8 50%, #0ea5e9 100%);
                    color:#fff;
                    display:flex;
                    align-items:center;
                    justify-content:space-between;
                    gap:12px;
                ">
                    <div>
                        <div style="font-size:15px; font-weight:900; margin:0;">
                            Turnos disponibles
                        </div>
                        <div style="font-size:12px; opacity:.9; margin-top:2px;">
                            {{ \Carbon\Carbon::parse($fecha)->isoFormat('dddd D [de] MMMM YYYY') }}
                        </div>
                    </div>

                    <div style="
                        display:inline-block;
                        padding:7px 10px;
                        border-radius:999px;
                        background:rgba(255,255,255,.15);
                        font-size:12px;
                        font-weight:800;
                    ">
                        {{ count($slots) }} resultado(s)
                    </div>
                </div>

                <div style="padding:20px;">
                    @php
                        $fechaSel = \Carbon\Carbon::parse($fecha ?? now());
                        $esPasado = $fechaSel->isPast() && !$fechaSel->isToday();
                        $esHoy    = $fechaSel->isToday();
                        $leadEdge = now()->addMinutes(30)->format('H:i');
                    @endphp

                    @if($esPasado)
                        <div style="
                            border:1px solid #fecaca;
                            background:#fef2f2;
                            color:#991b1b;
                            padding:12px 14px;
                            border-radius:12px;
                            font-size:13px;
                            font-weight:700;
                        ">
                            No podés reservar en fechas pasadas.
                        </div>

                    @elseif(empty($slots))
                        <div style="
                            border:1px solid #e5e7eb;
                            background:#f9fafb;
                            padding:14px;
                            border-radius:12px;
                            color:#6b7280;
                            font-size:13px;
                        ">
                            No hay horarios disponibles para esta materia/tema en la fecha seleccionada.
                        </div>

                    @else
                        {{-- (tu bloque de cards de slots sigue igual, sin cambios) --}}
                        <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(300px, 1fr)); gap:18px;">
                            @foreach($slots as $i => $s)
                                @php
                                    $disabled = $esPasado || ($esHoy && ($s['desde'] < $leadEdge));
                                    $avg = (float)($s['rating_avg'] ?? 0);
                                    $cnt = (int)($s['rating_count'] ?? 0);

                                    $filled = (int) floor($avg);
                                    $half = ($avg - $filled) >= 0.5;
                                    $stars = '';
                                    for ($k=1; $k<=5; $k++) {
                                        if ($k <= $filled) $stars .= '★';
                                        elseif ($half && $k == $filled+1) $stars .= '★';
                                        else $stars .= '☆';
                                    }

                                    $badgeTop = ($avg >= 4.7 && $cnt >= 3);
                                    $badgeNuevo = ($cnt > 0 && $cnt < 3);
                                    $badgeSin = ($cnt === 0);

                                    $chipBg = $avg >= 4.5 ? '#dcfce7' : ($avg >= 4.0 ? '#fef9c3' : '#fee2e2');
                                    $chipTx = $avg >= 4.5 ? '#166534' : ($avg >= 4.0 ? '#854d0e' : '#991b1b');
                                @endphp

                                <div style="
                                    border:1px solid #e5e7eb;
                                    border-radius:16px;
                                    padding:16px;
                                    background:linear-gradient(180deg,#ffffff 0%, #f8fafc 100%);
                                    box-shadow:0 16px 30px rgba(15,23,42,.10);
                                ">
                                    <div style="display:flex; justify-content:space-between; gap:12px; align-items:flex-start;">
                                        <div style="flex:1;">
                                            <div style="font-weight:900; font-size:16px; color:#111827;">
                                                {{ $s['desde'] }} – {{ $s['hasta'] }}
                                            </div>

                                            <div style="margin-top:6px; font-size:13px; color:#374151;">
                                                <span style="font-weight:900;">{{ $s['profesor_nombre'] ?? 'Profesor' }}</span>
                                            </div>

                                            <div style="margin-top:10px; display:flex; align-items:center; gap:10px; flex-wrap:wrap;">
                                                <span style="
                                                    display:inline-block;
                                                    padding:7px 10px;
                                                    border-radius:999px;
                                                    background:{{ $chipBg }};
                                                    color:{{ $chipTx }};
                                                    font-size:12px;
                                                    font-weight:900;
                                                ">
                                                    {{ $stars }}
                                                    <span style="margin-left:6px;">
                                                        {{ $avg > 0 ? number_format($avg, 1, ',', '.') : '—' }}
                                                    </span>
                                                </span>

                                                <span style="font-size:12px; color:#6b7280;">
                                                    ({{ $cnt }} calificación{{ $cnt === 1 ? '' : 'es' }})
                                                </span>
                                            </div>

                                            @if($this->materia)
                                                <div style="margin-top:10px; font-size:12px; color:#6b7280;">
                                                    📘 {{ $this->materia->materia_nombre }}
                                                </div>
                                            @endif
                                            @if($this->tema)
                                                <div style="margin-top:4px; font-size:12px; color:#6b7280;">
                                                    🧩 {{ $this->tema->tema_nombre }}
                                                </div>
                                            @endif

                                            @if(!empty($s['precio_por_hora']))
                                                <div style="margin-top:12px; font-size:12px; color:#111827;">
                                                    <span style="opacity:.75;">Precio por hora:</span>
                                                    <span style="font-weight:900;">
                                                        ${{ number_format((float) $s['precio_por_hora'], 0, ',', '.') }}
                                                    </span>
                                                </div>
                                            @endif

                                            @if(!empty($s['precio_total']))
                                                <div style="margin-top:2px; font-size:12px; color:#111827;">
                                                    <span style="opacity:.75;">Precio del turno:</span>
                                                    <span style="font-weight:900;">
                                                        ${{ number_format((float) $s['precio_total'], 0, ',', '.') }}
                                                    </span>
                                                </div>
                                            @endif
                                        </div>

                                        <div style="display:flex; flex-direction:column; gap:8px; align-items:flex-end; min-width:120px;">
                                            @if($badgeTop)
                                                <span style="
                                                    display:inline-block;
                                                    padding:7px 10px;
                                                    border-radius:12px;
                                                    background:linear-gradient(135deg,#f59e0b 0%, #f97316 100%);
                                                    color:#fff;
                                                    font-size:12px;
                                                    font-weight:900;
                                                ">
                                                    🏆 TOP
                                                </span>
                                            @elseif($badgeNuevo)
                                                <span style="
                                                    display:inline-block;
                                                    padding:7px 10px;
                                                    border-radius:12px;
                                                    background:#e0f2fe;
                                                    color:#075985;
                                                    font-size:12px;
                                                    font-weight:900;
                                                ">
                                                    ✨ NUEVO
                                                </span>
                                            @elseif($badgeSin)
                                                <span style="
                                                    display:inline-block;
                                                    padding:7px 10px;
                                                    border-radius:12px;
                                                    background:#f3f4f6;
                                                    color:#374151;
                                                    font-size:12px;
                                                    font-weight:900;
                                                ">
                                                    Sin reseñas
                                                </span>
                                            @endif
                                        </div>
                                    </div>

                                    <div style="margin-top:14px;">
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

                                        @if($disabled)
                                            <div style="margin-top:8px; font-size:11px; color:#9ca3af;">
                                                No disponible (horario pasado o demasiado cerca).
                                            </div>
                                        @endif
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
