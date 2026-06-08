@php
    $profesor = $turno->profesor;
    $materia = $turno->materia;
    $tema = $turno->tema;
    $fecha = $turno->fecha?->format('d/m/Y');
    $inicio = substr((string) $turno->hora_inicio, 0, 5);
    $fin = substr((string) $turno->hora_fin, 0, 5);
@endphp

<div style="font-family: Arial, sans-serif; line-height:1.6; color:#111827;">
    <h2 style="margin:0 0 10px 0;">🚨 Clase suspendida por el profesor</h2>

    <p style="margin:0 0 10px 0;">
        Hola <strong>{{ $turno->alumno?->name }}</strong>,<br>
        el profesor {{ $profesor?->name }} {{ $profesor?->apellido }} suspendió tu clase.
    </p>

    <div style="padding:12px; border:1px solid #e5e7eb; border-radius:10px; background:#f9fafb; margin:14px 0;">
        <div><strong>Materia:</strong> {{ $materia?->materia_nombre ?? '—' }}</div>
        @if($tema)
            <div><strong>Tema:</strong> {{ $tema->tema_nombre }}</div>
        @endif
        <div><strong>Fecha:</strong> {{ $fecha }}</div>
        <div><strong>Horario:</strong> {{ $inicio }} - {{ $fin }}</div>
        <div><strong>Motivo:</strong> {{ $turno->suspension_motivo ?? 'No informado' }}</div>
    </div>

    <p style="margin:0 0 10px 0;">
        El pago sigue vigente y podés reprogramar la clase con el mismo profesor.
    </p>

    <p style="margin:0 0 20px 0;">
        <a href="{{ $urlReprogramar }}" style="display:inline-block;padding:10px 14px;background:#10b981;color:#fff;text-decoration:none;border-radius:8px;">
            Reprogramar clase
        </a>
    </p>

    <p style="color:#6b7280;font-size:12px; margin:0;">
        Este correo fue generado automáticamente.
    </p>
</div>
