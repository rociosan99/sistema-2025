<x-filament-panels::page>
    <div style="display:flex; flex-direction:column; gap:18px;">
        <section style="border:1px solid #dbeafe; border-radius:16px; padding:20px; background:#eff6ff;">
            <div style="font-size:13px; font-weight:800; color:#1e40af;">Saldo disponible total</div>
            <div style="margin-top:6px; font-size:30px; line-height:1.1; font-weight:900; color:#1e3a8a;">
                ${{ number_format((float) $saldoDisponible, 2, ',', '.') }}
            </div>
            <p style="margin:10px 0 0; color:#475569; font-size:13px;">
                Podés aplicar este saldo al completar el pago de una clase.
            </p>
        </section>

        <section style="border:1px solid #e5e7eb; border-radius:16px; padding:18px; background:#fff;">
            <div style="font-size:17px; font-weight:900; color:#111827;">Historial de créditos</div>

            @if (empty($historial))
                <div style="margin-top:14px; padding:16px; border-radius:12px; background:#f8fafc; color:#64748b; font-size:14px;">
                    Todavía no tenés créditos registrados.
                </div>
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
                                <tr>
                                    <td style="padding:12px 11px; border-bottom:1px solid #f1f5f9; color:#0f172a;">
                                        <div style="font-weight:800;">Turno #{{ $credito['turno_id'] }}</div>
                                        <div style="margin-top:3px; color:#64748b;">
                                            {{ $credito['turno_fecha'] }} · {{ $credito['turno_horario'] }}
                                        </div>
                                    </td>
                                    <td style="padding:12px 11px; border-bottom:1px solid #f1f5f9; color:#334155;">
                                        {{ $credito['fecha'] }}
                                    </td>
                                    <td style="padding:12px 11px; border-bottom:1px solid #f1f5f9; text-align:right; color:#334155;">
                                        ${{ number_format((float) $credito['importe_pagado'], 2, ',', '.') }}
                                    </td>
                                    <td style="padding:12px 11px; border-bottom:1px solid #f1f5f9; text-align:right; font-weight:800; color:#166534;">
                                        ${{ number_format((float) $credito['importe_credito'], 2, ',', '.') }}
                                    </td>
                                    <td style="padding:12px 11px; border-bottom:1px solid #f1f5f9; text-align:right; font-weight:800; color:#1e40af;">
                                        ${{ number_format((float) $credito['saldo_disponible'], 2, ',', '.') }}
                                    </td>
                                    <td style="padding:12px 11px; border-bottom:1px solid #f1f5f9; text-align:right; color:#991b1b;">
                                        ${{ number_format((float) $credito['importe_penalizacion'], 2, ',', '.') }}
                                    </td>
                                    <td style="padding:12px 11px; border-bottom:1px solid #f1f5f9; color:#334155;">
                                        {{ $credito['vence_at'] ?? 'Sin vencimiento' }}
                                    </td>
                                    <td style="padding:12px 11px; border-bottom:1px solid #f1f5f9;">
                                        <span style="display:inline-flex; padding:5px 9px; border-radius:9999px; background:{{ $credito['estado_visual'] === 'disponible' ? '#dcfce7' : ($credito['estado_visual'] === 'esperando_pago' ? '#fef3c7' : ($credito['estado_visual'] === 'vencido' ? '#fee2e2' : '#f1f5f9')) }}; color:{{ $credito['estado_visual'] === 'disponible' ? '#166534' : ($credito['estado_visual'] === 'esperando_pago' ? '#92400e' : ($credito['estado_visual'] === 'vencido' ? '#991b1b' : '#475569')) }}; font-weight:800;">
                                            {{ $credito['estado_label'] }}
                                        </span>
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
            @endif
        </section>
    </div>
</x-filament-panels::page>
