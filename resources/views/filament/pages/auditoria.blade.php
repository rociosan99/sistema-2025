<x-filament-panels::page>

    <div style="display:flex; flex-direction:column; gap:18px;">

        {{-- Header --}}
        <div style="border:1px solid #e5e7eb; border-radius:16px; padding:18px; background:#fff;">
            <div style="display:flex; justify-content:space-between; align-items:center; gap:12px; flex-wrap:wrap;">
                <div>
                    <div style="font-size:20px; font-weight:900;">Auditoría</div>
                    <div style="font-size:13px; color:#6b7280; margin-top:4px;">
                        Registro de acciones sobre modelos reales del sistema.
                    </div>
                </div>

                <div style="font-size:12px; color:#374151; background:#f9fafb; border:1px solid #e5e7eb; padding:8px 10px; border-radius:12px;">
                    {{ $this->fechaInicio }} → {{ $this->fechaFin }}
                </div>
            </div>
        </div>

        {{-- Filtros --}}
        <div style="border:1px solid #e5e7eb; border-radius:16px; padding:18px; background:#fff;">
            <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(220px, 1fr)); gap:14px;">

                <div style="display:flex; flex-direction:column; gap:6px;">
                    <label style="font-size:12px; font-weight:900;">Desde</label>
                    <input
                        type="date"
                        wire:model.live="fechaInicio"
                        style="border:1px solid #d1d5db; border-radius:12px; padding:10px 12px;"
                    >
                </div>

                <div style="display:flex; flex-direction:column; gap:6px;">
                    <label style="font-size:12px; font-weight:900;">Hasta</label>
                    <input
                        type="date"
                        wire:model.live="fechaFin"
                        style="border:1px solid #d1d5db; border-radius:12px; padding:10px 12px;"
                    >
                </div>

                <div style="display:flex; flex-direction:column; gap:6px;">
                    <label style="font-size:12px; font-weight:900;">Modelo</label>
                    <select
                        wire:model.live="modelo"
                        style="border:1px solid #d1d5db; border-radius:12px; padding:10px 12px;"
                    >
                        <option value="">Todos</option>
                        @foreach($this->modelosOptions as $valor => $label)
                            <option value="{{ $valor }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                <div style="display:flex; flex-direction:column; gap:6px;">
                    <label style="font-size:12px; font-weight:900;">Evento</label>
                    <select
                        wire:model.live="evento"
                        style="border:1px solid #d1d5db; border-radius:12px; padding:10px 12px;"
                    >
                        @foreach($this->eventosOptions as $valor => $label)
                            <option value="{{ $valor }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                <div style="display:flex; flex-direction:column; gap:6px;">
                    <label style="font-size:12px; font-weight:900;">Rol</label>
                    <select
                        wire:model.live="rolUsuario"
                        style="border:1px solid #d1d5db; border-radius:12px; padding:10px 12px;"
                    >
                        @foreach($this->rolesOptions as $valor => $label)
                            <option value="{{ $valor }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                <div style="display:flex; flex-direction:column; gap:6px;">
                    <label style="font-size:12px; font-weight:900;">Usuario o email</label>
                    <input
                        type="text"
                        wire:model.live.debounce.500ms="buscarUsuario"
                        placeholder="Buscar usuario..."
                        style="border:1px solid #d1d5db; border-radius:12px; padding:10px 12px;"
                    >
                </div>

            </div>

            @error('fechas')
                <div style="margin-top:10px; color:#991b1b; font-weight:700;">{{ $message }}</div>
            @enderror
        </div>

        {{-- Tabla --}}
        <div style="border:1px solid #e5e7eb; border-radius:16px; padding:16px; background:#fff;">
            <div style="display:flex; justify-content:space-between; align-items:center; gap:12px; flex-wrap:wrap; margin-bottom:12px;">
                <div>
                    <div style="font-size:16px; font-weight:900;">Registros de auditoría</div>
                    <div style="font-size:12px; color:#6b7280; margin-top:4px;">
                        Mostrando hasta 300 registros.
                    </div>
                </div>

                <div style="font-size:12px; color:#6b7280;">
                    {{ count($this->registros) }} registro(s)
                </div>
            </div>

            <div style="overflow:auto;">
                <table style="width:100%; border-collapse:collapse; min-width:1100px;">
                    <thead>
                        <tr style="background:#111827; color:#fff;">
                            <th style="text-align:right; padding:10px;">ID</th>
                            <th style="text-align:left; padding:10px;">Modelo</th>
                            <th style="text-align:left; padding:10px;">Descripción</th>
                            <th style="text-align:left; padding:10px;">Evento</th>
                            <th style="text-align:left; padding:10px;">Usuario</th>
                            <th style="text-align:left; padding:10px;">Fecha</th>
                            <th style="text-align:center; padding:10px;">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($this->registros as $row)
                            @php
                                $eventoBg = '#eef2ff';
                                $eventoColor = '#4338ca';

                                if ($row['evento'] === 'Actualización') {
                                    $eventoBg = '#ecfeff';
                                    $eventoColor = '#155e75';
                                } elseif ($row['evento'] === 'Eliminación') {
                                    $eventoBg = '#fee2e2';
                                    $eventoColor = '#991b1b';
                                } elseif ($row['evento'] === 'Creación') {
                                    $eventoBg = '#dcfce7';
                                    $eventoColor = '#166534';
                                }
                            @endphp

                            <tr>
                                <td style="padding:10px; text-align:right; border-bottom:1px solid #e5e7eb;">
                                    {{ $row['id'] }}
                                </td>

                                <td style="padding:10px; border-bottom:1px solid #e5e7eb;">
                                    {{ $row['modelo'] }}
                                </td>

                                <td style="padding:10px; border-bottom:1px solid #e5e7eb;">
                                    {{ $row['descripcion'] }}
                                </td>

                                <td style="padding:10px; border-bottom:1px solid #e5e7eb;">
                                    <span style="
                                        display:inline-flex;
                                        align-items:center;
                                        padding:6px 10px;
                                        border-radius:999px;
                                        font-size:12px;
                                        font-weight:700;
                                        background:{{ $eventoBg }};
                                        color:{{ $eventoColor }};
                                    ">
                                        {{ $row['evento'] }}
                                    </span>
                                </td>

                                <td style="padding:10px; border-bottom:1px solid #e5e7eb;">
                                    <div>{{ $row['usuario'] }}</div>
                                    @if(($row['email'] ?? '-') !== '-')
                                        <div style="font-size:12px; color:#6b7280; margin-top:2px;">
                                            {{ $row['email'] }}
                                        </div>
                                    @endif
                                    @if(($row['rol'] ?? '-') !== '-')
                                        <div style="font-size:12px; color:#6b7280; margin-top:2px;">
                                            Rol: {{ $row['rol'] }}
                                        </div>
                                    @endif
                                </td>

                                <td style="padding:10px; border-bottom:1px solid #e5e7eb; white-space:nowrap;">
                                    {{ $row['fecha'] }}
                                </td>

                                <td style="padding:10px; text-align:center; border-bottom:1px solid #e5e7eb;">
                                    <x-filament::button
                                        size="sm"
                                        color="gray"
                                        icon="heroicon-o-eye"
                                        wire:click="verDetalle({{ $row['id'] }})"
                                    >
                                        Ver
                                    </x-filament::button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" style="padding:12px; color:#6b7280;">
                                    Sin datos en el rango seleccionado.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Detalle --}}
        @if($detalle)
            <div style="border:1px solid #e5e7eb; border-radius:16px; padding:18px; background:#fff;">
                <div style="display:flex; justify-content:space-between; align-items:center; gap:12px; flex-wrap:wrap; margin-bottom:14px;">
                    <div>
                        <div style="font-size:16px; font-weight:900;">
                            Detalle del registro #{{ $detalle['id'] }}
                        </div>
                        <div style="font-size:12px; color:#6b7280; margin-top:4px;">
                            Información útil para auditar el cambio seleccionado.
                        </div>
                    </div>

                    <x-filament::button
                        color="gray"
                        icon="heroicon-o-x-mark"
                        wire:click="cerrarDetalle"
                    >
                        Cerrar
                    </x-filament::button>
                </div>

                <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(220px, 1fr)); gap:14px; margin-bottom:18px;">
                    <div style="border:1px solid #e5e7eb; border-radius:14px; padding:14px;">
                        <div style="font-size:12px; color:#6b7280; font-weight:700;">Fecha y hora</div>
                        <div style="font-size:15px; font-weight:900; margin-top:6px;">{{ $detalle['fecha'] }}</div>
                    </div>

                    <div style="border:1px solid #e5e7eb; border-radius:14px; padding:14px;">
                        <div style="font-size:12px; color:#6b7280; font-weight:700;">Modelo</div>
                        <div style="font-size:15px; font-weight:900; margin-top:6px;">{{ $detalle['modelo'] }}</div>
                    </div>

                    <div style="border:1px solid #e5e7eb; border-radius:14px; padding:14px;">
                        <div style="font-size:12px; color:#6b7280; font-weight:700;">ID afectado</div>
                        <div style="font-size:15px; font-weight:900; margin-top:6px;">{{ $detalle['subject_id'] }}</div>
                    </div>

                    <div style="border:1px solid #e5e7eb; border-radius:14px; padding:14px;">
                        <div style="font-size:12px; color:#6b7280; font-weight:700;">Evento</div>
                        <div style="font-size:15px; font-weight:900; margin-top:6px;">{{ $detalle['evento'] }}</div>
                    </div>

                    <div style="border:1px solid #e5e7eb; border-radius:14px; padding:14px;">
                        <div style="font-size:12px; color:#6b7280; font-weight:700;">Usuario</div>
                        <div style="font-size:15px; font-weight:900; margin-top:6px;">{{ $detalle['usuario'] }}</div>
                        <div style="font-size:12px; color:#6b7280; margin-top:4px;">{{ $detalle['email'] }}</div>
                        <div style="font-size:12px; color:#6b7280; margin-top:4px;">Rol: {{ $detalle['rol'] }}</div>
                    </div>
                </div>

                <div style="border:1px solid #e5e7eb; border-radius:14px; padding:14px; background:#fcfcfd; margin-bottom:18px;">
                    <div style="font-size:12px; color:#6b7280; font-weight:700;">Descripción</div>
                    <div style="font-size:15px; font-weight:900; margin-top:6px;">{{ $detalle['descripcion'] }}</div>
                </div>

                <div style="border:1px solid #e5e7eb; border-radius:14px; padding:14px; background:#fff;">
                    <div style="font-size:14px; font-weight:900; margin-bottom:10px;">Cambios registrados</div>

                    @if(count($detalle['cambios'] ?? []))
                        <div style="overflow:auto;">
                            <table style="width:100%; border-collapse:collapse; min-width:700px;">
                                <thead>
                                    <tr style="background:#111827; color:#fff;">
                                        <th style="text-align:left; padding:8px;">Campo</th>
                                        <th style="text-align:left; padding:8px;">Antes</th>
                                        <th style="text-align:left; padding:8px;">Después</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($detalle['cambios'] as $cambio)
                                        <tr>
                                            <td style="padding:8px; border-bottom:1px solid #e5e7eb; font-weight:700;">
                                                {{ $cambio['campo'] }}
                                            </td>
                                            <td style="padding:8px; border-bottom:1px solid #e5e7eb; white-space:pre-wrap;">
                                                {{ $cambio['antes'] }}
                                            </td>
                                            <td style="padding:8px; border-bottom:1px solid #e5e7eb; white-space:pre-wrap;">
                                                {{ $cambio['despues'] }}
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div style="font-size:13px; color:#6b7280;">
                            Este registro no tiene campos comparables guardados en properties.
                        </div>
                    @endif
                </div>
            </div>
        @endif

    </div>

</x-filament-panels::page>