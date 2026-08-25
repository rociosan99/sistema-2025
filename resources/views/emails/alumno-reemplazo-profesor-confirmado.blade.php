@php
    $profesor = trim(($turno->profesor?->name ?? '') . ' ' . ($turno->profesor?->apellido ?? '')) ?: 'Profesor';
    $fecha = $turno->fecha?->format('d/m/Y') ?? '-';
    $horaInicio = substr((string) $turno->hora_inicio, 0, 5);
    $horaFin = substr((string) $turno->hora_fin, 0, 5);
@endphp

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Profesor reemplazante confirmado</title>
</head>
<body style="margin:0; padding:24px; background:#f3f4f6; font-family:Arial, sans-serif; color:#111827;">
    <div style="max-width:600px; margin:0 auto; padding:28px; border-radius:14px; background:#ffffff; box-shadow:0 8px 24px rgba(15, 23, 42, 0.08);">
        <h2 style="margin:0 0 18px; font-size:22px; line-height:1.35;">
            Tu profesor reemplazante confirmó la clase
        </h2>

        <div style="padding:16px; border:1px solid #e5e7eb; border-radius:10px; background:#f9fafb; line-height:1.7;">
            <div><strong>Profesor:</strong> {{ $profesor }}</div>
            <div><strong>Materia:</strong> {{ $turno->materia?->materia_nombre ?? '-' }}</div>
            @if($turno->tema)
                <div><strong>Tema:</strong> {{ $turno->tema->tema_nombre }}</div>
            @endif
            <div><strong>Fecha:</strong> {{ $fecha }}</div>
            <div><strong>Horario:</strong> {{ $horaInicio }} - {{ $horaFin }}</div>
        </div>

        <p style="margin:18px 0; line-height:1.6;">
            La clase continuará con el profesor reemplazante.
        </p>

        <p style="margin:0 0 20px;">
            <a
                href="{{ $urlTurnos }}"
                style="display:inline-block; padding:11px 18px; border-radius:8px; background:#2563eb; color:#ffffff; font-weight:700; text-decoration:none;"
            >
                Ver mi clase
            </a>
        </p>

        <p style="margin:0; color:#6b7280; font-size:12px; line-height:1.5;">
            Ingresá al Portal Alumno para consultar los datos y acceder al enlace de la clase cuando corresponda.
        </p>
    </div>
</body>
</html>
