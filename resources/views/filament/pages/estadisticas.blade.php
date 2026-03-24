<x-filament-panels::page>
    <div style="display:flex; flex-direction:column; gap:18px;">

        {{-- Header --}}
        <div style="border:1px solid #e5e7eb; border-radius:16px; padding:18px; background:#fff;">
            <div style="display:flex; justify-content:space-between; align-items:center; gap:12px; flex-wrap:wrap;">
                <div>
                    <div style="font-size:22px; font-weight:900;">Módulo Estadístico</div>
                    <div style="font-size:13px; color:#6b7280; margin-top:4px;">
                        Analizá turnos por materia, tema, estado o profesor según el rango seleccionado.
                    </div>
                </div>

                <div style="display:flex; gap:10px; align-items:center; flex-wrap:wrap;">
                    <x-filament::button
                        tag="a"
                        :href="\App\Filament\Pages\Reportes::getUrl()"
                        color="info"
                        icon="heroicon-o-document-text"
                    >
                        Ver reportes
                    </x-filament::button>

                    <x-filament::button
                        tag="a"
                        :href="\App\Filament\Pages\OptimizacionPrecios::getUrl()"
                        color="success"
                        icon="heroicon-o-currency-dollar"
                    >
                        Ver optimización de precios
                    </x-filament::button>

                    <div style="font-size:12px; color:#374151; background:#f9fafb; border:1px solid #e5e7eb; padding:8px 12px; border-radius:12px;">
                        {{ $this->fechaInicio }} → {{ $this->fechaFin }}
                    </div>
                </div>
            </div>
        </div>

        {{-- Filtros --}}
        <div style="border:1px solid #e5e7eb; border-radius:16px; padding:18px; background:#fff;">
            <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(220px, 1fr)); gap:14px;">

                <div style="display:flex; flex-direction:column; gap:6px;">
                    <label style="font-size:12px; font-weight:800;">Agrupar por</label>
                    <select
                        wire:model.live="dimension"
                        style="border:1px solid #d1d5db; border-radius:12px; padding:10px 12px;"
                    >
                        <option value="materias">Materias</option>
                        <option value="temas">Temas</option>
                        <option value="estados">Estados</option>
                        <option value="profesores">Profesores</option>
                    </select>
                </div>

                @if($dimension !== 'estados')
                    <div style="display:flex; flex-direction:column; gap:6px;">
                        <label style="font-size:12px; font-weight:800;">Mostrar</label>
                        <select
                            wire:model.live="metrica"
                            style="border:1px solid #d1d5db; border-radius:12px; padding:10px 12px;"
                        >
                            <option value="solicitados">Solicitudes</option>
                            <option value="confirmados">Confirmados</option>
                            <option value="cancelados">Cancelados</option>
                            <option value="vencidos">Vencidos</option>
                        </select>
                    </div>
                @else
                    <div style="display:flex; flex-direction:column; gap:6px;">
                        <label style="font-size:12px; font-weight:800;">Mostrar</label>
                        <div style="border:1px solid #e5e7eb; border-radius:12px; padding:10px 12px; background:#f9fafb; color:#6b7280;">
                            Cantidad total por estado
                        </div>
                    </div>
                @endif

                <div style="display:flex; flex-direction:column; gap:6px;">
                    <label style="font-size:12px; font-weight:800;">Desde</label>
                    <input
                        type="date"
                        wire:model.live="fechaInicio"
                        style="border:1px solid #d1d5db; border-radius:12px; padding:10px 12px;"
                    >
                </div>

                <div style="display:flex; flex-direction:column; gap:6px;">
                    <label style="font-size:12px; font-weight:800;">Hasta</label>
                    <input
                        type="date"
                        wire:model.live="fechaFin"
                        style="border:1px solid #d1d5db; border-radius:12px; padding:10px 12px;"
                    >
                </div>
            </div>

            <div style="margin-top:12px; font-size:12px; color:#6b7280;">
                La vista se actualiza automáticamente al cambiar filtros.
            </div>

            @error('fechas')
                <div style="margin-top:10px; color:#991b1b; font-weight:700;">
                    {{ $message }}
                </div>
            @enderror
        </div>

        {{-- Resumen --}}
        <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(180px, 1fr)); gap:14px;">
            <div style="border:1px solid #e5e7eb; border-radius:16px; padding:16px; background:#fff;">
                <div style="font-size:12px; color:#6b7280; font-weight:700;">Total de turnos</div>
                <div style="font-size:28px; font-weight:900; margin-top:6px;">
                    {{ $resumen['total'] ?? 0 }}
                </div>
            </div>

            <div style="border:1px solid #e5e7eb; border-radius:16px; padding:16px; background:#fff;">
                <div style="font-size:12px; color:#6b7280; font-weight:700;">Confirmados</div>
                <div style="font-size:28px; font-weight:900; margin-top:6px;">
                    {{ $resumen['confirmados'] ?? 0 }}
                </div>
            </div>

            <div style="border:1px solid #e5e7eb; border-radius:16px; padding:16px; background:#fff;">
                <div style="font-size:12px; color:#6b7280; font-weight:700;">Cancelados</div>
                <div style="font-size:28px; font-weight:900; margin-top:6px;">
                    {{ $resumen['cancelados'] ?? 0 }}
                </div>
            </div>

            <div style="border:1px solid #e5e7eb; border-radius:16px; padding:16px; background:#fff;">
                <div style="font-size:12px; color:#6b7280; font-weight:700;">Vencidos</div>
                <div style="font-size:28px; font-weight:900; margin-top:6px;">
                    {{ $resumen['vencidos'] ?? 0 }}
                </div>
            </div>
        </div>

        {{-- Gráfico --}}
        <div style="border:1px solid #e5e7eb; border-radius:16px; padding:16px; background:#fff;">
            <div style="display:flex; justify-content:space-between; align-items:center; gap:10px; flex-wrap:wrap; margin-bottom:12px;">
                <div style="font-size:16px; font-weight:900;">
                    {{ $this->tituloGrafico }}
                </div>

                <div wire:loading.delay style="font-size:12px; color:#6b7280;">
                    Actualizando...
                </div>
            </div>

            <div
                id="chartDataMain"
                data-labels='@json($this->chartLabels)'
                data-values='@json($this->chartValues)'
                data-title='@json($this->tituloGrafico)'
                style="display:none;"
            ></div>

            <div wire:ignore>
                <canvas id="chartMain" style="max-width:100%; height:340px;"></canvas>
            </div>
        </div>

        {{-- Tabla --}}
        <div style="border:1px solid #e5e7eb; border-radius:16px; padding:16px; background:#fff;">
            <div style="font-size:16px; font-weight:900; margin-bottom:12px;">
                Detalle
            </div>

            <div style="overflow:auto;">
                <table style="width:100%; border-collapse:collapse; min-width:860px;">
                    <thead>
                        <tr style="background:#111827; color:#fff;">
                            @foreach($columnas as $col)
                                @php
                                    $label = match($col) {
                                        'nombre' => 'Nombre',
                                        'email' => 'Email',
                                        'solicitados' => 'Solicitudes',
                                        'confirmados' => 'Confirmados',
                                        'cancelados' => 'Cancelados',
                                        'vencidos' => 'Vencidos',
                                        'total' => 'Cantidad',
                                        default => ucfirst(str_replace('_', ' ', $col)),
                                    };

                                    $alignRight = in_array($col, ['solicitados', 'confirmados', 'cancelados', 'vencidos', 'total']);
                                @endphp

                                <th style="padding:10px; text-align:{{ $alignRight ? 'right' : 'left' }};">
                                    {{ $label }}
                                </th>
                            @endforeach
                        </tr>
                    </thead>

                    <tbody>
                        @forelse($tabla as $row)
                            <tr>
                                @foreach($columnas as $col)
                                    @php
                                        $alignRight = in_array($col, ['solicitados', 'confirmados', 'cancelados', 'vencidos', 'total']);
                                    @endphp

                                    <td style="padding:10px; border-bottom:1px solid #e5e7eb; text-align:{{ $alignRight ? 'right' : 'left' }};">
                                        {{ $row[$col] ?? '-' }}
                                    </td>
                                @endforeach
                            </tr>
                        @empty
                            <tr>
                                <td colspan="{{ count($columnas) }}" style="padding:12px; color:#6b7280;">
                                    Sin datos en el rango seleccionado.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

    </div>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <script>
        function readChartDataMain() {
            const el = document.getElementById('chartDataMain');

            if (!el) {
                return { labels: [], values: [], title: '' };
            }

            let labels = [];
            let values = [];
            let title = '';

            try { labels = JSON.parse(el.dataset.labels || '[]'); } catch (e) { labels = []; }
            try { values = JSON.parse(el.dataset.values || '[]'); } catch (e) { values = []; }
            try { title = JSON.parse(el.dataset.title || '""'); } catch (e) { title = ''; }

            return { labels, values, title };
        }

        function renderMainChart() {
            const data = readChartDataMain();
            const canvas = document.getElementById('chartMain');

            if (!canvas) return;

            if (window._chartMain) {
                window._chartMain.destroy();
            }

            window._chartMain = new Chart(canvas.getContext('2d'), {
                type: 'bar',
                data: {
                    labels: data.labels,
                    datasets: [{
                        label: data.title,
                        data: data.values,
                        backgroundColor: 'rgba(139, 92, 246, 0.45)',
                        borderColor: 'rgba(139, 92, 246, 1)',
                        borderWidth: 1,
                        borderRadius: 6
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: true
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                precision: 0
                            }
                        }
                    }
                }
            });
        }

        document.addEventListener('DOMContentLoaded', () => {
            renderMainChart();

            if (window.Livewire) {
                Livewire.on('estadisticas-actualizadas', () => {
                    setTimeout(() => renderMainChart(), 80);
                });
            }
        });
    </script>
</x-filament-panels::page>