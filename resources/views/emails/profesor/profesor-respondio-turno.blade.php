<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Respuesta del profesor</title>
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.5;">

    @php
        $nombreProfesor = trim(($turno->profesor?->name ?? '') . ' ' . ($turno->profesor?->apellido ?? ''));
        if ($nombreProfesor === '') $nombreProfesor = $turno->profesor?->name ?? 'Profesor';

        $estado = (string) $turno->estado;

        $estadoTexto = match ($estado) {
            \App\Models\Turno::ESTADO_PENDIENTE_PAGO => 'aceptó tu solicitud (queda pendiente de pago)',
            \App\Models\Turno::ESTADO_RECHAZADO => 'rechazó tu solicitud',
            default => 'actualizó el estado de tu solicitud',
        };
    @endphp

    <h2>Respuesta del profesor</h2>

    <p>
        El profesor <strong>{{ $nombreProfesor }}</strong> {{ $estadoTexto }}.
    </p>

    <ul>
        <li><strong>Materia:</strong> {{ $turno->materia?->materia_nombre ?? '-' }}</li>

        @if($turno->tema)
            <li><strong>Tema:</strong> {{ $turno->tema?->tema_nombre ?? '-' }}</li>
        @endif

        <li><strong>Fecha:</strong> {{ $turno->fecha?->format('d/m/Y') ?? $turno->fecha }}</li>
        <li><strong>Horario:</strong> {{ substr((string)$turno->hora_inicio, 0, 5) }} - {{ substr((string)$turno->hora_fin, 0, 5) }}</li>
        <li><strong>Estado actual:</strong> {{ ucfirst($estado) }}</li>
    </ul>

    @if($estado === \App\Models\Turno::ESTADO_PENDIENTE_PAGO)
        <p>Ya podés ingresar al panel para <strong>realizar el pago</strong>.</p>
    @elseif($estado === \App\Models\Turno::ESTADO_RECHAZADO)
        <p>Podés solicitar otro turno en un horario diferente.</p>
    @else
        <p>Podés revisar el estado del turno desde tu panel.</p>
    @endif

    <p style="margin-top: 12px;">
        <a href="{{ $urlPanelAlumno }}"
           style="display:inline-block; padding:10px 14px; background:#16a34a; color:#ffffff; text-decoration:none; border-radius:8px; font-weight:700;">
            Ir a mis turnos
        </a>
    </p>

    <p style="color:#6b7280; font-size: 12px; margin-top: 18px;">
        (Este correo es automático. No respondas a este mensaje.)
    </p>

</body>
</html>
