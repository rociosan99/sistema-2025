<x-filament-panels::page>
    <div style="border:1px solid #e5e7eb; border-radius:14px; padding:18px; background:#fff;">

        <div style="display:flex; justify-content:space-between; align-items:center; gap:12px; flex-wrap:wrap;">
            <div>
                <div style="font-weight:900; font-size:16px; color:#111827;">Ofertas de alumnos</div>
                <div style="margin-top:6px; font-size:13px; color:#6b7280;">
                    Aceptar crea un turno en <strong>pendiente_pago</strong>.
                </div>
            </div>

            <div style="display:flex; gap:8px; flex-wrap:wrap;">
                <x-filament::button size="sm" wire:click="cargar" icon="heroicon-o-arrow-path">
                    Actualizar
                </x-filament::button>
            </div>
        </div>

        {{-- ✅ FILTROS (compactos) --}}
        <div style="
            margin-top:14px;
            padding:12px;
            border:1px solid #e5e7eb;
            border-radius:12px;
            background:#f9fafb;
        ">
            <div style="display:grid; grid-template-columns:repeat(12, minmax(0, 1fr)); gap:10px; align-items:end;">

                <div style="grid-column: span 3;">
                    <label style="display:block; font-size:12px; font-weight:800; color:#374151; margin-bottom:6px;">Alumno</label>
                    <select wire:model.defer="fAlumnoId" style="width:100%; padding:8px 10px; border:1px solid #e5e7eb; border-radius:10px; background:#fff;">
                        <option value="">Todos</option>
                        @foreach($this->alumnosOptions as $id => $nombre)
                            <option value="{{ $id }}">{{ $nombre }}</option>
                        @endforeach
                    </select>
                </div>

                <div style="grid-column: span 3;">
                    <label style="display:block; font-size:12px; font-weight:800; color:#374151; margin-bottom:6px;">Materia</label>
                    <select wire:model.defer="fMateriaId" style="width:100%; padding:8px 10px; border:1px solid #e5e7eb; border-radius:10px; background:#fff;">
                        <option value="">Todas</option>
                        @foreach($this->materiasOptions as $id => $nombre)
                            <option value="{{ $id }}">{{ $nombre }}</option>
                        @endforeach
                    </select>
                </div>

                <div style="grid-column: span 2;">
                    <label style="display:block; font-size:12px; font-weight:800; color:#374151; margin-bottom:6px;">Desde</label>
                    <input type="date" wire:model.defer="fFechaDesde"
                           style="width:100%; padding:8px 10px; border:1px solid #e5e7eb; border-radius:10px; background:#fff;">
                </div>

                <div style="grid-column: span 2;">
                    <label style="display:block; font-size:12px; font-weight:800; color:#374151; margin-bottom:6px;">Hasta</label>
                    <input type="date" wire:model.defer="fFechaHasta"
                           style="width:100%; padding:8px 10px; border:1px solid #e5e7eb; border-radius:10px; background:#fff;">
                </div>

                <div style="grid-column: span 2; display:flex; gap:10px; align-items:center; padding-bottom:6px;">
                    <label style="display:flex; gap:8px; align-items:center; font-size:12px; font-weight:800; color:#374151;">
                        <input type="checkbox" wire:model.defer="fSoloRecomendadas">
                        Solo recomendadas
                    </label>
                </div>

                <div style="grid-column: span 12; display:flex; gap:10px; justify-content:flex-end; flex-wrap:wrap;">
                    <x-filament::button size="sm" wire:click="cargar" icon="heroicon-o-funnel">
                        Aplicar
                    </x-filament::button>

                    <x-filament::button size="sm" color="gray" wire:click="limpiarFiltros" icon="heroicon-o-x-mark">
                        Limpiar
                    </x-filament::button>
                </div>
            </div>
        </div>

        @if(empty($this->ofertas))
            <div style="margin-top:14px; color:#6b7280; font-size:13px;">
                No tenés ofertas pendientes.
            </div>
        @else
            <div style="margin-top:14px; overflow:auto;">
                <table style="width:100%; border-collapse:collapse; font-size:13px;">
                    <thead>
                        <tr style="background:#f9fafb;">
                            <th style="text-align:left; padding:10px; border-bottom:1px solid #e5e7eb;">Recomendada</th>
                            <th style="text-align:left; padding:10px; border-bottom:1px solid #e5e7eb;">Alumno</th>
                            <th style="text-align:left; padding:10px; border-bottom:1px solid #e5e7eb;">Materia</th>
                            <th style="text-align:left; padding:10px; border-bottom:1px solid #e5e7eb;">Tema</th>
                            <th style="text-align:left; padding:10px; border-bottom:1px solid #e5e7eb;">Fecha</th>
                            <th style="text-align:left; padding:10px; border-bottom:1px solid #e5e7eb;">Horario</th>
                            <th style="text-align:left; padding:10px; border-bottom:1px solid #e5e7eb;">Vence</th>
                            <th style="text-align:left; padding:10px; border-bottom:1px solid #e5e7eb;">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($this->ofertas as $o)
                            <tr>
                                <td style="padding:10px; border-bottom:1px solid #f1f5f9;">
                                    @if(!empty($o['recomendada']))
                                        <span style="display:inline-block; padding:4px 8px; border-radius:999px; background:#dcfce7; color:#166534; font-weight:800;">
                                            ✅ Recomendada
                                        </span>
                                        <div style="margin-top:6px; font-size:12px; color:#166534;">
                                            {{ $o['recomendacion_texto'] ?? '' }}
                                        </div>
                                    @else
                                        <span style="color:#9ca3af;">—</span>
                                    @endif
                                </td>

                                <td style="padding:10px; border-bottom:1px solid #f1f5f9;">{{ $o['alumno'] }}</td>
                                <td style="padding:10px; border-bottom:1px solid #f1f5f9;">{{ $o['materia'] }}</td>
                                <td style="padding:10px; border-bottom:1px solid #f1f5f9;">{{ $o['tema'] }}</td>
                                <td style="padding:10px; border-bottom:1px solid #f1f5f9;">{{ $o['fecha'] }}</td>
                                <td style="padding:10px; border-bottom:1px solid #f1f5f9;">{{ $o['hora_inicio'] }} - {{ $o['hora_fin'] }}</td>
                                <td style="padding:10px; border-bottom:1px solid #f1f5f9;">{{ $o['expires_at'] }}</td>

                                <td style="padding:10px; border-bottom:1px solid #f1f5f9; display:flex; gap:8px; flex-wrap:wrap;">
                                    <x-filament::button size="sm" wire:click="aceptar({{ $o['id'] }})">
                                        Aceptar
                                    </x-filament::button>

                                    <x-filament::button size="sm" color="danger" wire:click="rechazar({{ $o['id'] }})">
                                        Rechazar
                                    </x-filament::button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</x-filament-panels::page>
