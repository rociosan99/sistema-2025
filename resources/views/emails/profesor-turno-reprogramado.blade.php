@php
    $alumno = $turnoNuevo->alumno;
    $materia = $turnoNuevo->materia;
    $tema = $turnoNuevo->tema;

    $origFecha = $turnoOriginal->fecha?->format('d/m/Y');
    $origIni = substr((string)$turnoOriginal->hora_inicio, 0, 5);
    $origFin = substr((string)$turnoOriginal->hora_fin, 0, 5);

    $newFecha = $turnoNuevo->fecha?->format('d/m/Y');
    $newIni = substr((string)$turnoNuevo->hora_inicio, 0, 5);
    $newFin = substr((string)$turnoNuevo->hora_fin, 0, 5);
@endphp

<div style="font-family: Arial, sans-serif; line-height:1.6; color:#111827;">
    <h2 style="margin:0 0 10px 0;">📅 Clase reprogramada</h2>

    <p style="margin:0 0 10px 0;">
        Hola <strong>{{ $turnoNuevo->profesor?->name }}</strong>,<br>
        un alumno reprogramó una clase.
    </p>

    <div style="padding:12px; border:1px solid #e5e7eb; border-radius:10px; background:#f9fafb; margin:14px 0;">
        <div><strong>Alumno:</strong> {{ $alumno?->name }} {{ $alumno?->apellido }}</div>
        <div><strong>Materia:</strong> {{ $materia?->materia_nombre ?? '—' }}</div>
        @if($tema)
            <div><strong>Tema:</strong> {{ $tema->tema_nombre }}</div>
        @endif
    </div>

    <div style="padding:12px; border:1px solid #fecaca; border-radius:10px; background:#fef2f2; margin:14px 0;">
        <div style="font-weight:800; margin-bottom:6px;">❌ Turno original (cancelado)</div>
        <div><strong>Fecha:</strong> {{ $origFecha }}</div>
        <div><strong>Horario:</strong> {{ $origIni }} - {{ $origFin }}</div>
        <div><strong>ID:</strong> {{ $turnoOriginal->id }}</div>
    </div>

    <div style="padding:12px; border:1px solid #bbf7d0; border-radius:10px; background:#f0fdf4; margin:14px 0;">
        <div style="font-weight:800; margin-bottom:6px;">✅ Nuevo turno</div>
        <div><strong>Fecha:</strong> {{ $newFecha }}</div>
        <div><strong>Horario:</strong> {{ $newIni }} - {{ $newFin }}</div>
        <div><strong>Estado:</strong> {{ $turnoNuevo->estado }}</div>
        <div><strong>ID:</strong> {{ $turnoNuevo->id }}</div>
    </div>

    <p style="margin-top:16px; color:#6b7280; font-size:12px;">
        Este correo fue generado automáticamente.
    </p>
</div>