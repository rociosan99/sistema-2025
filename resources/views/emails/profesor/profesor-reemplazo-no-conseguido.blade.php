<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>No se consiguió reemplazo</title>
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.5;">
    <h2>No se consiguió reemplazo</h2>

    @php
        $alumno = trim(($turnoCancelado->alumno?->name ?? '') . ' ' . ($turnoCancelado->alumno?->apellido ?? ''));
        if ($alumno === '') $alumno = $turnoCancelado->alumno?->name ?? 'Alumno';

        $fecha = $turnoCancelado->fecha?->format('d/m/Y') ?? $turnoCancelado->fecha;
        $desde = substr((string) $turnoCancelado->hora_inicio, 0, 5);
        $hasta = substr((string) $turnoCancelado->hora_fin, 0, 5);
    @endphp

    <p>La clase fue cancelada y el sistema intentó encontrar un reemplazo, pero <strong>no hubo alumnos disponibles</strong>.</p>

    <ul>
        <li><strong>Alumno que canceló:</strong> {{ $alumno }}</li>
        <li><strong>Materia:</strong> {{ $turnoCancelado->materia?->materia_nombre ?? '-' }}</li>
        <li><strong>Tema:</strong> {{ $turnoCancelado->tema?->tema_nombre ?? 'Sin tema' }}</li>
        <li><strong>Fecha:</strong> {{ $fecha }}</li>
        <li><strong>Horario:</strong> {{ $desde }} - {{ $hasta }}</li>
        <li><strong>Tipo de cancelación:</strong> {{ $turnoCancelado->cancelacion_tipo === 'sin_cargo' ? 'Con anticipación' : 'Cerca del horario' }}</li>
    </ul>

    <p style="color:#6b7280; font-size: 12px;">
        (Correo automático)
    </p>
</body>
</html>