<x-filament-panels::page>
    <div style="display:flex; flex-direction:column; gap:18px; max-width:980px;">

        @if($this->errorMensaje)
            <div style="border:1px solid #fecaca; background:#fef2f2; color:#991b1b; padding:14px; border-radius:12px;">
                <strong>⚠️ No se puede reprogramar</strong>
                <div style="margin-top:6px;">{{ $this->errorMensaje }}</div>
            </div>
        @endif

        @if($this->turnoOriginal)
            <div style="border:1px solid #e5e7eb; border-radius:14px; padding:18px; background:#fff;">
                <div style="font-size:16px; font-weight:900; color:#111827;">
                    Reprogramar clase
                </div>

                <p style="margin:8px 0 0 0; font-size:13px; color:#6b7280; line-height:1.6;">
                    Turno original:
                    <strong>{{ $this->turnoOriginal->fecha->format('d/m/Y') }}</strong>
                    ({{ substr((string)$this->turnoOriginal->hora_inicio,0,5) }} - {{ substr((string)$this->turnoOriginal->hora_fin,0,5) }})
                    • Materia: <strong>{{ $this->turnoOriginal->materia?->materia_nombre }}</strong>
                    @if($this->turnoOriginal->tema)
                        • Tema: <strong>{{ $this->turnoOriginal->tema->tema_nombre }}</strong>
                    @endif
                    • Estado: <strong>{{ $this->turnoOriginal->estado }}</strong>
                </p>
            </div>

            <div style="border:1px solid #e5e7eb; border-radius:14px; padding:18px; background:#fff;">
                <label style="font-size:13px; font-weight:900; color:#111827;">Nueva fecha</label>

                <div style="display:flex; gap:10px; align-items:center; margin-top:8px;">
                    <input
                        type="date"
                        wire:model.live="fecha"
                        style="width:260px; border-radius:12px; border:1px solid #d1d5db; padding:10px 12px; font-size:14px; background:#fff;"
                    />

                    <x-filament::button
                        color="primary"
                        wire:click="consultar"
                        wire:target="consultar"
                        wire:loading.attr="disabled"
                        icon="heroicon-o-magnifying-glass"
                    >
                        <span wire:loading.remove wire:target="consultar">Consultar horarios</span>
                        <span wire:loading wire:target="consultar">Buscando…</span>
                    </x-filament::button>
                </div>

                <p style="margin:10px 0 0 0; font-size:11px; color:#6b7280;">
                    Elegí una fecha para ver horarios disponibles según la materia/tema del turno original.
                </p>
            </div>

            @if(!empty($slots))
                <div style="border:1px solid #e5e7eb; border-radius:14px; overflow:hidden; background:#fff;">
                    <div style="padding:14px 18px; background:#111827; color:#fff; font-weight:900;">
                        Horarios disponibles ({{ count($slots) }})
                    </div>

                    <div style="padding:18px;">
                        <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(300px, 1fr)); gap:14px;">
                            @foreach($slots as $i => $s)
                                <div style="border:1px solid #e5e7eb; border-radius:14px; padding:14px; background:#fff;">
                                    <div style="font-size:15px; font-weight:900; color:#111827;">
                                        {{ $s['desde'] }} – {{ $s['hasta'] }}
                                    </div>

                                    <div style="margin-top:6px; font-size:13px; color:#374151;">
                                        Profesor: <strong>{{ $s['profesor_nombre'] ?? 'Profesor' }}</strong>
                                    </div>

                                    @if(!empty($s['precio_total']))
                                        <div style="margin-top:8px; font-size:12px; color:#111827;">
                                            Precio: <strong>${{ number_format((float)$s['precio_total'], 0, ',', '.') }}</strong>
                                        </div>
                                    @endif

                                    <div style="margin-top:12px;">
                                        <x-filament::button
                                            size="sm"
                                            class="w-full"
                                            wire:click="reprogramar({{ $i }})"
                                            wire:target="reprogramar({{ $i }})"
                                            wire:loading.attr="disabled"
                                        >
                                            <span wire:loading.remove wire:target="reprogramar({{ $i }})">Elegir este horario</span>
                                            <span wire:loading wire:target="reprogramar({{ $i }})">Reprogramando…</span>
                                        </x-filament::button>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            @elseif($this->fecha)
                <div style="border:1px solid #e5e7eb; border-radius:14px; padding:16px; background:#fff; color:#6b7280;">
                    No hay horarios disponibles para esa fecha.
                </div>
            @endif
        @endif

    </div>
</x-filament-panels::page>