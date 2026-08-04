<x-filament-panels::page>
    <div style="display:flex; flex-direction:column; gap:18px;">

        <div style="border:1px solid #e5e7eb; border-radius:14px; padding:18px; background:#fff;">
            <div style="font-weight:900; font-size:16px; color:#111827;">Crear solicitud de disponibilidad</div>

            <p style="margin:8px 0 0; font-size:13px; color:#6b7280;">
                Si no hay profesores disponibles, dejá tu horario y materia. Cuando aparezca un match, se enviará una oferta a profesores compatibles.
            </p>

            @if (! $this->tieneCarreraActiva)
                <div style="margin-top:14px; padding:12px; border:1px solid #f59e0b; border-radius:12px; background:#fffbeb; color:#92400e; font-size:13px;">
                    Configurá una carrera activa en Mi perfil para seleccionar una materia y crear una solicitud.
                </div>
            @endif

            <div style="margin-top:14px; display:grid; grid-template-columns:repeat(auto-fit, minmax(220px, 1fr)); gap:12px;">

                <div>
                    <label style="font-size:12px; font-weight:900;">Materia</label>

                    <select wire:model.live="materiaId"
                        @disabled(! $this->tieneCarreraActiva)
                        style="width:100%; border:1px solid #d1d5db; border-radius:12px; padding:10px;">

                        <option value="">
                            {{ $this->tieneCarreraActiva ? 'Seleccionar...' : 'Sin carrera activa' }}
                        </option>

                        @foreach($this->materiasOptions as $id => $name)
                            <option value="{{ $id }}">{{ $name }}</option>
                        @endforeach

                    </select>

                    @if ($errors->has('materiaId'))
                        <div style="margin-top:8px; color:#dc2626; font-size:13px;">
                            {{ $errors->first('materiaId') }}
                        </div>
                    @endif
                </div>

                <div>
                    <label style="font-size:12px; font-weight:900;">Tema (opcional)</label>

                    @php($temasOptions = $this->temasOptions)

                    <select wire:model.live="temaId"
                        @disabled(! $this->tieneCarreraActiva || ! $materiaId)
                        style="width:100%; border:1px solid #d1d5db; border-radius:12px; padding:10px;">

                        <option value="">
                            {{ $materiaId ? 'Sin tema' : 'Seleccioná primero una materia' }}
                        </option>

                        @foreach($temasOptions as $id => $name)
                            <option value="{{ $id }}">{{ $name }}</option>
                        @endforeach

                    </select>

                    @if ($this->tieneCarreraActiva && $materiaId && empty($temasOptions))
                        <div style="margin-top:8px; color:#6b7280; font-size:13px;">
                            No hay temas disponibles para esta materia.
                        </div>
                    @endif

                    @if ($errors->has('temaId'))
                        <div style="margin-top:8px; color:#dc2626; font-size:13px;">
                            {{ $errors->first('temaId') }}
                        </div>
                    @endif
                </div>

                <div>
                    <label style="font-size:12px; font-weight:900;">Fecha</label>

                    <input
                        type="date"
                        wire:model.live="fecha"
                        @disabled(! $this->tieneCarreraActiva)
                        style="width:100%; border:1px solid #d1d5db; border-radius:12px; padding:10px;">

                    @if ($errors->has('fecha'))
                        <div style="margin-top:8px; color:#dc2626; font-size:13px;">
                            {{ $errors->first('fecha') }}
                        </div>
                    @endif
                </div>

                <div>
                    <label style="font-size:12px; font-weight:900;">Desde</label>

                    <input
                        type="time"
                        step="3600"
                        wire:model.live="horaInicio"
                        @disabled(! $this->tieneCarreraActiva)
                        style="width:100%; border:1px solid #d1d5db; border-radius:12px; padding:10px;">
                </div>

                <div>
                    <label style="font-size:12px; font-weight:900;">Hasta</label>

                    <input
                        type="time"
                        step="3600"
                        wire:model.live="horaFin"
                        @disabled(! $this->tieneCarreraActiva)
                        style="width:100%; border:1px solid #d1d5db; border-radius:12px; padding:10px;">

                    @if ($errors->has('hora'))
                        <div style="margin-top:8px; color:#dc2626; font-size:13px; font-weight:600;">
                            {{ $errors->first('hora') }}
                        </div>
                    @endif
                </div>

                <div>
                    <label style="font-size:12px; font-weight:900;">Expira (opcional)</label>

                    <input
                        type="datetime-local"
                        wire:model.live="expiresAt"
                        @disabled(! $this->tieneCarreraActiva)
                        style="width:100%; border:1px solid #d1d5db; border-radius:12px; padding:10px;">
                </div>

            </div>

            <div style="margin-top:14px;">
                <x-filament::button
                    wire:click="crearSolicitud"
                    icon="heroicon-o-plus"
                    :disabled="! $this->tieneCarreraActiva">
                    Crear solicitud
                </x-filament::button>
            </div>
        </div>

        <div style="border:1px solid #e5e7eb; border-radius:14px; padding:18px; background:#fff;">

            <div style="font-weight:900; font-size:16px; color:#111827;">
                Mis solicitudes
            </div>

            @if(empty($this->misSolicitudes))

                <div style="margin-top:10px; color:#6b7280; font-size:13px;">
                    No tenés solicitudes creadas.
                </div>

            @else

                <div style="margin-top:12px; overflow:auto;">

                    <table style="width:100%; border-collapse:collapse; font-size:13px;">

                        <thead>
                            <tr style="background:#f9fafb;">
                                <th style="text-align:left; padding:10px; border-bottom:1px solid #e5e7eb;">Materia</th>
                                <th style="text-align:left; padding:10px; border-bottom:1px solid #e5e7eb;">Tema</th>
                                <th style="text-align:left; padding:10px; border-bottom:1px solid #e5e7eb;">Fecha</th>
                                <th style="text-align:left; padding:10px; border-bottom:1px solid #e5e7eb;">Horario</th>
                                <th style="text-align:left; padding:10px; border-bottom:1px solid #e5e7eb;">Estado</th>
                                <th style="text-align:left; padding:10px; border-bottom:1px solid #e5e7eb;">Acción</th>
                            </tr>
                        </thead>

                        <tbody>

                            @foreach($this->misSolicitudes as $s)

                                <tr>
                                    <td style="padding:10px; border-bottom:1px solid #f1f5f9;">
                                        {{ $s['materia'] }}
                                    </td>

                                    <td style="padding:10px; border-bottom:1px solid #f1f5f9;">
                                        {{ $s['tema'] }}
                                    </td>

                                    <td style="padding:10px; border-bottom:1px solid #f1f5f9;">
                                        {{ $s['fecha'] }}
                                    </td>

                                    <td style="padding:10px; border-bottom:1px solid #f1f5f9;">
                                        {{ $s['hora_inicio'] }} - {{ $s['hora_fin'] }}
                                    </td>

                                    <td style="padding:10px; border-bottom:1px solid #f1f5f9;">
                                        {{ $s['estado'] }}
                                    </td>

                                    <td style="padding:10px; border-bottom:1px solid #f1f5f9;">

                                        @if($s['estado'] === 'activa')

                                            <x-filament::button
                                                size="sm"
                                                color="danger"
                                                wire:click="cancelarSolicitud({{ $s['id'] }})">

                                                Cancelar

                                            </x-filament::button>

                                        @else

                                            <span style="color:#9ca3af;">—</span>

                                        @endif

                                    </td>
                                </tr>

                            @endforeach

                        </tbody>

                    </table>

                </div>

            @endif

        </div>

    </div>
</x-filament-panels::page>
