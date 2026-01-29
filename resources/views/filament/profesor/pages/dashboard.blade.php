<x-filament-panels::page>

    <style>
        /* ====== Layout general ====== */
        .dash-wrap { font-family: system-ui, -apple-system, Segoe UI, Roboto, Arial, sans-serif; }
        .dash-title { font-size: 22px; font-weight: 800; margin: 0 0 6px; }
        .dash-sub { margin: 0 0 18px; color: #6b7280; font-size: 13px; }

        /* Grilla 2 columnas */
        .grid-2 { display: grid; grid-template-columns: 1fr; gap: 16px; }
        @media (min-width: 1024px) {
            .grid-2 { grid-template-columns: 1fr 1fr; }
        }

        /* Listas simples */
        .list { margin: 0; padding-left: 18px; }
        .list li { margin: 6px 0; }

        /* ====== Calendario ====== */
        .cal-wrap {
            border: 1px solid #e5e7eb;
            border-radius: 14px;
            overflow: hidden;
            background: #fff;
        }

        .cal-head {
            padding: 12px 14px;
            border-bottom: 1px solid #e5e7eb;
            background: #f9fafb;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
        }

        .cal-head h2 {
            margin: 0;
            font-size: 16px;
            font-weight: 800;
        }

        .cal-note {
            font-size: 12px;
            color: #6b7280;
        }

        .cal-body {
            display: grid;
            grid-template-columns: 86px 1fr; /* columna horas + columna eventos */
        }

        .time-col {
            border-right: 1px solid #e5e7eb;
            background: #fcfcfd;
            position: relative;
        }

        .events-col {
            position: relative;
            background: #ffffff;
        }

        .hour-row {
            height: 72px; /* 60min * 1.2px/min = 72px */
            border-bottom: 1px solid #f1f5f9;
            position: relative;
        }

        .hour-label {
            position: absolute;
            top: 8px;
            right: 10px;
            font-size: 12px;
            color: #6b7280;
            font-weight: 600;
        }

        /* Lineas en la zona de eventos */
        .events-col .hour-row {
            border-bottom: 1px dashed #eef2f7;
        }

        /* Bloque de clase */
        .event {
            position: absolute;
            left: 10px;
            right: 10px;
            border-radius: 12px;
            border: 1px solid #c7d2fe;
            background: #eef2ff;
            padding: 10px 12px;
            box-shadow: 0 6px 18px rgba(0,0,0,.06);
            overflow: hidden;
        }

        .event-title {
            font-weight: 800;
            font-size: 13px;
            margin: 0 0 4px;
            color: #111827;
        }

        .event-meta {
            margin: 0;
            font-size: 12px;
            color: #374151;
            line-height: 1.35;
        }

        .badge {
            display: inline-block;
            margin-top: 8px;
            padding: 4px 10px;
            font-size: 11px;
            border-radius: 999px;
            background: #dcfce7;
            color: #166534;
            font-weight: 700;
            border: 1px solid #bbf7d0;
        }

        /* Empty state */
        .empty {
            padding: 16px;
            color: #374151;
        }
        .empty p { margin: 0; }
    </style>

    <div class="dash-wrap">
        <h1 class="dash-title">Bienvenido, profesor</h1>
        <p class="dash-sub">Agenda del día en formato calendario (solo clases pagadas).</p>

        {{-- Materias/Temas como tenías, pero sin Tailwind --}}
        <div class="grid-2">
            <x-filament::section>
                <x-slot name="heading">Materias que enseña</x-slot>
                @if(count($this->materias))
                    <ul class="list">
                        @foreach($this->materias as $materia)
                            <li>{{ $materia }}</li>
                        @endforeach
                    </ul>
                @else
                    <p>No tenés materias asignadas todavía.</p>
                @endif
            </x-filament::section>

            <x-filament::section>
                <x-slot name="heading">Temas que domina</x-slot>
                @if(count($this->temas))
                    <ul class="list">
                        @foreach($this->temas as $tema)
                            <li>{{ $tema }}</li>
                        @endforeach
                    </ul>
                @else
                    <p>No tenés temas seleccionados todavía.</p>
                @endif
            </x-filament::section>
        </div>

        <div style="height:16px"></div>

        {{-- Agenda tipo calendario --}}
        <x-filament::section>
            <x-slot name="heading">Agenda de hoy ({{ now()->format('d/m/Y') }})</x-slot>

            @php
                // Helpers de tiempo sin depender de Carbon en Blade
                $toMinutes = function(string $hhmm) {
                    [$h, $m] = array_map('intval', explode(':', $hhmm));
                    return $h * 60 + $m;
                };

                $startMin = $toMinutes($this->dayStart);
                $endMin   = $toMinutes($this->dayEnd);
                $pxPerMin = $this->pxPerMin;

                // Generar horas (labels) cada 1h
                $hours = [];
                for ($m = $startMin; $m <= $endMin; $m += 60) {
                    $h = str_pad((string) intdiv($m, 60), 2, '0', STR_PAD_LEFT);
                    $hours[] = "{$h}:00";
                }

                $totalHeight = ($endMin - $startMin) * $pxPerMin; // alto total de la columna eventos
            @endphp

            <div class="cal-wrap">
                <div class="cal-head">
                    <h2>Calendario del día</h2>
                    <div class="cal-note">Se muestran solo turnos “Clase pagada”.</div>
                </div>

                @if(count($this->agendaHoy))
                    <div class="cal-body">
                        {{-- Columna de horas --}}
                        <div class="time-col">
                            @foreach($hours as $h)
                                <div class="hour-row">
                                    <div class="hour-label">{{ $h }}</div>
                                </div>
                            @endforeach
                        </div>

                        {{-- Columna de eventos --}}
                        <div class="events-col" style="height: {{ $totalHeight }}px;">
                            {{-- líneas de hora --}}
                            @foreach($hours as $h)
                                <div class="hour-row"></div>
                            @endforeach

                            {{-- eventos posicionados --}}
                            @foreach($this->agendaHoy as $e)
                                @php
                                    $eStart = $toMinutes($e['inicio']);
                                    $eEnd   = $toMinutes($e['fin']);

                                    // clamp dentro del rango del día
                                    $eStart = max($eStart, $startMin);
                                    $eEnd   = min($eEnd, $endMin);

                                    $top = ($eStart - $startMin) * $pxPerMin;
                                    $height = max(40, ($eEnd - $eStart) * $pxPerMin); // mínimo 40px para que se lea

                                    $titulo = $e['materia'] . ' — ' . $e['alumno'];
                                    $tema = $e['tema'] ? "Tema: {$e['tema']}" : "Tema: —";
                                @endphp

                                <div class="event" style="top: {{ $top }}px; height: {{ $height }}px;">
                                    <p class="event-title">{{ $titulo }}</p>
                                    <p class="event-meta">
                                        Horario: <b>{{ $e['inicio'] }}</b> - <b>{{ $e['fin'] }}</b><br>
                                        {{ $tema }}
                                    </p>
                                    <span class="badge">Clase pagada</span>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @else
                    <div class="empty">
                        <p><b>No hay clases pagadas para hoy.</b></p>
                        <p>Cuando un turno quede “confirmado” (pago aprobado), aparecerá acá automáticamente.</p>
                    </div>
                @endif
            </div>
        </x-filament::section>
    </div>

</x-filament-panels::page>
