<x-filament-panels::page>

    <div style="display:flex; flex-direction:column; gap:18px;">

        <div style="border:1px solid #e5e7eb; border-radius:14px; padding:16px; background:#fff;">
            <div style="display:flex; justify-content:space-between; align-items:center; gap:12px; flex-wrap:wrap;">
                <div>
                    <div style="font-size:18px; font-weight:900;">Reportes</div>
                    <div style="font-size:13px; color:#6b7280; margin-top:4px;">
                        Módulo de reportes del sistema. Primer bloque: reporte detallado de turnos.
                    </div>
                </div>

                <div style="display:flex; gap:10px; flex-wrap:wrap;">
                    <x-filament::button
                        tag="a"
                        :href="\App\Filament\Pages\Estadisticas::getUrl()"
                        color="gray"
                        icon="heroicon-o-arrow-left"
                    >
                        Volver a estadísticas
                    </x-filament::button>

                    <x-filament::button
                        tag="a"
                        :href="$this->getPdfUrl()"
                        color="danger"
                        icon="heroicon-o-document-arrow-down"
                    >
                        Exportar PDF
                    </x-filament::button>

                    <x-filament::button
                        tag="a"
                        :href="$this->getExcelUrl()"
                        color="success"
                        icon="heroicon-o-table-cells"
                    >
                        Exportar CSV
                    </x-filament::button>

                    <div style="font-size:12px; color:#374151; background:#f9fafb; border:1px solid #e5e7eb; padding:8px 10px; border-radius:12px;">
                        {{ $this->fechaInicio }} -> {{ $this->fechaFin }}
                    </div>
                </div>
            </div>
        </div>

        <div style="border:1px solid #e5e7eb; border-radius:14px; padding:16px; background:#fff;">
            <div style="display:flex; gap:12px; flex-wrap:wrap; align-items:end;">

                <div style="display:flex; flex-direction:column; gap:6px;">
                    <label style="font-size:12px; font-weight:900;">Desde</label>
                    <input
                        type="date"
                        wire:model.live="fechaInicio"
                        style="border:1px solid #d1d5db; border-radius:12px; padding:10px 12px; width:220px;"
                    >
                </div>

                <div style="display:flex; flex-direction:column; gap:6px;">
                    <label style="font-size:12px; font-weight:900;">Hasta</label>
                    <input
                        type="date"
                        wire:model.live="fechaFin"
                        style="border:1px solid #d1d5db; border-radius:12px; padding:10px 12px; width:220px;"
                    >
                </div>

                <div style="display:flex; flex-direction:column; gap:6px;">
                    <label style="font-size:12px; font-weight:900;">Estado</label>
                    <select
                        wire:model.live="estado"
                        style="border:1px solid #d1d5db; border-radius:12px; padding:10px 12px; width:220px;"
                    >
                        <option value="">Todos</option>
                        <option value="pendiente">Pendiente</option>
                        <option value="pendiente_pago">Pendiente de pago</option>
                        <option value="confirmado">Clase pagada</option>
                        <option value="rechazado">Rechazado</option>
                        <option value="cancelado">Cancelado</option>
                        <option value="vencido">Vencido</option>
                        <option value="aceptado">Aceptado (legacy)</option>
                    </select>
                </div>

                <div>
                    <x-filament::button
                        color="primary"
                        wire:click="aplicarFiltros"
                        wire:target="aplicarFiltros"
                        wire:loading.attr="disabled"
                        icon="heroicon-o-funnel"
                    >
                        <span wire:loading.remove wire:target="aplicarFiltros">Aplicar</span>
                        <span wire:loading wire:target="aplicarFiltros">Cargando...</span>
                    </x-filament::button>
                </div>

            </div>

            @error('fechas')
                <div style="margin-top:10px; color:#991b1b; font-weight:700;">{{ $message }}</div>
            @enderror
        </div>

        <div style="border:1px solid #e5e7eb; border-radius:14px; padding:16px; background:#fff;">
            <div style="display:flex; justify-content:space-between; align-items:center; gap:12px; flex-wrap:wrap; margin-bottom:10px;">
                <div style="font-size:16px; font-weight:900;">
                    Reporte de turnos
                </div>

                <div style="font-size:12px; color:#6b7280;">
                    {{ count($this->turnos) }} registro(s)
                </div>
            </div>

            <div style="overflow:auto;">
                <table style="width:100%; border-collapse:collapse; min-width:1250px;">
                    <thead>
                        <tr style="background:#111827; color:#fff;">
                            <th style="text-align:right; padding:10px;">ID</th>
                            <th style="text-align:left; padding:10px;">Alumno</th>
                            <th style="text-align:left; padding:10px;">Profesor</th>
                            <th style="text-align:left; padding:10px;">Materia</th>
                            <th style="text-align:left; padding:10px;">Tema</th>
                            <th style="text-align:left; padding:10px;">Fecha</th>
                            <th style="text-align:left; padding:10px;">Hora inicio</th>
                            <th style="text-align:left; padding:10px;">Hora fin</th>
                            <th style="text-align:left; padding:10px;">Estado</th>
                            <th style="text-align:right; padding:10px;">Precio total</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($this->turnos as $row)
                            @php
                                [$bg, $color] = \App\Filament\Pages\Reportes::estadoBadgeColors($row['estado']);
                                $estadoLabel = \App\Filament\Pages\Reportes::estadoLabel($row['estado']);
                            @endphp

                            <tr>
                                <td style="padding:10px; text-align:right; border-bottom:1px solid #e5e7eb;">{{ $row['id'] }}</td>
                                <td style="padding:10px; border-bottom:1px solid #e5e7eb;">{{ $row['alumno'] }}</td>
                                <td style="padding:10px; border-bottom:1px solid #e5e7eb;">{{ $row['profesor'] }}</td>
                                <td style="padding:10px; border-bottom:1px solid #e5e7eb;">{{ $row['materia'] }}</td>
                                <td style="padding:10px; border-bottom:1px solid #e5e7eb;">{{ $row['tema'] }}</td>
                                <td style="padding:10px; border-bottom:1px solid #e5e7eb;">{{ $row['fecha'] }}</td>
                                <td style="padding:10px; border-bottom:1px solid #e5e7eb;">{{ $row['hora_inicio'] }}</td>
                                <td style="padding:10px; border-bottom:1px solid #e5e7eb;">{{ $row['hora_fin'] }}</td>
                                <td style="padding:10px; border-bottom:1px solid #e5e7eb;">
                                    <span style="
                                        display:inline-flex;
                                        align-items:center;
                                        padding:6px 10px;
                                        border-radius:999px;
                                        font-size:12px;
                                        font-weight:700;
                                        background:{{ $bg }};
                                        color:{{ $color }};
                                    ">
                                        {{ $estadoLabel }}
                                    </span>
                                </td>
                                <td style="padding:10px; text-align:right; border-bottom:1px solid #e5e7eb;">
                                    ${{ number_format($row['precio_total'], 2, ',', '.') }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="10" style="padding:10px; color:#6b7280;">Sin datos.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div style="border:1px dashed #d1d5db; border-radius:14px; padding:16px; background:#fff;">
            <div style="font-size:14px; font-weight:900; margin-bottom:6px;">Próximos bloques sugeridos</div>
            <div style="font-size:13px; color:#6b7280;">
                Después podés sumar: reporte de cancelaciones, reprogramaciones y pagos.
            </div>
        </div>

    </div>

</x-filament-panels::page>