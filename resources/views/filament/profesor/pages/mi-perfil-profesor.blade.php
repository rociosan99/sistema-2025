<x-filament-panels::page>
    <div style="display:flex; flex-direction:column; gap:16px; max-width:980px;">

        <div style="display:flex; justify-content:space-between; align-items:center; gap:12px;">
            <div style="font-size:18px; font-weight:900; color:#111827;">Mi perfil (Profesor)</div>

            <div style="display:flex; gap:10px; flex-wrap:wrap;">
                @if(!$isEditing)
                    <x-filament::button color="primary" icon="heroicon-o-pencil" wire:click="editar">
                        Editar
                    </x-filament::button>
                @else
                    <x-filament::button color="gray" icon="heroicon-o-x-mark" wire:click="cancelarEdicion">
                        Cancelar
                    </x-filament::button>

                    <x-filament::button
                        color="primary"
                        icon="heroicon-o-check"
                        wire:click="guardar"
                        wire:target="guardar"
                        wire:loading.attr="disabled"
                    >
                        <span wire:loading.remove wire:target="guardar">Guardar</span>
                        <span wire:loading wire:target="guardar">Guardando…</span>
                    </x-filament::button>
                @endif
            </div>
        </div>

        {{-- Datos personales --}}
        <div style="border:1px solid #e5e7eb; border-radius:14px; padding:18px; background:#fff;">
            <div style="font-size:16px; font-weight:900;">Datos personales</div>

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
                        @error('foto') <div style="font-size:12px; color:#b91c1c;">{{ $message }}</div> @enderror

                        <x-filament::button
                            color="danger"
                            size="sm"
                            icon="heroicon-o-trash"
                            wire:click="eliminarFoto"
                            onclick="return confirm('¿Eliminar la foto de perfil?')"
                        >
                            Eliminar foto
                        </x-filament::button>

                        <div style="font-size:11px; color:#6b7280;">Máx 2MB. JPG/PNG/WebP.</div>
                    @endif
                </div>

                {{-- Nombre --}}
                <div style="display:flex; flex-direction:column; gap:8px;">
                    <label style="font-weight:900;">Nombre</label>
                    @if($isEditing)
                        <input type="text" wire:model.live="name" style="border:1px solid #d1d5db; border-radius:12px; padding:10px;">
                        @error('name') <div style="font-size:12px; color:#b91c1c;">{{ $message }}</div> @enderror
                    @else
                        <div style="padding:10px; border:1px solid #e5e7eb; border-radius:12px; background:#f9fafb;">{{ $name }}</div>
                    @endif
                </div>

                {{-- Apellido --}}
                <div style="display:flex; flex-direction:column; gap:8px;">
                    <label style="font-weight:900;">Apellido</label>
                    @if($isEditing)
                        <input type="text" wire:model.live="apellido" style="border:1px solid #d1d5db; border-radius:12px; padding:10px;">
                        @error('apellido') <div style="font-size:12px; color:#b91c1c;">{{ $message }}</div> @enderror
                    @else
                        <div style="padding:10px; border:1px solid #e5e7eb; border-radius:12px; background:#f9fafb;">{{ $apellido }}</div>
                    @endif
                </div>

                {{-- Email --}}
                <div style="display:flex; flex-direction:column; gap:8px; grid-column: 2 / 4;">
                    <label style="font-weight:900;">Email</label>
                    <div style="padding:10px; border:1px solid #e5e7eb; border-radius:12px; background:#f9fafb;">{{ $email }}</div>
                </div>

                {{-- Ciudad --}}
                <div style="display:flex; flex-direction:column; gap:8px; grid-column: 2 / 4;">
                    <label style="font-weight:900;">Ciudad</label>
                    @if($isEditing)
                        <input type="text" wire:model.live="ciudad" placeholder="Ej: Córdoba"
                               style="border:1px solid #d1d5db; border-radius:12px; padding:10px;">
                        @error('ciudad') <div style="font-size:12px; color:#b91c1c;">{{ $message }}</div> @enderror
                    @else
                        <div style="padding:10px; border:1px solid #e5e7eb; border-radius:12px; background:#f9fafb;">{{ $ciudad ?: '—' }}</div>
                    @endif
                </div>
            </div>
        </div>

        {{-- Presentación profesional --}}
        <div style="border:1px solid #e5e7eb; border-radius:14px; padding:18px; background:#fff;">
            <div style="font-size:16px; font-weight:900;">Presentación profesional</div>

            <div style="margin-top:14px; display:grid; grid-template-columns: 1fr 1fr; gap:14px;">
                {{-- Bio --}}
                <div style="display:flex; flex-direction:column; gap:8px; grid-column: 1 / 3;">
                    <label style="font-weight:900;">Bio / Descripción</label>
                    @if($isEditing)
                        <textarea wire:model.live="bio" rows="5"
                                  style="border:1px solid #d1d5db; border-radius:12px; padding:10px;"></textarea>
                        @error('bio') <div style="font-size:12px; color:#b91c1c;">{{ $message }}</div> @enderror
                    @else
                        <div style="padding:10px; border:1px solid #e5e7eb; border-radius:12px; background:#f9fafb; white-space:pre-wrap;">{{ $bio ?: '—' }}</div>
                    @endif
                </div>

                {{-- Experiencia --}}
                <div style="display:flex; flex-direction:column; gap:8px;">
                    <label style="font-weight:900;">Experiencia (años)</label>
                    @if($isEditing)
                        <input type="number" min="0" max="80" wire:model.live="experiencia_anios"
                               style="border:1px solid #d1d5db; border-radius:12px; padding:10px;">
                        @error('experiencia_anios') <div style="font-size:12px; color:#b91c1c;">{{ $message }}</div> @enderror
                    @else
                        <div style="padding:10px; border:1px solid #e5e7eb; border-radius:12px; background:#f9fafb;">
                            {{ $experiencia_anios !== null ? $experiencia_anios.' años' : '—' }}
                        </div>
                    @endif
                </div>

                {{-- Precio default --}}
                <div style="display:flex; flex-direction:column; gap:8px;">
                    <label style="font-weight:900;">Precio por hora (default)</label>
                    @if($isEditing)
                        <input type="number" step="0.01" wire:model.live="precio_por_hora_default"
                               placeholder="Ej: 12000"
                               style="border:1px solid #d1d5db; border-radius:12px; padding:10px;">
                        @error('precio_por_hora_default') <div style="font-size:12px; color:#b91c1c;">{{ $message }}</div> @enderror
                    @else
                        <div style="padding:10px; border:1px solid #e5e7eb; border-radius:12px; background:#f9fafb;">
                            {{ $precio_por_hora_default !== null ? '$'.number_format($precio_por_hora_default, 0, ',', '.') : '—' }}
                        </div>
                    @endif
                </div>

                {{-- Título profesional (al final) --}}
                <div style="display:flex; flex-direction:column; gap:8px; grid-column: 1 / 3;">
                    <label style="font-weight:900;">Título profesional</label>
                    @if($isEditing)
                        <input type="text" wire:model.live="titulo_profesional"
                               placeholder="Ej: Profesor de Álgebra y Cálculo"
                               style="border:1px solid #d1d5db; border-radius:12px; padding:10px;">
                        @error('titulo_profesional') <div style="font-size:12px; color:#b91c1c;">{{ $message }}</div> @enderror
                    @else
                        <div style="padding:10px; border:1px solid #e5e7eb; border-radius:12px; background:#f9fafb;">
                            {{ $titulo_profesional ?: '—' }}
                        </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- Materias --}}
        <div style="border:1px solid #e5e7eb; border-radius:14px; padding:18px; background:#fff;">
            <div style="font-size:16px; font-weight:900;">Materias que dictás</div>

            @if($isEditing)
                <div style="margin-top:12px; position:relative;">
                    <input type="text" wire:model.live="materiaQuery" placeholder="Escribí para buscar materias…"
                           style="width:100%; border:1px solid #d1d5db; border-radius:12px; padding:10px;">
                    @if(!empty($materiaResultados))
                        <div style="position:absolute; z-index:20; top:44px; left:0; right:0; border:1px solid #e5e7eb; background:#fff; border-radius:12px; overflow:hidden; box-shadow:0 14px 28px rgba(15,23,42,.14);">
                            @foreach($materiaResultados as $r)
                                <button type="button" wire:click="agregarMateria({{ $r['id'] }})"
                                        style="width:100%; text-align:left; padding:10px 12px; border:none; background:#fff; cursor:pointer;"
                                        onmouseover="this.style.backgroundColor='#f9fafb'"
                                        onmouseout="this.style.backgroundColor='#fff'">
                                    {{ $r['nombre'] }}
                                </button>
                            @endforeach
                        </div>
                    @endif
                </div>
            @endif

            <div style="margin-top:14px;">
                @if(empty($materiasIds))
                    <div style="color:#6b7280;">No tenés materias cargadas.</div>
                @else
                    <div style="display:flex; flex-direction:column; gap:10px;">
                        @foreach($this->materiasOptions as $id => $nombre)
                            <div style="display:flex; justify-content:space-between; gap:12px; align-items:center; border:1px solid #e5e7eb; border-radius:12px; padding:10px; background:#f9fafb;">
                                <div style="font-weight:900;">{{ $nombre }}</div>

                                <div style="display:flex; gap:10px; align-items:center;">
                                    @if($isEditing)
                                        <input type="number" step="0.01"
                                               wire:model.live="materiasPrecios.{{ $id }}"
                                               placeholder="Precio por hora"
                                               style="width:170px; border:1px solid #d1d5db; border-radius:10px; padding:8px; background:#fff;">
                                        <x-filament::button color="danger" size="sm" wire:click="quitarMateria({{ $id }})">Quitar</x-filament::button>
                                    @else
                                        @php $p = $materiasPrecios[$id] ?? null; @endphp
                                        <div style="font-weight:900;">
                                            {{ $p !== null ? '$'.number_format((float)$p, 0, ',', '.') : '—' }}
                                        </div>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>

        {{-- Temas --}}
        <div style="border:1px solid #e5e7eb; border-radius:14px; padding:18px; background:#fff;">
            <div style="font-size:16px; font-weight:900;">Temas que dictás</div>

            @if($isEditing)
                <div style="margin-top:12px; position:relative;">
                    <input type="text" wire:model.live="temaQuery" placeholder="Escribí para buscar temas…"
                           style="width:100%; border:1px solid #d1d5db; border-radius:12px; padding:10px;">
                    @if(!empty($temaResultados))
                        <div style="position:absolute; z-index:20; top:44px; left:0; right:0; border:1px solid #e5e7eb; background:#fff; border-radius:12px; overflow:hidden; box-shadow:0 14px 28px rgba(15,23,42,.14);">
                            @foreach($temaResultados as $r)
                                <button type="button" wire:click="agregarTema({{ $r['id'] }})"
                                        style="width:100%; text-align:left; padding:10px 12px; border:none; background:#fff; cursor:pointer;"
                                        onmouseover="this.style.backgroundColor='#f9fafb'"
                                        onmouseout="this.style.backgroundColor='#fff'">
                                    {{ $r['nombre'] }}
                                </button>
                            @endforeach
                        </div>
                    @endif
                </div>
            @endif

            <div style="margin-top:14px;">
                @if(empty($temasIds))
                    <div style="color:#6b7280;">No tenés temas cargados.</div>
                @else
                    <div style="display:flex; flex-wrap:wrap; gap:8px;">
                        @foreach($this->temasOptions as $id => $nombre)
                            <div style="display:flex; align-items:center; gap:8px; padding:8px 10px; border:1px solid #e5e7eb; border-radius:999px; background:#f9fafb;">
                                <span style="font-size:12px; font-weight:900;">{{ $nombre }}</span>
                                @if($isEditing)
                                    <button type="button" wire:click="quitarTema({{ $id }})"
                                            style="border:none; background:transparent; cursor:pointer; font-weight:900; color:#991b1b;">
                                        ✕
                                    </button>
                                @endif
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>

    </div>
</x-filament-panels::page>