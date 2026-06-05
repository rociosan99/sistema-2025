<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Nueva solicitud de ubicacion</title>
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.5;">

    @php
        $tipoSolicitud = match ($solicitud->tipo) {
            \App\Models\SolicitudUbicacion::TIPO_PAIS => 'Pais',
            \App\Models\SolicitudUbicacion::TIPO_PROVINCIA => 'Provincia',
            \App\Models\SolicitudUbicacion::TIPO_CIUDAD => 'Ciudad/localidad',
            default => ucfirst((string) $solicitud->tipo),
        };

        $nombreProfesor = trim(($solicitud->solicitante?->name ?? '') . ' ' . ($solicitud->solicitante?->apellido ?? ''));
        if ($nombreProfesor === '') {
            $nombreProfesor = 'Profesor';
        }
    @endphp

    <h2>Nueva solicitud de ubicacion</h2>

    <p>
        Un profesor solicito agregar una nueva ubicacion al sistema.
    </p>

    <ul>
        <li><strong>Tipo:</strong> {{ $tipoSolicitud }}</li>
        <li><strong>Nombre solicitado:</strong> {{ $solicitud->nombre_solicitado }}</li>
        <li><strong>Profesor solicitante:</strong> {{ $nombreProfesor }}</li>
        <li><strong>Email del profesor:</strong> {{ $solicitud->solicitante?->email ?? '-' }}</li>

        @if($solicitud->pais)
            <li><strong>Pais contexto:</strong> {{ $solicitud->pais->pais_nombre }}</li>
        @endif

        @if($solicitud->provincia)
            <li><strong>Provincia contexto:</strong> {{ $solicitud->provincia->provincia_nombre }}</li>
        @endif

        @if($solicitud->observacion_solicitante)
            <li><strong>Observacion:</strong> {{ $solicitud->observacion_solicitante }}</li>
        @endif

        <li><strong>Fecha de creacion:</strong> {{ $solicitud->created_at?->format('d/m/Y H:i') ?? '-' }}</li>
    </ul>

    <p>
        Ingresa al panel de administracion para revisar la solicitud:
    </p>

    <p style="margin-top: 12px;">
        <a href="{{ $urlPanelSolicitudes }}"
           style="
                display:inline-block;
                padding:10px 14px;
                background:#1d4ed8;
                color:#ffffff;
                text-decoration:none;
                border-radius:8px;
                font-weight:700;
            ">
            Ver solicitudes de ubicacion
        </a>
    </p>

    <p style="color:#6b7280; font-size: 12px; margin-top: 18px;">
        (Este correo es automatico. No respondas a este mensaje.)
    </p>

</body>
</html>
