<x-filament-panels::page>
    <div style="max-width:820px; margin:0 auto; padding:24px; border:1px solid #e5e7eb; border-radius:14px; background:#ffffff;">
        @if($propuestaRechazada)
            <div>
                <h2 style="margin:0; color:#111827; font-size:22px; font-weight:700;">
                    Propuesta rechazada
                </h2>

                <div style="margin-top:24px; color:#374151; line-height:1.7;">
                    <p style="margin:0 0 8px;">Rechazaste la propuesta de este profesor.</p>
                    <p style="margin:0 0 8px;">La solicitud anterior quedó cerrada.</p>
                    <p style="margin:0;">Podés iniciar una nueva búsqueda si todavía necesitás una clase.</p>
                </div>

                <div style="display:flex; flex-wrap:wrap; align-items:center; gap:12px; margin-top:28px;">
                    <x-filament::button tag="a" :href="$urlTurnos" color="gray" outlined>
                        Volver a mis turnos
                    </x-filament::button>

                    <x-filament::button tag="a" :href="$urlBuscarProfesor">
                        Buscar otro profesor
                    </x-filament::button>
                </div>
            </div>
        @else
            <div style="display:flex; justify-content:space-between; align-items:flex-start; gap:16px; flex-wrap:wrap;">
                <div>
                    <h2 style="margin:0; color:#111827; font-size:22px; font-weight:700;">
                        Propuesta de profesor
                    </h2>
                    <p style="margin:6px 0 0; color:#6b7280;">
                        Revisá la información antes de continuar al pago.
                    </p>
                </div>

                <span style="display:inline-flex; padding:6px 10px; border-radius:9999px; background:#e0f2fe; color:#075985; font-size:13px; font-weight:600;">
                    Esperando tu respuesta
                </span>
            </div>

            <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(240px, 1fr)); gap:18px 28px; margin-top:28px; padding-top:24px; border-top:1px solid #e5e7eb;">
                <div>
                    <span style="font-weight:600; color:#374151;">Profesor:</span>
                    <span style="margin-left:6px; color:#111827;">
                        {{ trim(($turno->profesor?->name ?? '').' '.($turno->profesor?->apellido ?? '')) ?: '-' }}
                    </span>
                </div>

                <div>
                    <span style="font-weight:600; color:#374151;">Materia:</span>
                    <span style="margin-left:6px; color:#111827;">{{ $turno->materia?->materia_nombre ?? '-' }}</span>
                </div>

                @if($turno->tema)
                    <div>
                        <span style="font-weight:600; color:#374151;">Tema:</span>
                        <span style="margin-left:6px; color:#111827;">{{ $turno->tema->tema_nombre }}</span>
                    </div>
                @endif

                <div>
                    <span style="font-weight:600; color:#374151;">Fecha:</span>
                    <span style="margin-left:6px; color:#111827;">{{ $turno->fecha->format('d/m/Y') }}</span>
                </div>

                <div>
                    <span style="font-weight:600; color:#374151;">Horario:</span>
                    <span style="margin-left:6px; color:#111827;">
                        {{ substr((string) $turno->hora_inicio, 0, 5) }} - {{ substr((string) $turno->hora_fin, 0, 5) }}
                    </span>
                </div>

                <div>
                    <span style="font-weight:600; color:#374151;">Precio:</span>
                    <span style="margin-left:6px; color:#111827; font-weight:700;">
                        ${{ number_format((float) $turno->precio_total, 2, ',', '.') }}
                    </span>
                </div>
            </div>

            <div style="display:flex; flex-wrap:wrap; justify-content:flex-end; align-items:center; gap:12px; margin-top:30px; padding-top:22px; border-top:1px solid #e5e7eb;">
                <x-filament::button
                    color="danger"
                    outlined
                    wire:click="rechazar"
                    wire:loading.attr="disabled"
                    wire:target="rechazar,aceptar"
                >
                    Rechazar
                </x-filament::button>

                <x-filament::button
                    color="success"
                    wire:click="aceptar"
                    wire:loading.attr="disabled"
                    wire:target="rechazar,aceptar"
                >
                    Aceptar y continuar al pago
                </x-filament::button>
            </div>
        @endif
    </div>
</x-filament-panels::page>
