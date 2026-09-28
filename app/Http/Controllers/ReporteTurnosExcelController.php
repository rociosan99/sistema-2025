<?php

namespace App\Http\Controllers;

use App\Filament\Pages\Reportes;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReporteTurnosExcelController extends Controller
{
    public function __invoke(Request $request): StreamedResponse
    {
        $fechaInicio = (string) $request->query('fechaInicio', now()->subDays(30)->toDateString());
        $fechaFin = (string) $request->query('fechaFin', now()->toDateString());
        $estado = (string) $request->query('estado', '');
        $emitidoEn = now();
        $usuario = $request->user();
        $emitidoPor = trim(implode(' ', array_filter([
            $usuario?->name,
            $usuario?->apellido,
        ])));

        if ($emitidoPor === '') {
            $emitidoPor = (string) ($usuario?->email ?? 'Administrador');
        }

        $desde = $fechaInicio !== '' ? Carbon::parse($fechaInicio)->format('d/m/Y') : '';
        $hasta = $fechaFin !== '' ? Carbon::parse($fechaFin)->format('d/m/Y') : '';
        $periodo = $desde && $hasta
            ? $desde.' al '.$hasta
            : ($desde ? 'Desde '.$desde : ($hasta ? 'Hasta '.$hasta : 'Todos los registros'));

        $rows = $this->getTurnosRows($fechaInicio, $fechaFin, $estado);
        $filename = 'reporte_turnos_' . $emitidoEn->format('Ymd_His') . '.csv';
        $encabezado = [
            ['TutorLine'],
            ['Reporte de turnos'],
            [],
            ['Emitido por:', $this->sanitizeUtf8($emitidoPor)],
            ['Rol:', 'Administrador'],
            ['Fecha de emisión:', $emitidoEn->format('d/m/Y')],
            ['Hora de emisión:', $emitidoEn->format('H:i')],
            [],
            ['Criterios del reporte'],
            ['Período analizado:', $periodo],
            ['Desde:', $desde],
            ['Hasta:', $hasta],
            ['Estado:', $estado !== '' ? Reportes::estadoLabel($estado) : 'Todos'],
            ['Total de registros:', count($rows)],
            [],
        ];

        return response()->streamDownload(function () use ($rows, $encabezado) {
            $handle = fopen('php://output', 'w');

            fprintf($handle, chr(0xEF) . chr(0xBB) . chr(0xBF));

            foreach ($encabezado as $fila) {
                fputcsv($handle, $fila, ';', '"', '');
            }

            fputcsv($handle, [
                'ID',
                'Alumno',
                'Profesor',
                'Materia',
                'Tema',
                'Fecha',
                'Hora inicio',
                'Hora fin',
                'Estado',
                'Precio total',
            ], ';', '"', '');

            foreach ($rows as $row) {
                fputcsv($handle, [
                    $row['id'],
                    $row['alumno'],
                    $row['profesor'],
                    $row['materia'],
                    $row['tema'],
                    Carbon::parse($row['fecha'])->format('d/m/Y'),
                    $row['hora_inicio'],
                    $row['hora_fin'],
                    Reportes::estadoLabel($row['estado']),
                    $row['precio_total'],
                ], ';', '"', '');
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    private function getTurnosQuery(string $fechaInicio, string $fechaFin, string $estado): Builder
    {
        $query = DB::table('turnos as t')
            ->join('users as a', 'a.id', '=', 't.alumno_id')
            ->join('users as p', 'p.id', '=', 't.profesor_id')
            ->join('materias as m', 'm.materia_id', '=', 't.materia_id')
            ->leftJoin('temas as te', 'te.tema_id', '=', 't.tema_id')
            ->selectRaw("
                t.id,
                CONCAT(a.name, ' ', COALESCE(a.apellido, '')) as alumno,
                CONCAT(p.name, ' ', COALESCE(p.apellido, '')) as profesor,
                m.materia_nombre as materia,
                te.tema_nombre as tema,
                t.fecha,
                t.hora_inicio,
                t.hora_fin,
                t.estado,
                t.precio_total
            ")
            ->whereBetween('t.fecha', [$fechaInicio, $fechaFin]);

        if ($estado !== '') {
            $query->where('t.estado', $estado);
        }

        return $query;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function getTurnosRows(string $fechaInicio, string $fechaFin, string $estado): array
    {
        $rows = $this->getTurnosQuery($fechaInicio, $fechaFin, $estado)
            ->orderByDesc('t.fecha')
            ->orderByDesc('t.hora_inicio')
            ->limit(300)
            ->get();

        return $rows->map(function ($r) {
            return [
                'id' => (int) $r->id,
                'alumno' => $this->sanitizeUtf8(trim((string) $r->alumno)),
                'profesor' => $this->sanitizeUtf8(trim((string) $r->profesor)),
                'materia' => $this->sanitizeUtf8((string) $r->materia),
                'tema' => $this->sanitizeUtf8($r->tema ? (string) $r->tema : '-'),
                'fecha' => (string) $r->fecha,
                'hora_inicio' => substr((string) $r->hora_inicio, 0, 5),
                'hora_fin' => substr((string) $r->hora_fin, 0, 5),
                'estado' => (string) $r->estado,
                'precio_total' => (float) $r->precio_total,
            ];
        })->all();
    }

    private function sanitizeUtf8(?string $value): string
    {
        if ($value === null) {
            return '';
        }

        $value = trim($value);
        $value = str_replace(["\xC2\xA0"], ' ', $value);
        $value = str_replace(['—', '–', '´', '`'], ['-', '-', "'", "'"], $value);

        if (mb_check_encoding($value, 'UTF-8')) {
            return $value;
        }

        $converted = @mb_convert_encoding($value, 'UTF-8', 'UTF-8, ISO-8859-1, Windows-1252');

        if (is_string($converted) && mb_check_encoding($converted, 'UTF-8')) {
            return $converted;
        }

        $clean = @iconv('UTF-8', 'UTF-8//IGNORE', $value);

        return is_string($clean) ? $clean : '';
    }
}
