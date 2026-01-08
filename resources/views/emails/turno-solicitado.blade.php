<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
</head>
<body>
    <h2>Nueva solicitud de turno</h2>

    <p>
        El alumno <strong>{{ $turno->alumno->name }}</strong>
        solicitó un turno.
    </p>

    <ul>
        <li><strong>Materia:</strong> {{ $turno->materia->materia_nombre }}</li>
        <li><strong>Fecha:</strong> {{ $turno->fecha }}</li>
        <li><strong>Horario:</strong> {{ $turno->hora_inicio }} - {{ $turno->hora_fin }}</li>
        <li><strong>Estado:</strong> {{ $turno->estado }}</li>
    </ul>

    <p>
        Ingresá al panel para confirmar o rechazar el turno.
    </p>
</body>
</html>
