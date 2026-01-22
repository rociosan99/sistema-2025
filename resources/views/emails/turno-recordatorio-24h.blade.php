<!doctype html>
<html>
<body>
    <p>Hola {{ $turno->alumno->name }},</p>

    <p>Te recordamos tu clase:</p>
    <ul>
        <li><strong>Fecha:</strong> {{ $turno->fecha_formateada }}</li>
        <li><strong>Horario:</strong> {{ $turno->horario }}</li>
        <li><strong>Profesor:</strong> {{ $turno->profesor->name }}</li>
        <li><strong>Materia:</strong> {{ $turno->materia->materia_nombre ?? $turno->materia->nombre ?? '-' }}</li>
        <li><strong>Tema:</strong> {{ $turno->tema->tema_nombre ?? '-' }}</li>
    </ul>

    <p>Confirmá si vas a asistir:</p>

    <p>
        <a href="{{ $confirmUrl }}" style="padding:10px 14px;background:#16a34a;color:white;text-decoration:none;border-radius:6px;">
            Confirmar asistencia
        </a>
    </p>

    <p>
        <a href="{{ $cancelUrl }}" style="padding:10px 14px;background:#dc2626;color:white;text-decoration:none;border-radius:6px;">
            Cancelar
        </a>
    </p>

    <p>Si confirmás, el turno quedará <strong>pendiente de pago</strong>.</p>
</body>
</html>
