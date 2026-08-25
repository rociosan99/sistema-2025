<x-filament-panels::page>
    <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm dark:border-white/10 dark:bg-gray-900">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h2 class="text-base font-bold text-gray-950 dark:text-white">Ofertas de alumnos</h2>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                    Aceptar crea un turno en <strong>pendiente_pago</strong>.
                </p>
            </div>

            <x-filament::button size="sm" wire:click="cargar" icon="heroicon-o-arrow-path">
                Actualizar
            </x-filament::button>
        </div>

        {{-- Filtros --}}
        <div class="mt-6 rounded-xl border border-gray-200 bg-gray-50 p-5 dark:border-white/10 dark:bg-white/5" style="margin-top:24px; padding:20px; border:1px solid #e5e7eb; border-radius:12px; background:#f9fafb;">
            <div style="display:flex; flex-wrap:wrap; align-items:flex-end; gap:16px 20px;">
                <div style="flex:1 1 180px; min-width:0;">
                    <label for="f-alumno" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-200" style="display:block; margin-bottom:6px; font-weight:600;">Alumno</label>
                    <select id="f-alumno" wire:model.defer="fAlumnoId" class="block w-full rounded-lg border-gray-300 bg-white text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-white/10 dark:bg-white/5 dark:text-white" style="display:block; width:100%; min-width:0; padding:8px 10px;">
                        <option value="">Todos</option>
                        @foreach($this->alumnosOptions as $id => $nombre)
                            <option value="{{ $id }}">{{ $nombre }}</option>
                        @endforeach
                    </select>
                    @error('fAlumnoId')
                        <p class="mt-1 text-sm text-danger-600">{{ $message }}</p>
                    @enderror
                </div>

                <div style="flex:1 1 180px; min-width:0;">
                    <label for="f-materia" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-200" style="display:block; margin-bottom:6px; font-weight:600;">Materia</label>
                    <select id="f-materia" wire:model.defer="fMateriaId" class="block w-full rounded-lg border-gray-300 bg-white text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-white/10 dark:bg-white/5 dark:text-white" style="display:block; width:100%; min-width:0; padding:8px 10px;">
                        <option value="">Todas</option>
                        @foreach($this->materiasOptions as $id => $nombre)
                            <option value="{{ $id }}">{{ $nombre }}</option>
                        @endforeach
                    </select>
                    @error('fMateriaId')
                        <p class="mt-1 text-sm text-danger-600">{{ $message }}</p>
                    @enderror
                </div>

                <div style="flex:1 1 180px; min-width:0;">
                    <label for="f-desde" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-200" style="display:block; margin-bottom:6px; font-weight:600;">Desde</label>
                    <input id="f-desde" type="date" wire:model.defer="fFechaDesde" class="block w-full rounded-lg border-gray-300 bg-white text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-white/10 dark:bg-white/5 dark:text-white" style="display:block; width:100%; min-width:0; padding:8px 10px;">
                    @error('fFechaDesde')
                        <p class="mt-1 text-sm text-danger-600">{{ $message }}</p>
                    @enderror
                </div>

                <div style="flex:1 1 180px; min-width:0;">
                    <label for="f-hasta" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-200" style="display:block; margin-bottom:6px; font-weight:600;">Hasta</label>
                    <input id="f-hasta" type="date" wire:model.defer="fFechaHasta" class="block w-full rounded-lg border-gray-300 bg-white text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-white/10 dark:bg-white/5 dark:text-white" style="display:block; width:100%; min-width:0; padding:8px 10px;">
                    @error('fFechaHasta')
                        <p class="mt-1 text-sm text-danger-600">{{ $message }}</p>
                    @enderror
                </div>

                <div style="display:flex; flex:0 0 auto; flex-wrap:nowrap; align-items:center; gap:10px; padding-bottom:1px;">
                    <x-filament::button size="sm" wire:click="cargar">Aplicar</x-filament::button>
                    <x-filament::button size="sm" color="gray" outlined wire:click="limpiarFiltros">Limpiar</x-filament::button>
                </div>
            </div>
        </div>

        {{-- Tabla --}}
        @if(empty($this->ofertas))
            <div class="mt-5 rounded-lg bg-gray-50 px-4 py-5 text-sm text-gray-500 dark:bg-white/5 dark:text-gray-400">
                No tenés ofertas pendientes que coincidan con los filtros.
            </div>
        @else
            <div class="mt-6 overflow-x-auto rounded-xl border border-gray-200 dark:border-white/10" style="overflow-x:auto;">
                <table class="w-full border-collapse text-left text-sm" style="width:100%; min-width:780px; table-layout:fixed; border-collapse:collapse; text-align:left;">
                    <colgroup>
                        <col style="width:15%;">
                        <col style="width:14%;">
                        <col style="width:22%;">
                        <col style="width:10%;">
                        <col style="width:11%;">
                        <col style="width:12%;">
                        <col style="width:16%;">
                    </colgroup>
                    <thead class="bg-gray-50 text-xs font-semibold uppercase tracking-wide text-gray-600 dark:bg-white/5 dark:text-gray-300" style="background:#f9fafb; font-weight:600;">
                        <tr class="border-b border-gray-200 dark:border-white/10" style="border-bottom:2px solid #d1d5db;">
                            <th style="padding:12px;">Alumno</th>
                            <th style="padding:12px; border-left:1px solid #e5e7eb;">Calificación</th>
                            <th style="padding:12px; border-left:1px solid #e5e7eb;">Clase</th>
                            <th style="padding:12px; border-left:1px solid #e5e7eb;">Fecha</th>
                            <th style="padding:12px; border-left:1px solid #e5e7eb;">Horario</th>
                            <th style="padding:12px; border-left:1px solid #e5e7eb;">Vence</th>
                            <th style="padding:12px; border-left:1px solid #e5e7eb;">Acciones</th>
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-gray-200 bg-white dark:divide-white/10 dark:bg-gray-900">
                        @foreach($this->ofertas as $o)
                            <tr class="align-middle" style="border-bottom:1px solid #e5e7eb; vertical-align:middle;">
                                <td class="font-medium text-gray-950 dark:text-white" style="padding:16px 12px; vertical-align:middle; overflow-wrap:anywhere;">{{ $o['alumno'] }}</td>
                                <td class="text-gray-700 dark:text-gray-200" style="padding:16px 12px; vertical-align:middle; border-left:1px solid #e5e7eb;">
                                    @if($o['calificaciones_cantidad'] > 0)
                                        <span class="font-medium">{{ number_format((float) $o['calificacion_promedio'], 1, ',', '.') }} ★</span>
                                        <span class="block text-xs text-gray-500 dark:text-gray-400">
                                            {{ $o['calificaciones_cantidad'] }} {{ $o['calificaciones_cantidad'] === 1 ? 'calificación' : 'calificaciones' }}
                                        </span>
                                    @else
                                        <span class="text-gray-500 dark:text-gray-400">Sin calificaciones</span>
                                    @endif
                                </td>
                                <td class="text-gray-700 dark:text-gray-200" style="padding:16px 12px; vertical-align:middle; border-left:1px solid #e5e7eb;">
                                    <div
                                        title="{{ $o['materia'] }}{{ filled($o['tema']) && $o['tema'] !== '-' ? ' — Tema: '.$o['tema'] : '' }}"
                                        style="display:-webkit-box; overflow:hidden; -webkit-box-orient:vertical; -webkit-line-clamp:2; line-height:1.4; overflow-wrap:anywhere;"
                                    >
                                        <span class="font-medium text-gray-950 dark:text-white">{{ $o['materia'] }}</span>
                                        @if(filled($o['tema']) && $o['tema'] !== '-')
                                            <span class="text-xs text-gray-500 dark:text-gray-400"> — Tema: {{ $o['tema'] }}</span>
                                        @endif
                                    </div>
                                </td>
                                <td class="text-gray-700 dark:text-gray-200" style="padding:16px 12px; vertical-align:middle; white-space:nowrap; border-left:1px solid #e5e7eb;">{{ $o['fecha'] }}</td>
                                <td class="text-gray-700 dark:text-gray-200" style="padding:16px 12px; vertical-align:middle; white-space:nowrap; border-left:1px solid #e5e7eb;">{{ $o['hora_inicio'] }} - {{ $o['hora_fin'] }}</td>
                                <td class="text-gray-700 dark:text-gray-200" style="padding:16px 12px; vertical-align:middle; border-left:1px solid #e5e7eb;">
                                    <span style="display:block; white-space:nowrap;">{{ substr($o['expires_at'], 0, 10) }}</span>
                                    <span style="display:block; white-space:nowrap;">{{ substr($o['expires_at'], 11) }}</span>
                                </td>
                                <td style="padding:16px 12px; vertical-align:middle; border-left:1px solid #e5e7eb;">
                                    <div style="display:flex; align-items:center; gap:6px; flex-wrap:nowrap;">
                                        <x-filament::button
                                            size="sm"
                                            color="success"
                                            style="padding:6px 9px;"
                                            x-data
                                            x-on:click="
                                                $wire.set('ofertaSeleccionada', {{ $o['id'] }});
                                                $dispatch('open-modal', { id: 'modal-aceptar-oferta' });
                                            "
                                        >
                                            Aceptar
                                        </x-filament::button>

                                        <x-filament::button size="sm" color="danger" outlined style="padding:6px 9px;" wire:click="rechazar({{ $o['id'] }})">
                                            Rechazar
                                        </x-filament::button>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

    {{-- Modal global de aceptación --}}
    <x-filament::modal id="modal-aceptar-oferta">
        <form wire:submit.prevent="aceptar">
            <div class="space-y-4 p-4">
                <p class="text-sm text-gray-600 dark:text-gray-300">
                    Se utilizará el enlace de clase predeterminado configurado en Mi perfil.
                </p>

                <div class="flex justify-end">
                    <x-filament::button type="submit">Confirmar</x-filament::button>
                </div>
            </div>
        </form>
    </x-filament::modal>
</x-filament-panels::page>
