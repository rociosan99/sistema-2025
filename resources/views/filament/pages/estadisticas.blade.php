<x-filament-panels::page>

    <div style="display:flex; flex-direction:column; gap:18px;">

        {{-- Header --}}
        <div style="border:1px solid #e5e7eb; border-radius:14px; padding:16px; background:#fff;">
            <div style="display:flex; justify-content:space-between; align-items:center; gap:12px; flex-wrap:wrap;">
                <div>
                    <div style="font-size:18px; font-weight:900;">Estadísticas</div>
                    <div style="font-size:13px; color:#6b7280; margin-top:4px;">
                        Turnos por Materia / Tema en el rango seleccionado.
                    </div>
                </div>

                <div style="font-size:12px; color:#374151; background:#f9fafb; border:1px solid #e5e7eb; padding:8px 10px; border-radius:12px;">
                    {{ $this->fechaInicio }} → {{ $this->fechaFin }}
                </div>
            </div>
        </div>

        {{-- Debug (oculto) --}}
        <div style="display:none;">
            <div>
                <strong>Debug:</strong>
                Turnos en rango = {{ $this->debugTotalTurnosEnRango }},
                Materias distintas = {{ $this->debugMateriasDistintas }},
                Temas distintas = {{ $this->debugTemasDistintos }}
            </div>

            <div style="margin-top:8px;">
                <strong>Debug Temas:</strong>
                Turnos con tema en rango = {{ $this->debugTurnosConTemaEnRango }}

                <div style="margin-top:6px;">
                    Top 5 temas (tema / solicitados):
                    <ul style="margin:6px 0 0 18px;">
                        @foreach($this->debugTopTemas as $x)
                            <li>{{ $x['tema'] }} — {{ $x['solicitados'] }}</li>
                        @endforeach
                    </ul>
                </div>
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

        {{-- Materias --}}
        <div style="border:1px solid #e5e7eb; border-radius:14px; padding:16px; background:#fff;">
            <div style="font-size:16px; font-weight:900; margin-bottom:10px;">
                Turnos por Materia (Top 10 por solicitudes)
            </div>

            {{-- Data para JS --}}
            <div
                id="chartDataMaterias"
                data-labels='@json($this->materiasChartLabels)'
                data-values='@json($this->materiasChartSolicitados)'
                style="display:none;"
            ></div>

            <div wire:ignore>
                <canvas id="chartMaterias" style="max-width:100%; height:320px;"></canvas>
            </div>

            <div style="margin-top:16px; overflow:auto;">
                <table style="width:100%; border-collapse:collapse; min-width:760px;">
                    <thead>
                        <tr style="background:#111827; color:#fff;">
                            <th style="text-align:left; padding:10px;">Materia</th>
                            <th style="text-align:right; padding:10px;">Solicitudes</th>
                            <th style="text-align:right; padding:10px;">Pagadas</th>
                            <th style="text-align:right; padding:10px;">Canceladas</th>
                            <th style="text-align:right; padding:10px;">Vencidas</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($this->materias as $row)
                            <tr>
                                <td style="padding:10px; border-bottom:1px solid #e5e7eb;">{{ $row['materia'] }}</td>
                                <td style="padding:10px; text-align:right; border-bottom:1px solid #e5e7eb;">{{ $row['solicitados'] }}</td>
                                <td style="padding:10px; text-align:right; border-bottom:1px solid #e5e7eb;">{{ $row['pagados'] }}</td>
                                <td style="padding:10px; text-align:right; border-bottom:1px solid #e5e7eb;">{{ $row['cancelados'] }}</td>
                                <td style="padding:10px; text-align:right; border-bottom:1px solid #e5e7eb;">{{ $row['vencidos'] }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="5" style="padding:10px; color:#6b7280;">Sin datos.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Temas --}}
        <div style="border:1px solid #e5e7eb; border-radius:14px; padding:16px; background:#fff;">
            <div style="font-size:16px; font-weight:900; margin-bottom:10px;">
                Turnos por Tema (Top 10 por solicitudes)
            </div>

            {{-- Data para JS --}}
            <div
                id="chartDataTemas"
                data-labels='@json($this->temasChartLabels)'
                data-values='@json($this->temasChartSolicitados)'
                style="display:none;"
            ></div>

            <div wire:ignore>
                <canvas id="chartTemas" style="max-width:100%; height:320px;"></canvas>
            </div>

            <div style="margin-top:16px; overflow:auto;">
                <table style="width:100%; border-collapse:collapse; min-width:760px;">
                    <thead>
                        <tr style="background:#111827; color:#fff;">
                            <th style="text-align:left; padding:10px;">Tema</th>
                            <th style="text-align:right; padding:10px;">Solicitudes</th>
                            <th style="text-align:right; padding:10px;">Pagadas</th>
                            <th style="text-align:right; padding:10px;">Canceladas</th>
                            <th style="text-align:right; padding:10px;">Vencidas</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($this->temas as $row)
                            <tr>
                                <td style="padding:10px; border-bottom:1px solid #e5e7eb;">{{ $row['tema'] }}</td>
                                <td style="padding:10px; text-align:right; border-bottom:1px solid #e5e7eb;">{{ $row['solicitados'] }}</td>
                                <td style="padding:10px; text-align:right; border-bottom:1px solid #e5e7eb;">{{ $row['pagados'] }}</td>
                                <td style="padding:10px; text-align:right; border-bottom:1px solid #e5e7eb;">{{ $row['cancelados'] }}</td>
                                <td style="padding:10px; text-align:right; border-bottom:1px solid #e5e7eb;">{{ $row['vencidos'] }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="5" style="padding:10px; color:#6b7280;">Sin datos.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

    </div>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <script>
        function readChartData(id) {
            const el = document.getElementById(id);
            if (!el) return { labels: [], values: [] };

            let labels = [];
            let values = [];

            try { labels = JSON.parse(el.dataset.labels || '[]'); } catch (e) { labels = []; }
            try { values = JSON.parse(el.dataset.values || '[]'); } catch (e) { values = []; }

            return { labels, values };
        }

        function renderCharts() {
            const materias = readChartData('chartDataMaterias');
            const temas    = readChartData('chartDataTemas');

            if (window._chartMaterias) window._chartMaterias.destroy();
            if (window._chartTemas) window._chartTemas.destroy();

            const cM = document.getElementById('chartMaterias');
            if (cM) {
                window._chartMaterias = new Chart(cM.getContext('2d'), {
                    type: 'bar',
                    data: {
                        labels: materias.labels,
                        datasets: [{
                            label: 'Solicitudes',
                            data: materias.values,
                            backgroundColor: 'rgba(54, 162, 235, 0.5)',
                            borderColor: 'rgba(54, 162, 235, 1)',
                            borderWidth: 1
                        }]
                    },
                    options: { responsive: true, scales: { y: { beginAtZero: true } } }
                });
            }

            const cT = document.getElementById('chartTemas');
            if (cT) {
                window._chartTemas = new Chart(cT.getContext('2d'), {
                    type: 'bar',
                    data: {
                        labels: temas.labels,
                        datasets: [{
                            label: 'Solicitudes',
                            data: temas.values,
                            backgroundColor: 'rgba(34, 197, 94, 0.5)',
                            borderColor: 'rgba(34, 197, 94, 1)',
                            borderWidth: 1
                        }]
                    },
                    options: { responsive: true, scales: { y: { beginAtZero: true } } }
                });
            }
        }

        document.addEventListener('DOMContentLoaded', () => {
            renderCharts();

            if (window.Livewire) {
                Livewire.on('estadisticas-actualizadas', () => {
                    setTimeout(() => renderCharts(), 50);
                });
            }
        });
    </script>

</x-filament-panels::page>
