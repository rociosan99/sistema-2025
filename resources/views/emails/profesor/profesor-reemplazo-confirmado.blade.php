<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Clase reasignada</title>
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.5;">
    <h2>Clase reasignada</h2>

    @php
        $alumnoNuevo = trim(($turnoNuevo->alumno?->name ?? '') . ' ' . ($turnoNuevo->alumno?->apellido ?? ''));
        if ($alumnoNuevo === '') $alumnoNuevo = $turnoNuevo->alumno?->name ?? 'Alumno';

        $fecha = $turnoNuevo->fecha?->format('d/m/Y') ?? $turnoNuevo->fecha;
        $desde = substr((string) $turnoNuevo->hora_inicio, 0, 5);
        $hasta = substr((string) $turnoNuevo->hora_fin, 0, 5);
    @endphp

    <p>Se liberó un horario por una cancelación y <strong>otro alumno tomó esa clase</strong>.</p>

    <ul>
        <li><strong>Alumno nuevo:</strong> {{ $alumnoNuevo }}</li>
        <li><strong>Materia:</strong> {{ $turnoNuevo->materia?->materia_nombre ?? '-' }}</li>
        <li><strong>Tema:</strong> {{ $turnoNuevo->tema?->tema_nombre ?? 'Sin tema' }}</li>
        <li><strong>Fecha:</strong> {{ $fecha }}</li>
        <li><strong>Horario:</strong> {{ $desde }} - {{ $hasta }}</li>
        <li><strong>Estado:</strong> {{ ucfirst($turnoNuevo->estado) }}</li>
    </ul>

    <p style="color:#6b7280; font-size: 12px;">
        (Correo automático)
    </p>
</body>
</html>