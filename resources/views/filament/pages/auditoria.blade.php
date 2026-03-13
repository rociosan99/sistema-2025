<x-filament-panels::page>

    <div style="display:flex; flex-direction:column; gap:18px;">

        {{-- Header --}}
        <div style="border:1px solid #e5e7eb; border-radius:14px; padding:16px; background:#fff;">
            <div style="display:flex; justify-content:space-between; align-items:center; gap:12px; flex-wrap:wrap;">
                <div>
                    <div style="font-size:18px; font-weight:900;">Auditoría</div>
                    <div style="font-size:13px; color:#6b7280; margin-top:4px;">
                        Historial de acciones y cambios registrados por el sistema.
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
                    <label style="font-size:12px; font-weight:900;">Log</label>
                    <select
                        wire:model.live="logName"
                        style="border:1px solid #d1d5db; border-radius:12px; padding:10px 12px; width:220px;"
                    >
                        <option value="">Todos</option>
                        <option value="audit">audit</option>
                        <option value="business">business</option>
                    </select>
                </div>

                <div style="display:flex; flex-direction:column; gap:6px;">
                    <label style="font-size:12px; font-weight:900;">Evento</label>
                    <select
                        wire:model.live="evento"
                        style="border:1px solid #d1d5db; border-radius:12px; padding:10px 12px; width:220px;"
                    >
                        <option value="">Todos</option>
                        <option value="created">created</option>
                        <option value="updated">updated</option>
                        <option value="deleted">deleted</option>
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
                        <span wire:loading wire:target="aplicarFiltros">Cargando…</span>
                    </x-filament::button>
                </div>

            </div>

            @error('fechas')
                <div style="margin-top:10px; color:#991b1b; font-weight:700;">{{ $message }}</div>
            @enderror
        </div>

        {{-- Tabla --}}
        <div style="border:1px solid #e5e7eb; border-radius:14px; padding:16px; background:#fff;">
            <div style="font-size:16px; font-weight:900; margin-bottom:10px;">
                Registros de auditoría
            </div>

            <div style="overflow:auto;">
                <table style="width:100%; border-collapse:collapse; min-width:1500px;">
                    <thead>
                        <tr style="background:#111827; color:#fff;">
                            <th style="text-align:right; padding:10px;">ID</th>
                            <th style="text-align:left; padding:10px;">Fecha</th>
                            <th style="text-align:left; padding:10px;">Descripción</th>
                            <th style="text-align:left; padding:10px;">Usuario</th>
                            <th style="text-align:left; padding:10px;">Email</th>
                            <th style="text-align:left; padding:10px;">Modelo</th>
                            <th style="text-align:left; padding:10px;">Registro</th>
                            <th style="text-align:left; padding:10px;">Evento</th>
                            <th style="text-align:left; padding:10px;">Log</th>
                            <th style="text-align:left; padding:10px;">Cambios / Propiedades</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($this->registros as $row)
                            <tr>
                                <td style="padding:10px; text-align:right; border-bottom:1px solid #e5e7eb;">
                                    {{ $row['id'] }}
                                </td>
                                <td style="padding:10px; border-bottom:1px solid #e5e7eb;">
                                    {{ $row['fecha'] }}
                                </td>
                                <td style="padding:10px; border-bottom:1px solid #e5e7eb;">
                                    {{ $row['description'] }}
                                </td>
                                <td style="padding:10px; border-bottom:1px solid #e5e7eb;">
                                    {{ $row['causer_nombre'] }}
                                </td>
                                <td style="padding:10px; border-bottom:1px solid #e5e7eb;">
                                    {{ $row['causer_email'] }}
                                </td>
                                <td style="padding:10px; border-bottom:1px solid #e5e7eb;">
                                    {{ $row['subject_type'] }}
                                </td>
                                <td style="padding:10px; border-bottom:1px solid #e5e7eb;">
                                    {{ $row['subject_id'] }}
                                </td>
                                <td style="padding:10px; border-bottom:1px solid #e5e7eb;">
                                    {{ $row['event'] }}
                                </td>
                                <td style="padding:10px; border-bottom:1px solid #e5e7eb;">
                                    {{ $row['log_name'] }}
                                </td>
                                <td style="padding:10px; border-bottom:1px solid #e5e7eb;">
                                    <pre style="white-space:pre-wrap; margin:0; font-size:12px; color:#374151;">{{ $row['properties_pretty'] }}</pre>
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

</x-filament-panels::page>