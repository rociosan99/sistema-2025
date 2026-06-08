<x-filament-panels::page>
    <div style="border:1px solid #e5e7eb; border-radius:14px; padding:18px; background:#fff;">

        <div style="display:flex; justify-content:space-between; align-items:center; gap:12px; flex-wrap:wrap;">
            <div>
                <div style="font-weight:900; font-size:16px; color:#111827;">
                    Ofertas de alumnos
                </div>
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

        {{-- FILTROS (NO TOCADO) --}}
        <div style="
            margin-top:14px;
            padding:12px;
            border:1px solid #e5e7eb;
            border-radius:12px;
            background:#f9fafb;
        ">
            <div style="display:grid; grid-template-columns:repeat(12, minmax(0, 1fr)); gap:10px; align-items:end;">

                <div style="grid-column: span 3;">
                    <label>Alumno</label>
                    <select wire:model.defer="fAlumnoId" style="width:100%; padding:8px;">
                        <option value="">Todos</option>
                        @foreach($this->alumnosOptions as $id => $nombre)
                            <option value="{{ $id }}">{{ $nombre }}</option>
                        @endforeach
                    </select>
                </div>

                <div style="grid-column: span 3;">
                    <label>Materia</label>
                    <select wire:model.defer="fMateriaId" style="width:100%; padding:8px;">
                        <option value="">Todas</option>
                        @foreach($this->materiasOptions as $id => $nombre)
                            <option value="{{ $id }}">{{ $nombre }}</option>
                        @endforeach
                    </select>
                </div>

                <div style="grid-column: span 2;">
                    <label>Desde</label>
                    <input type="date" wire:model.defer="fFechaDesde" style="width:100%; padding:8px;">
                </div>

                <div style="grid-column: span 2;">
                    <label>Hasta</label>
                    <input type="date" wire:model.defer="fFechaHasta" style="width:100%; padding:8px;">
                </div>

                <div style="grid-column: span 2;">
                    <label>
                        <input type="checkbox" wire:model.defer="fSoloRecomendadas">
                        Solo recomendadas
                    </label>
                </div>

                <div style="grid-column: span 12; display:flex; justify-content:flex-end; gap:10px;">
                    <x-filament::button size="sm" wire:click="cargar">
                        Aplicar
                    </x-filament::button>

                    <x-filament::button size="sm" color="gray" wire:click="limpiarFiltros">
                        Limpiar
                    </x-filament::button>
                </div>

            </div>
        </div>

        {{-- TABLA --}}
        @if(empty($this->ofertas))
            <div style="margin-top:14px; color:#6b7280;">
                No tenés ofertas pendientes.
            </div>
        @else
            <div style="margin-top:14px; overflow:auto;">
                <table style="width:100%; border-collapse:collapse; font-size:13px;">

                    <thead>
                        <tr style="background:#f9fafb;">
                            <th>Alumno</th>
                            <th>Materia</th>
                            <th>Tema</th>
                            <th>Fecha</th>
                            <th>Horario</th>
                            <th>Vence</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>

                    <tbody>
                        @foreach($this->ofertas as $o)
                            <tr>

                                <td>{{ $o['alumno'] }}</td>
                                <td>{{ $o['materia'] }}</td>
                                <td>{{ $o['tema'] }}</td>
                                <td>{{ $o['fecha'] }}</td>
                                <td>{{ $o['hora_inicio'] }} - {{ $o['hora_fin'] }}</td>
                                <td>{{ $o['expires_at'] }}</td>

                                <td style="display:flex; gap:8px;">

                                    {{-- BOTÓN ACEPTAR --}}
                                    <x-filament::button
                                        size="sm"
                                        x-data
                                        x-on:click="
                                            $wire.set('ofertaSeleccionada', {{ $o['id'] }});
                                            $dispatch('open-modal', { id: 'modal-aceptar-oferta' });
                                        "
                                    >
                                        Aceptar
                                    </x-filament::button>

                                    {{-- MODAL GLOBAL (NO rompe Livewire) --}}
                                    <x-filament::modal id="modal-aceptar-oferta">

                                        <form wire:submit.prevent="aceptar">

                                            <div style="padding:16px;">

                                                <label style="font-weight:700;">
                                                    Enlace de la clase
                                                </label>

                                                <input
                                                    type="url"
                                                    wire:model="enlaceClase"
                                                    placeholder="https://meet.google.com/..."
                                                    style="width:100%; margin-top:8px; padding:10px; border:1px solid #ccc; border-radius:8px;"
                                                    required
                                                />

                                                <div style="margin-top:14px; display:flex; justify-content:end; gap:10px;">
                                                    <x-filament::button type="submit">
                                                        Confirmar
                                                    </x-filament::button>
                                                </div>

                                            </div>

                                        </form>

                                    </x-filament::modal>

                                    {{-- RECHAZAR --}}
                                    <x-filament::button
                                        size="sm"
                                        color="danger"
                                        wire:click="rechazar({{ $o['id'] }})"
                                    >
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