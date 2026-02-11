<x-filament-panels::page>
    <div style="border:1px solid #e5e7eb; border-radius:14px; padding:18px; background:#fff;">
        <div style="display:flex; justify-content:space-between; align-items:center; gap:12px;">
            <div>
                <div style="font-weight:900; font-size:16px; color:#111827;">Ofertas de alumnos</div>
                <div style="margin-top:6px; font-size:13px; color:#6b7280;">
                    Aceptar crea un turno en <strong>pendiente_pago</strong>.
                </div>
            </div>
            <x-filament::button size="sm" wire:click="cargar" icon="heroicon-o-arrow-path">
                Actualizar
            </x-filament::button>
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
