<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Nueva solicitud de turno</title>
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.5;">

    <h2>Nueva solicitud de turno</h2>

    @php
        $nombreAlumno = trim(($turno->alumno?->name ?? '') . ' ' . ($turno->alumno?->apellido ?? ''));
        if ($nombreAlumno === '') $nombreAlumno = $turno->alumno?->name ?? 'Alumno';
    @endphp

    <p>
        El alumno <strong>{{ $nombreAlumno }}</strong> solicitó un turno.
    </p>

    <ul>
        @if(!empty($institucionNombre))
            <li><strong>Institución:</strong> {{ $institucionNombre }}</li>
        @endif

        @if(!empty($carreraNombre))
            <li><strong>Carrera:</strong> {{ $carreraNombre }}</li>
        @endif

        <li><strong>Materia:</strong> {{ $turno->materia?->materia_nombre ?? '-' }}</li>

        @if($turno->tema)
            <li><strong>Tema:</strong> {{ $turno->tema?->tema_nombre ?? '-' }}</li>
        @endif

        <li><strong>Fecha:</strong> {{ $turno->fecha?->format('d/m/Y') ?? $turno->fecha }}</li>
        <li><strong>Horario:</strong> {{ substr((string)$turno->hora_inicio, 0, 5) }} - {{ substr((string)$turno->hora_fin, 0, 5) }}</li>
        <li><strong>Estado:</strong> {{ ucfirst((string) $turno->estado) }}</li>
    </ul>

    <p>
        Ingresá al panel del profesor para <strong>aceptar o rechazar</strong> esta solicitud:
    </p>

    <p style="margin-top: 12px;">
        <a href="{{ $urlPanelProfesor }}"
           style="
                display:inline-block;
                padding:10px 14px;
                background:#1d4ed8;
                color:#ffffff;
                text-decoration:none;
                border-radius:8px;
                font-weight:700;
            ">
            Ir a solicitudes (Aceptar / Rechazar)
        </a>
    </p>

    <p style="color:#6b7280; font-size: 12px; margin-top: 18px;">
        (Este correo es automático. No respondas a este mensaje.)
    </p>

</body>
</html>
