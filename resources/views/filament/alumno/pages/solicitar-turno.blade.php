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
                .
            </p>
        </div>

        {{-- Contexto académico (carrera activa) --}}
        @php
            $user = auth()->user();
            $carreraActiva = $user?->carreraActiva?->carrera_nombre;
            $institucionActiva = $user?->carreraActiva?->institucion?->institucion_nombre;
        @endphp

        @if(!$carreraActiva)
            <div style="
                border:1px solid #fde68a;
                background:#fffbeb;
                color:#92400e;
                padding:12px 14px;
                border-radius:12px;
                font-size:13px;
                font-weight:800;
            ">
                Para solicitar turnos, completá tu perfil académico y elegí una <strong>carrera activa</strong>.

                <div style="margin-top:10px;">
                    <a href="{{ \App\Filament\Alumno\Pages\MiPerfil::getUrl() }}">
                        <x-filament::button size="sm" color="warning">
                            Ir a Mi perfil
                        </x-filament::button>
                    </a>
                </div>
            </div>
        @else
            <div style="
                border:1px solid #e5e7eb;
                background:#fff;
                padding:12px 14px;
                border-radius:12px;
                font-size:13px;
                box-shadow:0 10px 22px rgba(15,23,42,.06);
            ">
                <strong>Carrera activa:</strong> {{ $carreraActiva }}
                @if($institucionActiva)
                    <span style="color:#6b7280;">— {{ $institucionActiva }}</span>
                @endif
                <a style="margin-left:10px; font-weight:900; color:#1d4ed8;"
                   href="{{ \App\Filament\Alumno\Pages\MiPerfil::getUrl() }}">
                    Cambiar
                </a>
            </div>
        @endif

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

        {{-- Panel de filtros --}}
        @if(!empty($slotsOriginales))
            <div style="border:1px solid #e5e7eb; border-radius:14px; padding:20px; background:#fff; box-shadow:0 10px 22px rgba(15,23,42,.06);">
                <div style="font-size:15px; font-weight:900; color:#111827; margin-bottom:16px;">
                    Filtrar turnos disponibles
                </div>

                <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(190px, 1fr)); gap:14px; align-items:end;">
                    <div style="display:flex; flex-direction:column; gap:7px;">
                        <label for="filtro-profesor" style="font-size:13px; font-weight:900; color:#111827;">Profesor</label>
                        <select id="filtro-profesor" wire:model="filtroProfesorId" style="width:100%; border:1px solid #d1d5db; border-radius:12px; padding:10px 12px; background:#fff; font-size:14px;">
                            <option value="">Todos los profesores</option>
                            @foreach($profesoresDisponibles as $profesorDisponible)
                                <option value="{{ $profesorDisponible['id'] }}">{{ $profesorDisponible['nombre'] }}</option>
                            @endforeach
                        </select>
                        @error('filtroProfesorId')
                            <span style="font-size:12px; color:#b91c1c;">{{ $message }}</span>
                        @enderror
                    </div>

                    <div style="display:flex; flex-direction:column; gap:7px;">
                        <label for="filtro-hora-desde" style="font-size:13px; font-weight:900; color:#111827;">Hora desde</label>
                        <input id="filtro-hora-desde" type="time" step="3600" wire:model="filtroHoraDesde" style="width:100%; border:1px solid #d1d5db; border-radius:12px; padding:10px 12px; background:#fff; font-size:14px;">
                        @error('filtroHoraDesde')
                            <span style="font-size:12px; color:#b91c1c;">{{ $message }}</span>
                        @enderror
                    </div>

                    <div style="display:flex; flex-direction:column; gap:7px;">
                        <label for="filtro-hora-hasta" style="font-size:13px; font-weight:900; color:#111827;">Hora hasta</label>
                        <input id="filtro-hora-hasta" type="time" step="3600" wire:model="filtroHoraHasta" style="width:100%; border:1px solid #d1d5db; border-radius:12px; padding:10px 12px; background:#fff; font-size:14px;">
                        @error('filtroHoraHasta')
                            <span style="font-size:12px; color:#b91c1c;">{{ $message }}</span>
                        @enderror
                    </div>

                    <div style="display:flex; gap:10px; flex-wrap:wrap;">
                        <x-filament::button wire:click="aplicarFiltros" wire:target="aplicarFiltros" wire:loading.attr="disabled">
                            Aplicar filtros
                        </x-filament::button>
                        <x-filament::button color="gray" wire:click="limpiarFiltros" wire:target="limpiarFiltros" wire:loading.attr="disabled">
                            Limpiar filtros
                        </x-filament::button>
                    </div>
                </div>
            </div>
        @endif

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
                        {{ count($slots) }} de {{ count($slotsOriginales) }} resultado(s)
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

                    @elseif(empty($slotsOriginales))
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

                    @elseif(empty($slots))
                        <div style="
                            border:1px solid #bfdbfe;
                            background:#eff6ff;
                            padding:14px;
                            border-radius:12px;
                            color:#1e40af;
                            font-size:13px;
                        ">
                            Hay horarios disponibles, pero ninguno coincide con los filtros aplicados.
                        </div>

                    @else
                        <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(300px, 1fr)); gap:18px;">
                            @foreach($slots as $s)
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
                                    <div style="font-weight:900; font-size:16px; color:#111827;">
                                        {{ $s['desde'] }} – {{ $s['hasta'] }}
                                    </div>

                                    <div style="margin-top:6px; display:grid; grid-template-columns:minmax(0, 1fr) 120px; column-gap:16px; align-items:start;">
                                        <div style="grid-column:1; grid-row:1; min-width:0;">
                                            <div style="font-size:13px; color:#374151;">
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
                                        </div>

                                        <div style="grid-column:1; grid-row:2; min-width:0;">
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
                                        </div>

                                        <div style="display:contents;">
                                            <div style="
                                                grid-column:2;
                                                grid-row:1;
                                                align-self:center;
                                                justify-self:end;
                                                width:64px;
                                                height:64px;
                                                border-radius:9999px;
                                                overflow:hidden;
                                                flex-shrink:0;
                                                display:flex;
                                                align-items:center;
                                                justify-content:center;
                                                background:#e0e7ff;
                                                color:#3730a3;
                                                border:1px solid #c7d2fe;
                                                font-size:18px;
                                                font-weight:900;
                                            ">
                                                @if(!empty($s['profesor_foto']))
                                                    <img
                                                        src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($s['profesor_foto']) }}"
                                                        alt="Foto de {{ $s['profesor_nombre'] ?? 'profesor' }}"
                                                        style="width:64px; height:64px; border-radius:9999px; object-fit:cover; flex-shrink:0;"
                                                    >
                                                @elseif(!empty($s['profesor_google_avatar']))
                                                    <img
                                                        src="{{ $s['profesor_google_avatar'] }}"
                                                        alt="Foto de {{ $s['profesor_nombre'] ?? 'profesor' }}"
                                                        style="width:64px; height:64px; border-radius:9999px; object-fit:cover; flex-shrink:0;"
                                                    >
                                                @else
                                                    <span aria-label="Avatar de {{ $s['profesor_nombre'] ?? 'profesor' }}">
                                                        {{ $s['profesor_iniciales'] ?? 'P' }}
                                                    </span>
                                                @endif
                                            </div>

                                            @if($badgeTop)
                                                <span style="
                                                    grid-column:2;
                                                    grid-row:2;
                                                    justify-self:end;
                                                    margin-top:8px;
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
                                                    grid-column:2;
                                                    grid-row:2;
                                                    justify-self:end;
                                                    margin-top:8px;
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
                                                    grid-column:2;
                                                    grid-row:2;
                                                    justify-self:end;
                                                    margin-top:8px;
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
                                            wire:click="reservar('{{ $s['slot_key'] }}')"
                                            wire:target="reservar('{{ $s['slot_key'] }}')"
                                            wire:loading.attr="disabled"
                                        >
                                            <span wire:loading.remove wire:target="reservar('{{ $s['slot_key'] }}')">Reservar</span>
                                            <span wire:loading wire:target="reservar('{{ $s['slot_key'] }}')">Reservando…</span>
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

    @if ($mostrarModalExito)
        <div
            role="dialog"
            aria-modal="true"
            aria-labelledby="modal-exito-titulo"
            style="
                position: fixed;
                inset: 0;
                z-index: 9999;
                display: flex;
                align-items: center;
                justify-content: center;
                padding: 20px;
                background: rgba(15, 23, 42, 0.68);
            "
        >
            <div
                style="
                    width: 100%;
                    max-width: 440px;
                    padding: 32px 28px 26px;
                    border-radius: 18px;
                    background: #ffffff;
                    text-align: center;
                    box-shadow: 0 24px 60px rgba(15, 23, 42, 0.35);
                "
            >
                <div
                    aria-hidden="true"
                    style="
                        width: 72px;
                        height: 72px;
                        margin: 0 auto 20px;
                        display: flex;
                        align-items: center;
                        justify-content: center;
                        border-radius: 9999px;
                        background: #dcfce7;
                        color: #16a34a;
                    "
                >
                    <svg
                        xmlns="http://www.w3.org/2000/svg"
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="2.5"
                        style="width: 40px; height: 40px;"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            d="m5 12 4 4L19 6"
                        />
                    </svg>
                </div>

                <h2
                    id="modal-exito-titulo"
                    style="
                        margin: 0;
                        color: #111827;
                        font-size: 22px;
                        font-weight: 800;
                        line-height: 1.3;
                    "
                >
                    Solicitud enviada correctamente
                </h2>

                <p
                    style="
                        margin: 14px 0 24px;
                        color: #4b5563;
                        font-size: 15px;
                        line-height: 1.6;
                    "
                >
                    El profesor deberá aceptar el turno. Te avisaremos cuando responda.
                </p>

                <x-filament::button
                    type="button"
                    wire:click="cerrarModalExito"
                    wire:loading.attr="disabled"
                    wire:target="cerrarModalExito"
                >
                    Aceptar
                </x-filament::button>
            </div>
        </div>
    @endif
</x-filament-panels::page>
