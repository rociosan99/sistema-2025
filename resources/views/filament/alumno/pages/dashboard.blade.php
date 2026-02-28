<x-filament-panels::page>

    <style>
        /* ====== Layout general ====== */
        .al-dashboard-wrap{
            font-family: system-ui, -apple-system, Segoe UI, Roboto, Arial, sans-serif;
        }

        /* ====== Header tipo app ====== */
        .al-header{
            display:flex;
            align-items:center;
            justify-content:space-between;
            gap:16px;
            padding:18px 18px;
            border-radius:18px;
            background: linear-gradient(135deg, #4f46e5 0%, #06b6d4 45%, #22c55e 100%);
            color:#fff;
            box-shadow: 0 18px 40px rgba(0,0,0,.15);
            margin-bottom:18px;
        }

        .al-header-left{
            display:flex;
            align-items:center;
            gap:14px;
        }

        .al-avatar{
            width:44px;
            height:44px;
            border-radius:14px;
            background: rgba(255,255,255,.22);
            display:flex;
            align-items:center;
            justify-content:center;
            box-shadow: inset 0 0 0 1px rgba(255,255,255,.25);
        }

        .al-title{
            margin:0;
            font-size:20px;
            font-weight:800;
            letter-spacing:.2px;
        }

        .al-subtitle{
            margin:2px 0 0 0;
            font-size:13px;
            opacity:.92;
        }

        .al-pill{
            display:inline-flex;
            align-items:center;
            gap:8px;
            padding:10px 12px;
            border-radius:999px;
            background: rgba(255,255,255,.20);
            box-shadow: inset 0 0 0 1px rgba(255,255,255,.22);
            font-size:13px;
            font-weight:700;
            white-space:nowrap;
        }

        /* ====== Grid de tarjetas ====== */
        .al-grid{
            display:grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap:14px;
        }

        @media (max-width: 1100px){
            .al-grid{ grid-template-columns: repeat(2, minmax(0, 1fr)); }
        }
        @media (max-width: 720px){
            .al-grid{ grid-template-columns: 1fr; }
            .al-header{ flex-direction:column; align-items:flex-start; }
        }

        /* ====== Card ====== */
        .al-card{
            position:relative;
            border-radius:18px;
            overflow:hidden;
            background:#ffffff;
            box-shadow: 0 16px 34px rgba(0,0,0,.10);
            border: 1px solid rgba(15, 23, 42, .08);
        }

        .al-card::before{
            content:"";
            position:absolute;
            inset:0;
            background: radial-gradient(600px 220px at 10% 0%, rgba(79, 70, 229, .14) 0%, rgba(255,255,255,0) 55%),
                        radial-gradient(520px 240px at 90% 0%, rgba(34, 197, 94, .12) 0%, rgba(255,255,255,0) 60%);
            pointer-events:none;
        }

        .al-card-inner{
            position:relative;
            padding:14px 14px 12px 14px;
        }

        /* ====== Top bar de la card ====== */
        .al-card-top{
            display:flex;
            justify-content:space-between;
            align-items:flex-start;
            gap:10px;
            margin-bottom:10px;
        }

        .al-time{
            font-size:16px;
            font-weight:900;
            color:#0f172a;
            margin:0;
            line-height:1.1;
        }

        .al-badges{
            display:flex;
            gap:8px;
            flex-wrap:wrap;
            margin-top:6px;
        }

        .al-badge{
            display:inline-flex;
            align-items:center;
            padding:6px 10px;
            border-radius:999px;
            font-size:12px;
            font-weight:800;
            letter-spacing:.2px;
            box-shadow: inset 0 0 0 1px rgba(15, 23, 42, .10);
            background: rgba(15, 23, 42, .04);
            color:#0f172a;
        }

        .al-badge-date{
            background: rgba(6, 182, 212, .12);
            color:#0e7490;
            box-shadow: inset 0 0 0 1px rgba(6, 182, 212, .22);
        }

        .al-badge-paid{
            background: rgba(34, 197, 94, .12);
            color:#166534;
            box-shadow: inset 0 0 0 1px rgba(34, 197, 94, .22);
        }

        .al-badge-warn{
            background: rgba(245, 158, 11, .12);
            color:#92400e;
            box-shadow: inset 0 0 0 1px rgba(245, 158, 11, .22);
        }

        /* ====== Info rows ====== */
        .al-info{
            display:flex;
            flex-direction:column;
            gap:8px;
            margin-top:10px;
        }

        .al-row{
            display:flex;
            align-items:flex-start;
            justify-content:space-between;
            gap:10px;
            padding:10px 10px;
            border-radius:14px;
            background: rgba(15, 23, 42, .03);
            box-shadow: inset 0 0 0 1px rgba(15, 23, 42, .06);
        }

        .al-label{
            font-size:12px;
            font-weight:800;
            color: rgba(15, 23, 42, .60);
            margin:0;
        }

        .al-value{
            font-size:13px;
            font-weight:900;
            color:#0f172a;
            margin:2px 0 0 0;
            text-align:right;
        }

        /* ====== Acciones ====== */
        .al-actions{
            display:flex;
            justify-content:flex-end;
            margin-top:12px;
            gap:8px;
            flex-wrap:wrap;
        }

        .al-btn{
            cursor:pointer;
            border:none;
            border-radius:14px;
            padding:10px 12px;
            font-weight:900;
            font-size:13px;
            transition: transform .08s ease, box-shadow .12s ease;
            display:inline-flex;
            align-items:center;
            gap:8px;
        }

        .al-btn:hover{
            transform: translateY(-1px);
        }

        .al-btn:active{
            transform: translateY(0px) scale(.99);
        }

        .al-btn-rate{
            color:#0f172a;
            background: linear-gradient(135deg, #fde68a 0%, #f59e0b 55%, #fb7185 100%);
            box-shadow: 0 12px 24px rgba(245, 158, 11, .25);
        }

        .al-btn-accept{
            color:#fff;
            background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%);
            box-shadow: 0 12px 24px rgba(34, 197, 94, .22);
        }

        .al-btn-reject{
            color:#0f172a;
            background:#e5e7eb;
            box-shadow: 0 12px 24px rgba(15,23,42,.08);
        }

        .al-star{
            display:inline-flex;
            width:18px;
            height:18px;
            border-radius:6px;
            background: rgba(255,255,255,.35);
            box-shadow: inset 0 0 0 1px rgba(255,255,255,.35);
            align-items:center;
            justify-content:center;
        }

        /* ====== Empty state ====== */
        .al-empty{
            border-radius:18px;
            background: linear-gradient(135deg, rgba(79, 70, 229, .10) 0%, rgba(6, 182, 212, .08) 50%, rgba(34, 197, 94, .10) 100%);
            box-shadow: 0 16px 34px rgba(0,0,0,.08);
            border: 1px solid rgba(15, 23, 42, .08);
            padding:18px;
        }

        .al-empty-title{
            margin:0;
            font-size:16px;
            font-weight:900;
            color:#0f172a;
        }

        .al-empty-sub{
            margin:6px 0 0 0;
            font-size:13px;
            color: rgba(15, 23, 42, .72);
        }

        /* ====== Sección (caja grande) ====== */
        .al-section{
            border:1px solid rgba(15,23,42,.10);
            border-radius:18px;
            overflow:hidden;
            background:#fff;
            box-shadow: 0 16px 34px rgba(0,0,0,.10);
            margin-bottom:18px;
        }

        .al-section-head{
            padding:14px 18px;
            color:#fff;
            display:flex;
            align-items:center;
            justify-content:space-between;
            gap:12px;
            flex-wrap:wrap;
        }

        .al-section-title{
            font-size:16px;
            font-weight:900;
            margin:0;
        }

        .al-section-sub{
            font-size:12px;
            opacity:.92;
            margin-top:2px;
        }

        .al-section-pill{
            display:inline-flex;
            align-items:center;
            gap:8px;
            padding:7px 10px;
            border-radius:999px;
            background:rgba(255,255,255,.18);
            font-size:12px;
            font-weight:900;
        }

        .al-section-body{
            padding:16px;
        }
    </style>

    <div class="al-dashboard-wrap">

        {{-- Header --}}
        <div class="al-header">
            <div class="al-header-left">
                <div class="al-avatar" aria-hidden="true">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none">
                        <path d="M12 2l9 5-9 5-9-5 9-5Z" stroke="white" stroke-width="2" />
                        <path d="M3 7v10l9 5 9-5V7" stroke="white" stroke-width="2" opacity=".9"/>
                    </svg>
                </div>
                <div>
                    <h1 class="al-title">Panel del Alumno</h1>
                    <p class="al-subtitle">Clases finalizadas que podés calificar</p>
                </div>
            </div>

            <div class="al-pill">
                <span style="display:inline-flex;align-items:center;gap:6px;">
                    <span style="width:8px;height:8px;border-radius:999px;background:#fff;opacity:.9;"></span>
                    Pendientes:
                </span>
                <span style="font-size:14px;font-weight:1000;">
                    {{ count($this->pendientes ?? []) }}
                </span>
            </div>
        </div>

        {{-- =========================
           INVITACIONES DE REEMPLAZO
           (requiere $this->invitacionesReemplazo)
        ========================= --}}
        @if (!empty($this->invitacionesReemplazo) && count($this->invitacionesReemplazo))
            <div class="al-section">
                <div class="al-section-head" style="background: linear-gradient(135deg, #111827 0%, #1d4ed8 55%, #0ea5e9 100%);">
                    <div>
                        <div class="al-section-title">Clases disponibles para tomar</div>
                        <div class="al-section-sub">
                            Si aceptás, se te asigna el turno y luego lo pagás.
                        </div>
                    </div>

                    <div class="al-section-pill">
                        {{ count($this->invitacionesReemplazo) }} invitación(es)
                    </div>
                </div>

                <div class="al-section-body">
                    <div class="al-grid">
                        @foreach ($this->invitacionesReemplazo as $i)
                            <div class="al-card">
                                <div class="al-card-inner">

                                    <div class="al-card-top">
                                        <div>
                                            <p class="al-time">{{ $i['hora_inicio'] }} - {{ $i['hora_fin'] }}</p>

                                            <div class="al-badges">
                                                <span class="al-badge al-badge-date">📅 {{ $i['fecha'] }}</span>
                                                <span class="al-badge al-badge-warn">⏳ Vence: {{ $i['vence'] }}</span>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="al-info">
                                        <div class="al-row">
                                            <div>
                                                <p class="al-label">Profesor</p>
                                                <p class="al-value" style="text-align:left;">{{ $i['profesor'] ?? '-' }}</p>
                                            </div>
                                        </div>

                                        <div class="al-row">
                                            <div>
                                                <p class="al-label">Materia</p>
                                                <p class="al-value" style="text-align:left;">{{ $i['materia'] ?? '-' }}</p>
                                            </div>
                                        </div>

                                        <div class="al-row">
                                            <div>
                                                <p class="al-label">Tema</p>
                                                <p class="al-value" style="text-align:left;">{{ $i['tema'] ?? 'Sin tema' }}</p>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="al-actions">
                                        <button
                                            class="al-btn al-btn-accept"
                                            type="button"
                                            wire:click="mountAction('aceptar_reemplazo', { reemplazo_id: {{ (int) ($i['id'] ?? 0) }} })"
                                        >
                                            ✅ Aceptar
                                        </button>

                                        <button
                                            class="al-btn al-btn-reject"
                                            type="button"
                                            wire:click="mountAction('rechazar_reemplazo', { reemplazo_id: {{ (int) ($i['id'] ?? 0) }} })"
                                        >
                                            ✖ Rechazar
                                        </button>
                                    </div>

                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        @endif

        {{-- =========================
           CALIFICACIONES PENDIENTES
        ========================= --}}
        @if (!empty($this->pendientes) && count($this->pendientes))
            <div class="al-grid">
                @foreach ($this->pendientes as $p)
                    <div class="al-card">
                        <div class="al-card-inner">

                            <div class="al-card-top">
                                <div>
                                    <p class="al-time">{{ $p['hora_inicio'] }} - {{ $p['hora_fin'] }}</p>

                                    <div class="al-badges">
                                        <span class="al-badge al-badge-date">📅 {{ $p['fecha'] }}</span>
                                        <span class="al-badge al-badge-paid">✅ Pagada</span>
                                    </div>
                                </div>
                            </div>

                            <div class="al-info">
                                <div class="al-row">
                                    <div>
                                        <p class="al-label">Profesor</p>
                                        <p class="al-value" style="text-align:left;">{{ $p['profesor'] }}</p>
                                    </div>
                                </div>

                                <div class="al-row">
                                    <div>
                                        <p class="al-label">Materia</p>
                                        <p class="al-value" style="text-align:left;">{{ $p['materia'] }}</p>
                                    </div>
                                </div>

                                <div class="al-row">
                                    <div>
                                        <p class="al-label">Tema</p>
                                        <p class="al-value" style="text-align:left;">{{ $p['tema'] }}</p>
                                    </div>
                                </div>
                            </div>

                            <div class="al-actions">
                                <button
                                    class="al-btn al-btn-rate"
                                    type="button"
                                    wire:click="mountAction('calificar', { turno_id: {{ $p['id'] }} })"
                                >
                                    <span class="al-star">⭐</span>
                                    Calificar
                                </button>
                            </div>

                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <div class="al-empty">
                <p class="al-empty-title">No tenés clases pendientes</p>
                <p class="al-empty-sub">
                    Cuando termine una clase pagada, te va a aparecer acá para que la califiques.
                </p>
            </div>
        @endif

        {{-- Modal de Filament Actions --}}
        <x-filament-actions::modals />

    </div>
</x-filament-panels::page>