<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reporte de Turnos</title>
    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 12px;
            color: #111827;
        }

        h1 {
            font-size: 18px;
            margin-bottom: 6px;
        }

        .meta {
            margin-bottom: 14px;
            font-size: 11px;
            color: #374151;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th, td {
            border: 1px solid #d1d5db;
            padding: 6px;
            font-size: 11px;
        }

        th {
            background: #111827;
            color: white;
            text-align: left;
        }

        .right {
            text-align: right;
        }
    </style>
</head>
<body>
    <h1>Reporte de turnos</h1>

    <div class="meta">
        Desde: {{ $fechaInicio }} |
        Hasta: {{ $fechaFin }} |
        Estado: {{ $estado !== '' ? $estado : 'Todos' }}
    </div>

    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Alumno</th>
                <th>Profesor</th>
                <th>Materia</th>
                <th>Tema</th>
                <th>Fecha</th>
                <th>Hora inicio</th>
                <th>Hora fin</th>
                <th>Estado</th>
                <th class="right">Precio total</th>
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
                    <td>{{ $row['fecha'] }}</td>
                    <td>{{ $row['hora_inicio'] }}</td>
                    <td>{{ $row['hora_fin'] }}</td>
                    <td>{{ $row['estado'] }}</td>
                    <td class="right">${{ number_format($row['precio_total'], 2, ',', '.') }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="10">Sin datos.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>