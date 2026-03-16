<x-filament-panels::page>

    <div style="display:flex; flex-direction:column; gap:18px;">

        {{-- Header --}}
        <div style="border:1px solid #e5e7eb; border-radius:14px; padding:16px; background:#fff;">
            <div style="display:flex; justify-content:space-between; align-items:center; gap:12px; flex-wrap:wrap;">
                <div>
                    <div style="font-size:18px; font-weight:900;">Auditoría</div>
                    <div style="font-size:13px; color:#6b7280; margin-top:4px;">
                        Registro de acciones del sistema y eventos de negocio.
                    </div>
                </div>

                <div style="font-size:12px; color:#374151; background:#f9fafb; border:1px solid #e5e7eb; padding:8px 10px; border-radius:12px;">
                    {{ $this->fechaInicio }} -> {{ $this->fechaFin }}
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

                <div style="display:flex; flex-direction:column; gap:6px;">
                    <label style="font-size:12px; font-weight:900;">Tipo de auditoría</label>
                    <select
                        wire:model.live="tipoAuditoria"
                        style="border:1px solid #d1d5db; border-radius:12px; padding:10px 12px; width:220px;"
                    >
                        <option value="">Todos</option>
                        <option value="audit">Sistema</option>
                        <option value="business">Negocio</option>
                    </select>
                </div>

                <div style="display:flex; flex-direction:column; gap:6px;">
                    <label style="font-size:12px; font-weight:900;">Modelo</label>
                    <select
                        wire:model.live="modelo"
                        style="border:1px solid #d1d5db; border-radius:12px; padding:10px 12px; width:220px;"
                    >
                        <option value="">Todos</option>
                        @foreach($this->modelosOptions as $valor => $label)
                            <option value="{{ $valor }}">{{ $label }}</option>
                        @endforeach
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

        {{-- Tabla --}}
        <div style="border:1px solid #e5e7eb; border-radius:14px; padding:16px; background:#fff;">
            <div style="display:flex; justify-content:space-between; align-items:center; gap:12px; flex-wrap:wrap; margin-bottom:10px;">
                <div style="font-size:16px; font-weight:900;">
                    Registros de auditoría
                </div>

                <div style="font-size:12px; color:#6b7280;">
                    {{ count($this->registros) }} registro(s)
                </div>
            </div>

            <div style="overflow:auto;">
                <table style="width:100%; border-collapse:collapse; min-width:1250px;">
                    <thead>
                        <tr style="background:#111827; color:#fff;">
                            <th style="text-align:right; padding:10px;">ID</th>
                            <th style="text-align:left; padding:10px;">Fecha</th>
                            <th style="text-align:left; padding:10px;">Descripción</th>
                            <th style="text-align:left; padding:10px;">Usuario</th>
                            <th style="text-align:left; padding:10px;">Email</th>
                            <th style="text-align:left; padding:10px;">Registro</th>
                            <th style="text-align:left; padding:10px;">Evento</th>
                            <th style="text-align:left; padding:10px;">Tipo de auditoría</th>
                            <th style="text-align:left; padding:10px;">Cambios / Propiedades</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($this->registros as $row)
                            <tr>
                                <td style="padding:10px; text-align:right; border-bottom:1px solid #e5e7eb;">{{ $row['id'] }}</td>
                                <td style="padding:10px; border-bottom:1px solid #e5e7eb; white-space:nowrap;">{{ $row['fecha'] }}</td>
                                <td style="padding:10px; border-bottom:1px solid #e5e7eb;">{{ $row['descripcion'] }}</td>
                                <td style="padding:10px; border-bottom:1px solid #e5e7eb;">{{ $row['usuario'] }}</td>
                                <td style="padding:10px; border-bottom:1px solid #e5e7eb;">{{ $row['email'] }}</td>
                                <td style="padding:10px; border-bottom:1px solid #e5e7eb;">{{ $row['registro'] }}</td>
                                <td style="padding:10px; border-bottom:1px solid #e5e7eb;">{{ $row['evento'] }}</td>
                                <td style="padding:10px; border-bottom:1px solid #e5e7eb;">{{ $row['log'] }}</td>
                                <td style="padding:10px; border-bottom:1px solid #e5e7eb; font-family:monospace; white-space:pre-wrap; font-size:12px;">{{ $row['properties'] }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" style="padding:10px; color:#6b7280;">Sin datos.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

    </div>

</x-filament-panels::page>