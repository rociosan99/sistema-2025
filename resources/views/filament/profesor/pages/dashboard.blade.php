<x-filament-panels::page>

    <div style="display:flex; flex-direction:column; gap:18px;">

        {{-- =========================
            AGENDA SEMANAL (primero)
        ========================= --}}
        <div style="
            border:1px solid #e5e7eb;
            border-radius:18px;
            overflow:hidden;
            background:#fff;
            box-shadow:0 16px 34px rgba(0,0,0,.08);
        ">
            <div style="
                padding:14px 18px;
                background:linear-gradient(135deg,#111827 0%, #1d4ed8 55%, #0ea5e9 100%);
                color:#fff;
                display:flex;
                align-items:center;
                justify-content:space-between;
                gap:12px;
                flex-wrap:wrap;
            ">
                <div>
                    <div style="font-size:16px; font-weight:900;">Agenda semanal</div>
                    <div style="font-size:12px; opacity:.92; margin-top:2px;">
                        Semana desde: {{ \Carbon\Carbon::parse($this->weekStart)->format('d/m/Y') }}
                    </div>
                </div>

                <div style="display:flex; gap:8px; flex-wrap:wrap;">
                    <x-filament::button size="sm" color="gray" wire:click="semanaAnterior">← Semana</x-filament::button>
                    <x-filament::button size="sm" color="gray" wire:click="semanaActual">Hoy</x-filament::button>
                    <x-filament::button size="sm" color="gray" wire:click="semanaSiguiente">Semana →</x-filament::button>
                </div>
            </div>

            <div style="padding:16px;">
                <div style="display:grid; grid-template-columns:repeat(7, minmax(160px, 1fr)); gap:12px; overflow:auto;">
                    @foreach($this->agendaSemana as $fecha => $turnos)
                        @php
                            $d = \Carbon\Carbon::parse($fecha);
                            $esHoy = $d->isToday();
                        @endphp

                        <div style="
                            border:1px solid rgba(15,23,42,.10);
                            border-radius:16px;
                            background:{{ $esHoy ? 'linear-gradient(180deg,#eef2ff 0%, #ffffff 100%)' : '#fff' }};
                            box-shadow:0 10px 22px rgba(15,23,42,.06);
                            padding:12px;
                            min-height:180px;
                        ">
                            <div style="font-weight:900; color:#0f172a;">
                                {{ $d->isoFormat('ddd') }}
                                <span style="opacity:.65; font-weight:800;">{{ $d->format('d/m') }}</span>
                            </div>

                            <div style="margin-top:10px; display:flex; flex-direction:column; gap:8px;">
                                @forelse($turnos as $t)
                                    <div style="
                                        border:1px solid rgba(15,23,42,.10);
                                        border-radius:14px;
                                        padding:10px;
                                        background:#fff;
                                    ">
                                        <div style="font-weight:900; color:#0f172a;">
                                            {{ $t['inicio'] }} - {{ $t['fin'] }}
                                        </div>
                                        <div style="margin-top:4px; font-size:12px; color:#334155;">
                                            👤 {{ $t['alumno'] }}
                                        </div>
                                        <div style="margin-top:2px; font-size:12px; color:#334155;">
                                            📘 {{ $t['materia'] }}
                                            @if(!empty($t['tema']))
                                                — 🧩 {{ $t['tema'] }}
                                            @endif
                                        </div>
                                    </div>
                                @empty
                                    <div style="font-size:12px; color:#6b7280; padding-top:6px;">
                                        Sin clases pagadas.
                                    </div>
                                @endforelse
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        {{-- =========================
            CALIFICACIONES PENDIENTES (después)
        ========================= --}}
        <div style="
            border:1px solid #e5e7eb;
            border-radius:18px;
            overflow:hidden;
            background:#fff;
            box-shadow:0 16px 34px rgba(0,0,0,.08);
        ">
            <div style="
                padding:14px 18px;
                background: linear-gradient(135deg, #0ea5e9 0%, #22c55e 55%, #f59e0b 100%);
                color:#fff;
                display:flex;
                align-items:center;
                justify-content:space-between;
                gap:12px;
            ">
                <div>
                    <div style="font-size:16px; font-weight:900;">Calificaciones pendientes</div>
                    <div style="font-size:12px; opacity:.92; margin-top:2px;">
                        Clases confirmadas que ya finalizaron
                    </div>
                </div>

                <div style="
                    display:inline-block;
                    padding:7px 10px;
                    border-radius:999px;
                    background:rgba(255,255,255,.18);
                    font-size:12px;
                    font-weight:900;
                ">
                    {{ count($this->pendientesCalificar) }} pendiente(s)
                </div>
            </div>

            <div style="padding:16px;">
                @if (count($this->pendientesCalificar))
                    <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(320px, 1fr)); gap:14px;">
                        @foreach ($this->pendientesCalificar as $p)
                            <div style="
                                border:1px solid rgba(15,23,42,.10);
                                border-radius:16px;
                                background:linear-gradient(180deg,#fff 0%, #f8fafc 100%);
                                box-shadow:0 14px 28px rgba(15,23,42,.08);
                                padding:14px;
                            ">
                                <div style="font-size:16px; font-weight:900; color:#0f172a;">
                                    {{ $p['hora_inicio'] }} - {{ $p['hora_fin'] }}
                                </div>

                                <div style="margin-top:6px; display:flex; gap:8px; flex-wrap:wrap;">
                                    <span style="
                                        display:inline-flex; align-items:center;
                                        padding:6px 10px; border-radius:999px;
                                        font-size:12px; font-weight:900;
                                        background:rgba(6,182,212,.12); color:#0e7490;
                                        box-shadow: inset 0 0 0 1px rgba(6,182,212,.22);
                                    ">
                                        📅 {{ $p['fecha'] }}
                                    </span>
                                    <span style="
                                        display:inline-flex; align-items:center;
                                        padding:6px 10px; border-radius:999px;
                                        font-size:12px; font-weight:900;
                                        background:rgba(34,197,94,.12); color:#166534;
                                        box-shadow: inset 0 0 0 1px rgba(34,197,94,.22);
                                    ">
                                        ✅ Confirmada
                                    </span>
                                </div>

                                <div style="margin-top:12px; display:flex; flex-direction:column; gap:8px;">
                                    <div style="
                                        padding:10px; border-radius:14px;
                                        background:rgba(15,23,42,.03);
                                        box-shadow: inset 0 0 0 1px rgba(15,23,42,.06);
                                    ">
                                        <div style="font-size:12px; font-weight:800; color:rgba(15,23,42,.60);">Alumno</div>
                                        <div style="font-size:13px; font-weight:900; color:#0f172a; margin-top:2px;">
                                            {{ $p['alumno'] }}
                                        </div>
                                    </div>

                                    <div style="
                                        padding:10px; border-radius:14px;
                                        background:rgba(15,23,42,.03);
                                        box-shadow: inset 0 0 0 1px rgba(15,23,42,.06);
                                    ">
                                        <div style="font-size:12px; font-weight:800; color:rgba(15,23,42,.60);">Materia</div>
                                        <div style="font-size:13px; font-weight:900; color:#0f172a; margin-top:2px;">
                                            {{ $p['materia'] }}
                                        </div>
                                    </div>

                                    <div style="
                                        padding:10px; border-radius:14px;
                                        background:rgba(15,23,42,.03);
                                        box-shadow: inset 0 0 0 1px rgba(15,23,42,.06);
                                    ">
                                        <div style="font-size:12px; font-weight:800; color:rgba(15,23,42,.60);">Tema</div>
                                        <div style="font-size:13px; font-weight:900; color:#0f172a; margin-top:2px;">
                                            {{ $p['tema'] }}
                                        </div>
                                    </div>
                                </div>

                                <div style="margin-top:12px; display:flex; justify-content:flex-end;">
                                    <button
                                        type="button"
                                        style="
                                            cursor:pointer;
                                            border:none;
                                            border-radius:14px;
                                            padding:10px 12px;
                                            font-weight:900;
                                            font-size:13px;
                                            color:#0f172a;
                                            background: linear-gradient(135deg, #fde68a 0%, #f59e0b 55%, #fb7185 100%);
                                            box-shadow: 0 12px 24px rgba(245, 158, 11, .25);
                                            display:inline-flex;
                                            align-items:center;
                                            gap:8px;
                                        "
                                        wire:click="mountAction('calificar_alumno', { turno_id: {{ $p['id'] }} })"
                                    >
                                        <span style="
                                            display:inline-flex; align-items:center; justify-content:center;
                                            width:18px; height:18px; border-radius:6px;
                                            background: rgba(255,255,255,.35);
                                            box-shadow: inset 0 0 0 1px rgba(255,255,255,.35);
                                        ">⭐</span>
                                        Calificar alumno
                                    </button>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div style="
                        border:1px solid rgba(15,23,42,.10);
                        border-radius:16px;
                        padding:14px;
                        background:linear-gradient(135deg, rgba(14,165,233,.08) 0%, rgba(34,197,94,.08) 55%, rgba(245,158,11,.08) 100%);
                        color:#0f172a;
                        font-size:13px;
                        font-weight:800;
                    ">
                        No tenés clases pendientes para calificar.
                    </div>
                @endif
            </div>
        </div>

        <x-filament-actions::modals />
    </div>

</x-filament-panels::page>
