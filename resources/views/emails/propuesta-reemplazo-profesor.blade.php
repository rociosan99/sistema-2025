@php
    $alumno = trim(($turno->alumno?->name ?? '') . ' ' . ($turno->alumno?->apellido ?? '')) ?: 'Alumno';
    $fecha = $turno->reemplazo_fecha?->format('d/m/Y') ?? '-';
    $inicio = substr((string) $turno->reemplazo_hora_inicio, 0, 5);
    $fin = substr((string) $turno->reemplazo_hora_fin, 0, 5);
    $vence = $turno->reemplazo_expires_at?->format('d/m/Y H:i') ?? '-';
@endphp

<div style="font-family:Arial,sans-serif;line-height:1.6;color:#111827;">
    <h2 style="margin:0 0 10px;">Nueva propuesta de reemplazo</h2>

    <p>Te propusieron para reemplazar a otro profesor en una clase.</p>

    <div style="padding:12px;border:1px solid #e5e7eb;border-radius:10px;background:#f9fafb;margin:14px 0;">
        <div><strong>Alumno:</strong> {{ $alumno }}</div>
        <div><strong>Materia:</strong> {{ $turno->materia?->materia_nombre ?? '-' }}</div>
        @if($turno->tema)
            <div><strong>Tema:</strong> {{ $turno->tema->tema_nombre }}</div>
        @endif
        <div><strong>Fecha:</strong> {{ $fecha }}</div>
        <div><strong>Horario:</strong> {{ $inicio }} - {{ $fin }}</div>
        <div><strong>Vence:</strong> {{ $vence }}</div>
    </div>

    <p style="margin:0 0 20px;">
        <a href="{{ $urlPropuesta }}" style="display:inline-block;padding:10px 14px;background:#2563eb;color:#fff;text-decoration:none;border-radius:8px;">
            Ver propuesta
        </a>
    </p>

    <p style="color:#6b7280;font-size:12px;margin:0;">El enlace vence junto con la propuesta.</p>
</div>
