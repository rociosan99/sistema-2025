<x-filament-panels::page>
    <style>
        @media (min-width: 1024px) {
            .mis-creditos-filters-grid {
                grid-template-columns:
                    minmax(0, 18fr)
                    minmax(0, 18fr)
                    minmax(0, 18fr)
                    minmax(0, 30fr)
                    minmax(7rem, 16fr) !important;
            }
        }
    </style>

    <div style="display:flex; flex-direction:column; gap:18px;">
        <section style="border:1px solid #dbeafe; border-radius:16px; padding:20px; background:#eff6ff;">
            <div style="font-size:13px; font-weight:800; color:#1e40af;">Saldo disponible total</div>
            <div style="margin-top:6px; font-size:30px; line-height:1.1; font-weight:900; color:#1e3a8a;">
                ${{ number_format((float) $saldoDisponible, 2, ',', '.') }}
            </div>
            <p style="margin:10px 0 0; color:#475569; font-size:13px;">Podés aplicar este saldo al completar el pago de una clase.</p>
        </section>

        <section style="border:1px solid #e5e7eb; border-radius:16px; padding:18px; background:#fff;">
            <form wire:submit="aplicarFiltros">
                <div style="display:flex; justify-content:space-between; align-items:center; gap:12px; margin-bottom:14px;">
                    <div style="font-size:16px; font-weight:900; color:#111827;">Filtros</div>
                    <button type="button" wire:click="resetearFiltros" style="border:0; padding:0; background:transparent; color:#dc2626; font-size:13px; font-weight:700; cursor:pointer;">Resetear filtros</button>
                </div>

                <div class="mis-creditos-filters-grid" style="display:grid; grid-template-columns:repeat(auto-fit, minmax(150px, 1fr)); gap:12px; align-items:end;">
                    <div style="display:flex; flex-direction:column; gap:6px;">
                        <label for="credito-estado" style="font-size:13px; font-weight:700; color:#374151;">Estado</label>
                        <select id="credito-estado" wire:model="estado" style="width:100%; min-height:42px; border:1px solid #d1d5db; border-radius:10px; padding:8px 10px; background:#fff;">
                            <option value="">Todos</option>
                            @foreach ($estadoOptions as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div style="display:flex; flex-direction:column; gap:6px;">
                        <label for="credito-desde" style="font-size:13px; font-weight:700; color:#374151;">Desde</label>
                        <input id="credito-desde" type="date" wire:model="desde" style="width:100%; min-height:42px; border:1px solid #d1d5db; border-radius:10px; padding:8px 10px;">
                    </div>

                    <div style="display:flex; flex-direction:column; gap:6px;">
                        <label for="credito-hasta" style="font-size:13px; font-weight:700; color:#374151;">Hasta</label>
                        <input id="credito-hasta" type="date" wire:model="hasta" style="width:100%; min-height:42px; border:1px solid #d1d5db; border-radius:10px; padding:8px 10px;">
                    </div>

                    <div style="display:flex; flex-direction:column; gap:6px;">
                        <label for="credito-buscar" style="font-size:13px; font-weight:700; color:#374151;">Buscar</label>
                        <input id="credito-buscar" type="search" wire:model="buscar" placeholder="Buscar por número de turno..." style="width:100%; min-height:42px; border:1px solid #d1d5db; border-radius:10px; padding:8px 10px;">
                    </div>

                    <div style="display:flex; flex-direction:column; gap:6px;">
                        <span aria-hidden="true" style="font-size:13px; font-weight:700; visibility:hidden;">Acción</span>
                        <x-filament::button type="submit" style="width:100%; min-height:42px; justify-content:center;">Aplicar filtros</x-filament::button>
                    </div>
                </div>

                @error('hasta')
                    <div style="margin-top:10px; color:#b91c1c; font-size:13px; font-weight:700;">{{ $message }}</div>
                @enderror
            </form>
        </section>

        <section style="border:1px solid #e5e7eb; border-radius:16px; padding:18px; background:#fff;">
            <div style="display:flex; justify-content:space-between; align-items:end; gap:12px; flex-wrap:wrap;">
                <div>
                    <div style="font-size:17px; font-weight:900; color:#111827;">Historial de créditos</div>
                    <div style="margin-top:4px; color:#64748b; font-size:12px;">{{ $historial->total() }} registro(s)</div>
                </div>

                <div style="display:flex; align-items:center; gap:8px;">
                    <label for="creditos-por-pagina" style="font-size:13px; font-weight:700; color:#475569;">Mostrar</label>
                    <select id="creditos-por-pagina" wire:model.live="porPagina" style="min-height:38px; border:1px solid #d1d5db; border-radius:9px; padding:6px 28px 6px 9px; background:#fff;">
                        <option value="10">10</option>
                        <option value="25">25</option>
                        <option value="50">50</option>
                    </select>
                </div>
            </div>

            @if ($historial->isEmpty())
                <div style="margin-top:14px; padding:16px; border-radius:12px; background:#f8fafc; color:#64748b; font-size:14px;">No hay créditos que coincidan con los filtros seleccionados.</div>
            @else
                <div style="margin-top:14px; overflow-x:auto;">
                    <table style="width:100%; border-collapse:collapse; min-width:980px; font-size:13px;">
                        <thead>
                            <tr style="background:#f8fafc; color:#475569; text-align:left;">
                                <th style="padding:11px; border-bottom:1px solid #e5e7eb;">Turno de origen</th>
                                <th style="padding:11px; border-bottom:1px solid #e5e7eb;">Cancelación</th>
                                <th style="padding:11px; border-bottom:1px solid #e5e7eb; text-align:right;">Pagado</th>
                                <th style="padding:11px; border-bottom:1px solid #e5e7eb; text-align:right;">Acreditado</th>
                                <th style="padding:11px; border-bottom:1px solid #e5e7eb; text-align:right;">Saldo disponible</th>
                                <th style="padding:11px; border-bottom:1px solid #e5e7eb; text-align:right;">Penalización</th>
                                <th style="padding:11px; border-bottom:1px solid #e5e7eb;">Vencimiento</th>
                                <th style="padding:11px; border-bottom:1px solid #e5e7eb;">Estado</th>
                                <th style="padding:11px; border-bottom:1px solid #e5e7eb;">Regla aplicada</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($historial as $credito)
                                <tr data-testid="credito-row-{{ $credito['id'] }}">
                                    <td style="padding:12px 11px; border-bottom:1px solid #f1f5f9; color:#0f172a;">
                                        <div style="font-weight:800;">Turno #{{ $credito['turno_id'] }}</div>
                                        <div style="margin-top:3px; color:#64748b;">{{ $credito['turno_fecha'] }} · {{ $credito['turno_horario'] }}</div>
                                    </td>
                                    <td style="padding:12px 11px; border-bottom:1px solid #f1f5f9; color:#334155;">{{ $credito['fecha'] }}</td>
                                    <td style="padding:12px 11px; border-bottom:1px solid #f1f5f9; text-align:right; color:#334155;">${{ number_format((float) $credito['importe_pagado'], 2, ',', '.') }}</td>
                                    <td style="padding:12px 11px; border-bottom:1px solid #f1f5f9; text-align:right; font-weight:800; color:#166534;">${{ number_format((float) $credito['importe_credito'], 2, ',', '.') }}</td>
                                    <td style="padding:12px 11px; border-bottom:1px solid #f1f5f9; text-align:right; font-weight:800; color:#1e40af;">${{ number_format((float) $credito['saldo_disponible'], 2, ',', '.') }}</td>
                                    <td style="padding:12px 11px; border-bottom:1px solid #f1f5f9; text-align:right; color:#991b1b;">${{ number_format((float) $credito['importe_penalizacion'], 2, ',', '.') }}</td>
                                    <td style="padding:12px 11px; border-bottom:1px solid #f1f5f9; color:#334155;">{{ $credito['vence_at'] ?? 'Sin vencimiento' }}</td>
                                    <td style="padding:12px 11px; border-bottom:1px solid #f1f5f9;">
                                        <span style="display:inline-flex; padding:5px 9px; border-radius:9999px; background:{{ $credito['estado_visual'] === 'disponible' ? '#dcfce7' : ($credito['estado_visual'] === 'esperando_pago' ? '#fef3c7' : ($credito['estado_visual'] === 'vencido' ? '#fee2e2' : '#f1f5f9')) }}; color:{{ $credito['estado_visual'] === 'disponible' ? '#166534' : ($credito['estado_visual'] === 'esperando_pago' ? '#92400e' : ($credito['estado_visual'] === 'vencido' ? '#991b1b' : '#475569')) }}; font-weight:800;">{{ $credito['estado_label'] }}</span>
                                    </td>
                                    <td style="padding:12px 11px; border-bottom:1px solid #f1f5f9; color:#475569;">
                                        <div>Crédito: {{ number_format((float) $credito['porcentaje_credito'], 2, ',', '.') }}%</div>
                                        <div>Penalización: {{ number_format((float) $credito['porcentaje_penalizacion'], 2, ',', '.') }}%</div>
                                        <div>Límite sin penalización: {{ $credito['horas_limite'] }} h</div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                @if ($historial->hasPages())
                    <div style="margin-top:16px;">{{ $historial->links() }}</div>
                @endif
            @endif
        </section>
    </div>
</x-filament-panels::page>
