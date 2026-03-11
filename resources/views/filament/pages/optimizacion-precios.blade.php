<x-filament-panels::page>

    <div style="display:flex; flex-direction:column; gap:18px;">

        {{-- Header --}}
        <div style="border:1px solid #e5e7eb; border-radius:14px; padding:16px; background:#fff;">
            <div style="display:flex; justify-content:space-between; align-items:center; gap:12px; flex-wrap:wrap;">
                <div>
                    <div style="font-size:18px; font-weight:900;">Optimización de precios</div>
                    <div style="font-size:13px; color:#6b7280; margin-top:4px;">
                        Recomendación de precio por profesor y materia según clases confirmadas y calificaciones.
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

                    <div style="font-size:12px; color:#374151; background:#f9fafb; border:1px solid #e5e7eb; padding:8px 10px; border-radius:12px;">
                        {{ $this->fechaInicio }} → {{ $this->fechaFin }}
                    </div>
                </div>
            </div>
        </div>

        {{-- Explicación --}}
        <div style="border:1px solid #e5e7eb; border-radius:14px; padding:16px; background:#fff;">
            <div style="font-size:14px; font-weight:900; margin-bottom:10px;">Criterio usado</div>
            <div style="font-size:14px; color:#374151; line-height:1.6;">
                Se recomienda subir el precio cuando el profesor tiene buen promedio de estrellas y buena cantidad de clases confirmadas.
                Se mantiene cuando el rendimiento es estable.
                Se recomienda bajar cuando tiene pocas clases y baja valoración.
            </div>
        </div>

        {{-- Filtros --}}
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

                <div>
                    <x-filament::button
                        color="primary"
                        wire:click="aplicarFiltros"
                        wire:target="aplicarFiltros"
                        wire:loading.attr="disabled"
                        icon="heroicon-o-funnel"
                    >
                        <span wire:loading.remove wire:target="aplicarFiltros">Aplicar</span>
                        <span wire:loading wire:target="aplicarFiltros">Cargando…</span>
                    </x-filament::button>
                </div>

            </div>

            @error('fechas')
                <div style="margin-top:10px; color:#991b1b; font-weight:700;">{{ $message }}</div>
            @enderror
        </div>

        {{-- Gráfico --}}
        <div style="border:1px solid #e5e7eb; border-radius:14px; padding:16px; background:#fff;">
            <div style="font-size:16px; font-weight:900; margin-bottom:10px;">
                Comparación de precio actual vs sugerido (Top 10)
            </div>

            <div
                id="chartDataOptimizacion"
                data-labels='@json($this->chartLabels)'
                data-actual='@json($this->chartPrecioActual)'
                data-sugerido='@json($this->chartPrecioSugerido)'
                style="display:none;"
            ></div>

            <div wire:ignore>
                <canvas id="chartOptimizacion" style="max-width:100%; height:360px;"></canvas>
            </div>
        </div>

        {{-- Tabla --}}
        <div style="border:1px solid #e5e7eb; border-radius:14px; padding:16px; background:#fff;">
            <div style="font-size:16px; font-weight:900; margin-bottom:10px;">
                Recomendación por profesor y materia
            </div>

            <div style="margin-top:16px; overflow:auto;">
                <table style="width:100%; border-collapse:collapse; min-width:1100px;">
                    <thead>
                        <tr style="background:#111827; color:#fff;">
                            <th style="text-align:left; padding:10px;">Profesor</th>
                            <th style="text-align:left; padding:10px;">Email</th>
                            <th style="text-align:left; padding:10px;">Materia</th>
                            <th style="text-align:right; padding:10px;">Precio actual</th>
                            <th style="text-align:right; padding:10px;">Clases confirmadas</th>
                            <th style="text-align:right; padding:10px;">Promedio estrellas</th>
                            <th style="text-align:right; padding:10px;">Reseñas</th>
                            <th style="text-align:right; padding:10px;">Precio sugerido</th>
                            <th style="text-align:left; padding:10px;">Recomendación</th>
                            <th style="text-align:left; padding:10px;">Ajuste</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($this->registros as $row)
                            <tr>
                                <td style="padding:10px; border-bottom:1px solid #e5e7eb;">{{ $row['profesor'] }}</td>
                                <td style="padding:10px; border-bottom:1px solid #e5e7eb;">{{ $row['email'] }}</td>
                                <td style="padding:10px; border-bottom:1px solid #e5e7eb;">{{ $row['materia'] }}</td>
                                <td style="padding:10px; text-align:right; border-bottom:1px solid #e5e7eb;">
                                    ${{ number_format($row['precio_actual'], 2, ',', '.') }}
                                </td>
                                <td style="padding:10px; text-align:right; border-bottom:1px solid #e5e7eb;">
                                    {{ $row['clases_confirmadas'] }}
                                </td>
                                <td style="padding:10px; text-align:right; border-bottom:1px solid #e5e7eb;">
                                    {{ number_format($row['promedio_estrellas'], 2, ',', '.') }}
                                </td>
                                <td style="padding:10px; text-align:right; border-bottom:1px solid #e5e7eb;">
                                    {{ $row['total_resenas'] }}
                                </td>
                                <td style="padding:10px; text-align:right; border-bottom:1px solid #e5e7eb;">
                                    ${{ number_format($row['precio_sugerido'], 2, ',', '.') }}
                                </td>

                                <td style="padding:10px; border-bottom:1px solid #e5e7eb;">
                                    @php
                                        $recoBg = '#f3f4f6';
                                        $recoColor = '#374151';
                                        $recoIcon = '→';

                                        if ($row['recomendacion'] === 'Subir') {
                                            $recoBg = '#dcfce7';
                                            $recoColor = '#166534';
                                            $recoIcon = '↑';
                                        } elseif ($row['recomendacion'] === 'Bajar') {
                                            $recoBg = '#fee2e2';
                                            $recoColor = '#991b1b';
                                            $recoIcon = '↓';
                                        }
                                    @endphp

                                    <span style="
                                        display:inline-flex;
                                        align-items:center;
                                        gap:6px;
                                        padding:6px 10px;
                                        border-radius:999px;
                                        font-size:12px;
                                        font-weight:700;
                                        background:{{ $recoBg }};
                                        color:{{ $recoColor }};
                                    ">
                                        <span>{{ $recoIcon }}</span>
                                        <span>{{ $row['recomendacion'] }}</span>
                                    </span>
                                </td>

                                <td style="padding:10px; border-bottom:1px solid #e5e7eb;">
                                    @php
                                        $ajusteBg = '#f3f4f6';
                                        $ajusteColor = '#374151';

                                        if (str_contains($row['ajuste_texto'], '+')) {
                                            $ajusteBg = '#dcfce7';
                                            $ajusteColor = '#166534';
                                        } elseif (str_contains($row['ajuste_texto'], '-')) {
                                            $ajusteBg = '#fee2e2';
                                            $ajusteColor = '#991b1b';
                                        }
                                    @endphp

                                    <span style="
                                        display:inline-flex;
                                        align-items:center;
                                        padding:6px 10px;
                                        border-radius:999px;
                                        font-size:12px;
                                        font-weight:700;
                                        background:{{ $ajusteBg }};
                                        color:{{ $ajusteColor }};
                                    ">
                                        {{ $row['ajuste_texto'] }}
                                    </span>
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

    </div>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <script>
        function readOptimizacionData() {
            const el = document.getElementById('chartDataOptimizacion');
            if (!el) return { labels: [], actual: [], sugerido: [] };

            let labels = [];
            let actual = [];
            let sugerido = [];

            try { labels = JSON.parse(el.dataset.labels || '[]'); } catch (e) { labels = []; }
            try { actual = JSON.parse(el.dataset.actual || '[]'); } catch (e) { actual = []; }
            try { sugerido = JSON.parse(el.dataset.sugerido || '[]'); } catch (e) { sugerido = []; }

            return { labels, actual, sugerido };
        }

        function renderOptimizacionChart() {
            const data = readOptimizacionData();

            if (window._chartOptimizacion) window._chartOptimizacion.destroy();

            const ctx = document.getElementById('chartOptimizacion');
            if (!ctx) return;

            window._chartOptimizacion = new Chart(ctx.getContext('2d'), {
                type: 'bar',
                data: {
                    labels: data.labels,
                    datasets: [
                        {
                            label: 'Precio actual',
                            data: data.actual,
                            backgroundColor: 'rgba(59, 130, 246, 0.5)',
                            borderColor: 'rgba(59, 130, 246, 1)',
                            borderWidth: 1
                        },
                        {
                            label: 'Precio sugerido',
                            data: data.sugerido,
                            backgroundColor: 'rgba(168, 85, 247, 0.5)',
                            borderColor: 'rgba(168, 85, 247, 1)',
                            borderWidth: 1
                        }
                    ]
                },
                options: {
                    responsive: true,
                    scales: {
                        y: { beginAtZero: true }
                    }
                }
            });
        }

        document.addEventListener('DOMContentLoaded', () => {
            renderOptimizacionChart();

            if (window.Livewire) {
                Livewire.on('optimizacion-precios-actualizada', () => {
                    setTimeout(() => renderOptimizacionChart(), 50);
                });
            }
        });
    </script>

</x-filament-panels::page>