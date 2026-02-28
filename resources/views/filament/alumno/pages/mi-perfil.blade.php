<x-filament-panels::page>
    <div style="display:flex; flex-direction:column; gap:16px; max-width:900px;">

        {{-- Header acciones --}}
        <div style="display:flex; justify-content:space-between; align-items:center; gap:12px;">
            <div style="font-size:18px; font-weight:900; color:#111827;">Mi perfil</div>

            <div style="display:flex; gap:10px; flex-wrap:wrap;">
                @if(!$isEditing)
                    <x-filament::button color="primary" icon="heroicon-o-pencil" wire:click="editar">
                        Editar
                    </x-filament::button>
                @else
                    <x-filament::button color="gray" icon="heroicon-o-x-mark" wire:click="cancelarEdicion">
                        Cancelar
                    </x-filament::button>

                    <x-filament::button color="primary" icon="heroicon-o-check" wire:click="guardar" wire:target="guardar" wire:loading.attr="disabled">
                        <span wire:loading.remove wire:target="guardar">Guardar</span>
                        <span wire:loading wire:target="guardar">Guardando…</span>
                    </x-filament::button>
                @endif
            </div>
        </div>

        {{-- Datos personales --}}
        <div style="border:1px solid #e5e7eb; border-radius:14px; padding:18px; background:#fff;">
            <div style="font-size:16px; font-weight:900; color:#111827;">Datos personales</div>

            <div style="margin-top:14px; display:grid; grid-template-columns: 170px 1fr 1fr; gap:14px; align-items:start;">

                {{-- Foto --}}
                <div style="display:flex; flex-direction:column; gap:10px;">
                    <div style="width:150px; height:150px; border-radius:999px; overflow:hidden; border:1px solid #e5e7eb; background:#f9fafb; display:flex; align-items:center; justify-content:center;">
                        @if($profilePhotoUrl)
                            <img src="{{ $profilePhotoUrl }}" alt="Foto" style="width:100%; height:100%; object-fit:cover;">
                        @else
                            <div style="color:#9ca3af; font-weight:800;">Sin foto</div>
                        @endif
                    </div>

                    @if($isEditing)
                        <input type="file" wire:model="foto" />
                        @error('foto')
                            <div style="font-size:12px; color:#b91c1c;">{{ $message }}</div>
                        @enderror

                        <div style="display:flex; gap:10px; flex-wrap:wrap;">
                            <x-filament::button
                                color="danger"
                                size="sm"
                                icon="heroicon-o-trash"
                                wire:click="eliminarFoto"
                                onclick="return confirm('¿Eliminar la foto de perfil?')"
                            >
                                Eliminar foto
                            </x-filament::button>
                        </div>

                        <div style="font-size:11px; color:#6b7280;">Máx 2MB. JPG/PNG/WebP.</div>
                    @endif
                </div>

                {{-- Nombre --}}
                <div style="display:flex; flex-direction:column; gap:8px;">
                    <label style="font-size:13px; font-weight:900;">Nombres</label>
                    @if($isEditing)
                        <input type="text" wire:model.live="name" style="border:1px solid #d1d5db; border-radius:12px; padding:10px;">
                        @error('name') <div style="font-size:12px; color:#b91c1c;">{{ $message }}</div> @enderror
                    @else
                        <div style="padding:10px; border:1px solid #e5e7eb; border-radius:12px; background:#f9fafb;">{{ $name }}</div>
                    @endif
                </div>

                {{-- Apellido --}}
                <div style="display:flex; flex-direction:column; gap:8px;">
                    <label style="font-size:13px; font-weight:900;">Apellido</label>
                    @if($isEditing)
                        <input type="text" wire:model.live="apellido" style="border:1px solid #d1d5db; border-radius:12px; padding:10px;">
                        @error('apellido') <div style="font-size:12px; color:#b91c1c;">{{ $message }}</div> @enderror
                    @else
                        <div style="padding:10px; border:1px solid #e5e7eb; border-radius:12px; background:#f9fafb;">{{ $apellido }}</div>
                    @endif
                </div>

                {{-- Email --}}
                <div style="display:flex; flex-direction:column; gap:8px; grid-column: 2 / 4;">
                    <label style="font-size:13px; font-weight:900;">Email</label>
                    <div style="padding:10px; border:1px solid #e5e7eb; border-radius:12px; background:#f9fafb;">
                        {{ $email }}
                    </div>
                </div>
            </div>
        </div>

        {{-- Perfil académico --}}
        <div style="border:1px solid #e5e7eb; border-radius:14px; padding:18px; background:#fff;">
            <div style="display:flex; justify-content:space-between; align-items:center; gap:12px;">
                <div style="font-size:16px; font-weight:900; color:#111827;">Perfil académico</div>

                @if($isEditing)
                    <x-filament::button
                        color="danger"
                        size="sm"
                        icon="heroicon-o-trash"
                        wire:click="limpiarAcademico"
                        onclick="return confirm('¿Eliminar TODAS tus carreras y carrera activa?')"
                    >
                        Eliminar
                    </x-filament::button>
                @endif
            </div>

            {{-- Buscador --}}
            @if($isEditing)
                <div style="margin-top:14px; display:flex; flex-direction:column; gap:8px;">
                    <label style="font-size:13px; font-weight:900;">Agregar carrera (escribí para buscar)</label>

                    <div style="position:relative;">
                        <input type="text" wire:model.live="carreraQuery" placeholder="Ej: Ingeniería, Medicina..."
                               style="width:100%; border:1px solid #d1d5db; border-radius:12px; padding:10px;">

                        @if(!empty($carreraResultados))
                            <div style="position:absolute; z-index:20; top:44px; left:0; right:0; border:1px solid #e5e7eb; background:#fff; border-radius:12px; overflow:hidden; box-shadow:0 14px 28px rgba(15,23,42,.14);">
                                @foreach($carreraResultados as $r)
                                    <button type="button"
                                            wire:click="agregarCarrera({{ $r['id'] }})"
                                            style="width:100%; text-align:left; padding:10px 12px; border:none; background:#fff; cursor:pointer;"
                                            onmouseover="this.style.backgroundColor='#f9fafb'"
                                            onmouseout="this.style.backgroundColor='#fff'">
                                        {{ $r['nombre'] }}
                                    </button>
                                @endforeach
                            </div>
                        @endif
                    </div>
                </div>
            @endif

            {{-- Carreras seleccionadas --}}
            <div style="margin-top:14px;">
                <div style="font-size:13px; font-weight:900; margin-bottom:8px;">Mis carreras</div>

                @if(empty($carrerasSeleccionadas))
                    <div style="font-size:13px; color:#6b7280;">Todavía no seleccionaste carreras.</div>
                @else
                    <div style="display:flex; flex-wrap:wrap; gap:8px;">
                        @foreach($this->carrerasOptions as $id => $nombre)
                            <div style="display:flex; align-items:center; gap:8px; padding:8px 10px; border:1px solid #e5e7eb; border-radius:999px; background:#f9fafb;">
                                <span style="font-size:12px; font-weight:900;">{{ $nombre }}</span>

                                @if($isEditing)
                                    <button type="button" wire:click="quitarCarrera({{ $id }})"
                                            style="border:none; background:transparent; cursor:pointer; font-weight:900; color:#991b1b;">
                                        ✕
                                    </button>
                                @endif
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>

            {{-- Carrera activa --}}
            <div style="margin-top:14px; display:flex; flex-direction:column; gap:8px; max-width:520px;">
                <label style="font-size:13px; font-weight:900;">Carrera activa</label>

                @if($isEditing)
                    <select wire:model.live="carreraActivaId"
                            @disabled(empty($carrerasSeleccionadas))
                            style="border:1px solid #d1d5db; border-radius:12px; padding:10px; background:#fff;">
                        <option value="">(Elegí carrera activa)</option>
                        @foreach($this->carrerasOptions as $id => $nombre)
                            <option value="{{ $id }}">{{ $nombre }}</option>
                        @endforeach
                    </select>
                @else
                    <div style="padding:10px; border:1px solid #e5e7eb; border-radius:12px; background:#f9fafb;">
                        @if($carreraActivaId && isset($this->carrerasOptions[$carreraActivaId]))
                            {{ $this->carrerasOptions[$carreraActivaId] }}
                        @else
                            <span style="color:#6b7280;">(No definida)</span>
                        @endif
                    </div>
                @endif

                <div style="font-size:11px; color:#6b7280;">Se usa para filtrar materias/temas al solicitar turnos.</div>
            </div>
        </div>

    </div>
</x-filament-panels::page>