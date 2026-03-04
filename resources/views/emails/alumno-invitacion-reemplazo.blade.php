<!doctype html>
<html>
<body style="font-family: Arial, sans-serif; line-height:1.5;">
    <h2>¡Se liberó una clase disponible!</h2>

    <p>
        Hay una clase disponible para tu solicitud:
        <br><strong>Fecha:</strong> {{ $inv->fecha->format('d/m/Y') }}
        <br><strong>Horario:</strong> {{ substr($inv->hora_inicio,0,5) }} - {{ substr($inv->hora_fin,0,5) }}
        <br><strong>Materia:</strong> {{ optional($inv->materia)->materia_nombre ?? '—' }}
        @if($inv->tema)
            <br><strong>Tema:</strong> {{ $inv->tema->tema_nombre }}
        @endif
    </p>

    <p>Podés aceptarla o rechazarla:</p>

    <p>
        <a href="{{ $urlAceptar }}"
           style="display:inline-block;padding:10px 14px;background:#16a34a;color:#fff;text-decoration:none;border-radius:8px;">
            Aceptar
        </a>

        <a href="{{ $urlRechazar }}"
           style="display:inline-block;padding:10px 14px;background:#dc2626;color:#fff;text-decoration:none;border-radius:8px;margin-left:8px;">
            Rechazar
        </a>
    </p>

    <p style="color:#6b7280;font-size:12px;">
        Este link expira cuando vence la invitación.
    </p>
</body>
</html>