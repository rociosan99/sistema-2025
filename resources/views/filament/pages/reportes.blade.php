<x-filament-panels::page>
    <style>
        .reporte-print-only { display: none !important; }

        @media print {
            @page { size: landscape; margin: 12mm; }
            body * { visibility: hidden !important; }
            .reporte-documento, .reporte-documento * { visibility: visible !important; }
            .reporte-documento {
                position: absolute; inset: 0; width: 100%; border: 0 !important;
                box-shadow: none !important; padding: 0 !important;
                color: #111827 !important; background: #fff !important;
            }
            .reporte-screen-only { display: none !important; }
            .reporte-print-only { display: block !important; }
            .reporte-tabla-contenedor { overflow: visible !important; }
            .reporte-tabla { min-width: 0 !important; font-size: 9px; }
            .reporte-tabla th, .reporte-tabla td { padding: 5px !important; }
            .reporte-fila { break-inside: avoid; }
        }
    </style>

    <div style="display:flex; flex-direction:column; gap:18px;">
        <div style="border:1px solid #e5e7eb; border-radius:14px; padding:16px; background:#fff;">
            <div style="display:flex; justify-content:space-between; align-items:center; gap:12px; flex-wrap:wrap;">
                <div>
                    <div style="font-size:18px; font-weight:900;">Reportes</div>
                    <div style="font-size:13px; color:#6b7280; margin-top:4px;">Módulo de reportes del sistema. Reporte detallado de turnos.</div>
                </div>

                <div style="display:flex; gap:10px; flex-wrap:wrap;">
                    <x-filament::button tag="a" :href="\App\Filament\Pages\Estadisticas::getUrl()" color="gray" icon="heroicon-o-arrow-left">Volver a estadísticas</x-filament::button>
                    <x-filament::button tag="a" :href="$this->getPdfUrl()" color="danger" icon="heroicon-o-document-arrow-down">Exportar PDF</x-filament::button>
                    <x-filament::button tag="a" :href="$this->getExcelUrl()" color="success" icon="heroicon-o-table-cells">Exportar CSV</x-filament::button>
                    <x-filament::button color="primary" icon="heroicon-o-printer" wire:click="prepararImpresion" wire:target="prepararImpresion" wire:loading.attr="disabled">
                        <span wire:loading.remove wire:target="prepararImpresion">Imprimir</span>
                        <span wire:loading wire:target="prepararImpresion">Preparando...</span>
                    </x-filament::button>
                    <div style="font-size:12px; color:#374151; background:#f9fafb; border:1px solid #e5e7eb; padding:8px 10px; border-radius:12px;">
                        {{ $this->formatearFecha($this->fechaInicio) }} → {{ $this->formatearFecha($this->fechaFin) }}
                    </div>
                </div>
            </div>
        </div>

        <div style="border:1px solid #e5e7eb; border-radius:14px; padding:16px; background:#fff;">
            <div style="display:flex; gap:12px; flex-wrap:wrap; align-items:end;">
                <div style="display:flex; flex-direction:column; gap:6px;">
                    <label style="font-size:12px; font-weight:900;">Desde</label>
                    <input type="date" wire:model.live="fechaInicio" style="border:1px solid #d1d5db; border-radius:12px; padding:10px 12px; width:220px;">
                </div>
                <div style="display:flex; flex-direction:column; gap:6px;">
                    <label style="font-size:12px; font-weight:900;">Hasta</label>
                    <input type="date" wire:model.live="fechaFin" style="border:1px solid #d1d5db; border-radius:12px; padding:10px 12px; width:220px;">
                </div>
                <div style="display:flex; flex-direction:column; gap:6px;">
                    <label style="font-size:12px; font-weight:900;">Estado</label>
                    <select wire:model.live="estado" style="border:1px solid #d1d5db; border-radius:12px; padding:10px 12px; width:220px;">
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
                <x-filament::button color="primary" wire:click="aplicarFiltros" wire:target="aplicarFiltros" wire:loading.attr="disabled" icon="heroicon-o-funnel">
                    <span wire:loading.remove wire:target="aplicarFiltros">Aplicar</span>
                    <span wire:loading wire:target="aplicarFiltros">Cargando...</span>
                </x-filament::button>
            </div>

            @error('fechas')
                <div style="margin-top:10px; color:#991b1b; font-weight:700;">{{ $message }}</div>
            @enderror
        </div>

        <div class="reporte-documento" style="border:1px solid #e5e7eb; border-radius:14px; padding:16px; background:#fff;">
            <header class="reporte-print-only" style="text-align:center; border-bottom:2px solid #111827; padding-bottom:14px; margin-bottom:16px;">
                <div style="font-size:20px; font-weight:800; text-transform:uppercase; letter-spacing:.04em;">{{ config('app.name') }}</div>
                <div style="font-size:16px; font-weight:700; margin-top:5px;">REPORTE DE TURNOS</div>
            </header>

            <section class="reporte-print-only" style="display:grid; grid-template-columns:1fr 1fr; gap:6px 24px; margin-bottom:16px; font-size:11px;">
                <div><strong>Reporte:</strong> Reporte de turnos</div>
                <div><strong>Período analizado:</strong> {{ $this->periodoAnalizado() }}</div>
                <div><strong>Emitido por:</strong> {{ $this->emitidoPor() }}</div>
                <div><strong>Rol:</strong> Administrador</div>
                <div><strong>Fecha de emisión:</strong> {{ $this->fechaEmision() }}</div>
                <div><strong>Hora de emisión:</strong> {{ $this->horaEmision() }}</div>
            </section>

            <section class="reporte-print-only" style="border:1px solid #d1d5db; padding:10px; margin-bottom:14px; font-size:11px;">
                <div style="font-weight:800; margin-bottom:6px;">CRITERIOS DEL REPORTE</div>
                <div><strong>Período:</strong> {{ $this->periodoAnalizado() }}</div>
                <div><strong>Estado:</strong> {{ $this->estadoAplicado() }}</div>
            </section>

            <section class="reporte-print-only" style="border:1px solid #d1d5db; padding:10px; margin-bottom:14px; font-size:11px;">
                <div style="font-weight:800; margin-bottom:6px;">RESUMEN</div>
                <div><strong>Total de registros:</strong> {{ count($this->turnos) }}</div>
            </section>

            <div class="reporte-screen-only" style="display:flex; justify-content:space-between; align-items:center; gap:12px; flex-wrap:wrap; margin-bottom:10px;">
                <div style="font-size:16px; font-weight:900;">Reporte de turnos</div>
                <div style="font-size:12px; color:#6b7280;">{{ count($this->turnos) }} registro(s)</div>
            </div>

            <div class="reporte-tabla-contenedor" style="overflow:auto;">
                <table class="reporte-tabla" style="width:100%; border-collapse:collapse; min-width:1250px;">
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
                            <tr class="reporte-fila">
                                <td style="padding:10px; text-align:right; border-bottom:1px solid #e5e7eb;">{{ $row['id'] }}</td>
                                <td style="padding:10px; border-bottom:1px solid #e5e7eb;">{{ $row['alumno'] }}</td>
                                <td style="padding:10px; border-bottom:1px solid #e5e7eb;">{{ $row['profesor'] }}</td>
                                <td style="padding:10px; border-bottom:1px solid #e5e7eb;">{{ $row['materia'] }}</td>
                                <td style="padding:10px; border-bottom:1px solid #e5e7eb;">{{ $row['tema'] }}</td>
                                <td style="padding:10px; border-bottom:1px solid #e5e7eb;">{{ $this->formatearFecha($row['fecha']) }}</td>
                                <td style="padding:10px; border-bottom:1px solid #e5e7eb;">{{ $row['hora_inicio'] }}</td>
                                <td style="padding:10px; border-bottom:1px solid #e5e7eb;">{{ $row['hora_fin'] }}</td>
                                <td style="padding:10px; border-bottom:1px solid #e5e7eb;">
                                    <span style="display:inline-flex; align-items:center; padding:6px 10px; border-radius:999px; font-size:12px; font-weight:700; background:{{ $bg }}; color:{{ $color }};">{{ $estadoLabel }}</span>
                                </td>
                                <td style="padding:10px; text-align:right; border-bottom:1px solid #e5e7eb;">${{ number_format($row['precio_total'], 2, ',', '.') }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="10" style="padding:10px; color:#6b7280;">Sin datos.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <footer class="reporte-print-only" style="border-top:1px solid #9ca3af; margin-top:16px; padding-top:8px; font-size:9px; color:#4b5563; text-align:center;">
                Documento generado por {{ config('app.name') }} · Fecha y hora de emisión: {{ $this->fechaEmision() }} {{ $this->horaEmision() }} · Emitido por: {{ $this->emitidoPor() }}
            </footer>
        </div>

        <div style="border:1px dashed #d1d5db; border-radius:14px; padding:16px; background:#fff;">
            <div style="font-size:14px; font-weight:900; margin-bottom:6px;">Próximos bloques sugeridos</div>
            <div style="font-size:13px; color:#6b7280;">Después podés sumar: reporte de cancelaciones, reprogramaciones y pagos.</div>
        </div>
    </div>

    @script
        <script>
            $wire.on('imprimir-reporte', () => requestAnimationFrame(() => window.print()))
        </script>
    @endscript
</x-filament-panels::page>
