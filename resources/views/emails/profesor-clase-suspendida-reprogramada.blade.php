@php
    $alumno = $turno->alumno;
    $materia = $turno->materia;
    $tema = $turno->tema;
    $fecha = $turno->fecha?->format('d/m/Y');
    $inicio = substr((string) $turno->hora_inicio, 0, 5);
    $fin = substr((string) $turno->hora_fin, 0, 5);
@endphp

<div style="font-family: Arial, sans-serif; line-height:1.6; color:#111827;">
    <h2 style="margin:0 0 10px 0;">✅ Clase suspendida reprogramada</h2>

    <p style="margin:0 0 10px 0;">
        Hola <strong>{{ $turno->profesor?->name }}</strong>,<br>
        la clase suspendida fue reprogramada por el alumno.
    </p>

    <div style="padding:12px; border:1px solid #e5e7eb; border-radius:10px; background:#f9fafb; margin:14px 0;">
        <div><strong>Alumno:</strong> {{ $alumno?->name }} {{ $alumno?->apellido }}</div>
        <div><strong>Materia:</strong> {{ $materia?->materia_nombre ?? '—' }}</div>
        @if($tema)
            <div><strong>Tema:</strong> {{ $tema->tema_nombre }}</div>
        @endif
    </div>

    <div style="padding:12px; border:1px solid #bbf7d0; border-radius:10px; background:#f0fdf4; margin:14px 0;">
        <div><strong>Nueva fecha:</strong> {{ $fecha }}</div>
        <div><strong>Nuevo horario:</strong> {{ $inicio }} - {{ $fin }}</div>
    </div>

    <p style="margin:0 0 10px 0;">
        Esta clase suspendida fue reprogramada exitosamente.
    </p>

    <p style="color:#6b7280;font-size:12px; margin:0;">
        Este correo fue generado automáticamente.
    </p>
</div>
