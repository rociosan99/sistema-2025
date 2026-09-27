<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reporte de Turnos</title>
    <style>
        @page { size: landscape; margin: 28px 30px 45px; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 10px; color: #111827; }
        .encabezado { text-align: center; border-bottom: 2px solid #111827; padding-bottom: 10px; margin-bottom: 12px; }
        .sistema { font-size: 18px; font-weight: bold; text-transform: uppercase; }
        .titulo { font-size: 14px; font-weight: bold; margin-top: 4px; }
        .identificacion { width: 100%; margin-bottom: 10px; }
        .identificacion td { border: 0; padding: 2px 5px; width: 50%; }
        .bloque { border: 1px solid #d1d5db; padding: 8px; margin-bottom: 10px; }
        .bloque-titulo { font-size: 10px; font-weight: bold; margin-bottom: 5px; }
        table.detalle { width: 100%; border-collapse: collapse; }
        .detalle th, .detalle td { border: 1px solid #d1d5db; padding: 5px; font-size: 8px; }
        .detalle th { background: #111827; color: white; text-align: left; }
        .right { text-align: right; }
        .pie { position: fixed; bottom: -28px; left: 0; right: 0; border-top: 1px solid #9ca3af; padding-top: 6px; text-align: center; font-size: 8px; color: #4b5563; }
    </style>
</head>
<body>
    @php
        $periodo = $fechaInicio && $fechaFin
            ? \Illuminate\Support\Carbon::parse($fechaInicio)->format('d/m/Y').' al '.\Illuminate\Support\Carbon::parse($fechaFin)->format('d/m/Y')
            : ($fechaInicio
                ? 'Desde '.\Illuminate\Support\Carbon::parse($fechaInicio)->format('d/m/Y')
                : ($fechaFin ? 'Hasta '.\Illuminate\Support\Carbon::parse($fechaFin)->format('d/m/Y') : 'Todos los registros'));
    @endphp

    <div class="encabezado">
        <div class="sistema">{{ $nombreSistema }}</div>
        <div class="titulo">REPORTE DE TURNOS</div>
    </div>

    <table class="identificacion">
        <tr><td><strong>Reporte:</strong> Reporte de turnos</td><td><strong>Período analizado:</strong> {{ $periodo }}</td></tr>
        <tr><td><strong>Emitido por:</strong> {{ $emitidoPor }}</td><td><strong>Rol:</strong> Administrador</td></tr>
        <tr><td><strong>Fecha de emisión:</strong> {{ $fechaEmision }}</td><td><strong>Hora de emisión:</strong> {{ $horaEmision }}</td></tr>
    </table>

    <div class="bloque">
        <div class="bloque-titulo">CRITERIOS DEL REPORTE</div>
        <div><strong>Período:</strong> {{ $periodo }}</div>
        <div><strong>Estado:</strong> {{ $estadoLabel }}</div>
    </div>

    <div class="bloque">
        <div class="bloque-titulo">RESUMEN</div>
        <div><strong>Total de registros:</strong> {{ count($turnos) }}</div>
    </div>

    <table class="detalle">
        <thead>
            <tr>
                <th>ID</th><th>Alumno</th><th>Profesor</th><th>Materia</th><th>Tema</th>
                <th>Fecha</th><th>Hora inicio</th><th>Hora fin</th><th>Estado</th><th class="right">Precio total</th>
            </tr>
        </thead>
        <tbody>
            @forelse($turnos as $row)
                <tr>
                    <td>{{ $row['id'] }}</td>
                    <td>{{ $row['alumno'] }}</td>
                    <td>{{ $row['profesor'] }}</td>
                    <td>{{ $row['materia'] }}</td>
                    <td>{{ $row['tema'] }}</td>
                    <td>{{ \Illuminate\Support\Carbon::parse($row['fecha'])->format('d/m/Y') }}</td>
                    <td>{{ $row['hora_inicio'] }}</td>
                    <td>{{ $row['hora_fin'] }}</td>
                    <td>{{ \App\Filament\Pages\Reportes::estadoLabel($row['estado']) }}</td>
                    <td class="right">${{ number_format($row['precio_total'], 2, ',', '.') }}</td>
                </tr>
            @empty
                <tr><td colspan="10">Sin datos.</td></tr>
            @endforelse
        </tbody>
    </table>

    <div class="pie">
        Documento generado por {{ $nombreSistema }} · Fecha y hora de emisión: {{ $fechaEmision }} {{ $horaEmision }} · Emitido por: {{ $emitidoPor }}
    </div>
</body>
</html>
