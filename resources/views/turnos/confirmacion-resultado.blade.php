<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $titulo }}</title>

    @if (!empty($autoRedirect) && !empty($redirectUrl))
        <meta http-equiv="refresh" content="{{ max(1, (int) ceil(($redirectDelay ?? 2000) / 1000)) }};url={{ $redirectUrl }}">
    @endif
</head>
<body style="font-family: Arial, sans-serif; padding: 24px;">
    <h2>{{ $titulo }}</h2>
    <p>{{ $mensaje }}</p>

    @if (!empty($autoRedirect) && !empty($redirectUrl))
        <p style="margin-top: 24px;">
            En 2 segundos volverás automáticamente a la pantalla de turnos.
        </p>
    @else
        <p style="margin-top: 24px;">
            Podés volver al sistema desde tu panel.
        </p>

        <p style="margin-top: 12px;">
            <a href="{{ $redirectUrl ?? url('/alumno/turnos') }}">
                Volver a mis turnos
            </a>
        </p>
    @endif

    @if (!empty($autoRedirect) && !empty($redirectUrl))
        <script>
            setTimeout(function () {
                const redirectUrl = @json($redirectUrl);

                if (window.opener && !window.opener.closed) {
                    try {
                        window.opener.location.href = redirectUrl;
                        window.close();

                        setTimeout(function () {
                            window.location.href = redirectUrl;
                        }, 300);

                        return;
                    } catch (e) {
                        // Si no se puede acceder o cerrar, redirige esta pestaña.
                    }
                }

                window.location.href = redirectUrl;
            }, {{ (int) ($redirectDelay ?? 2000) }});
        </script>
    @endif
</body>
</html>