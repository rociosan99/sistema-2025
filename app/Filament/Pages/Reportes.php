<?php

namespace App\Filament\Pages;

use App\Models\Turno;
use Filament\Pages\Page;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class Reportes extends Page
{
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-document-text';
    protected static ?string $navigationLabel = 'Reportes';
    protected static ?string $title = 'Reportes';
    protected static ?string $slug = 'reportes';
    protected static string|\UnitEnum|null $navigationGroup = 'General';

    protected string $view = 'filament.pages.reportes';

    public ?string $fechaInicio = null;
    public ?string $fechaFin = null;
    public ?string $estado = '';

    /** @var array<int, array<string, mixed>> */
    public array $turnos = [];

    public function mount(): void
    {
        $this->fechaFin = now()->toDateString();
        $this->fechaInicio = now()->subDays(30)->toDateString();
        $this->estado = '';

        $this->cargarDatos();
    }

    public function aplicarFiltros(): void
    {
        $this->validarFechas();
        $this->cargarDatos();
    }

    private function validarFechas(): void
    {
        if (! $this->fechaInicio || ! $this->fechaFin) {
            throw ValidationException::withMessages([
                'fechas' => 'Seleccioná fecha inicio y fecha fin.',
            ]);
        }

        if ($this->fechaInicio > $this->fechaFin) {
            throw ValidationException::withMessages([
                'fechas' => 'La fecha inicio no puede ser mayor que la fecha fin.',
            ]);
        }
    }

    private function getTurnosQuery(): Builder
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
            ->whereBetween('t.fecha', [$this->fechaInicio, $this->fechaFin]);

        if (! empty($this->estado)) {
            $query->where('t.estado', $this->estado);
        }

        return $query;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function getTurnosRows(): array
    {
        $rows = $this->getTurnosQuery()
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

    private function cargarDatos(): void
    {
        $this->turnos = $this->getTurnosRows();
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

    public function getPdfUrl(): string
    {
        return route('reportes.turnos.pdf', [
            'fechaInicio' => $this->fechaInicio,
            'fechaFin' => $this->fechaFin,
            'estado' => $this->estado,
        ]);
    }

    public function getExcelUrl(): string
    {
        return route('reportes.turnos.excel', [
            'fechaInicio' => $this->fechaInicio,
            'fechaFin' => $this->fechaFin,
            'estado' => $this->estado,
        ]);
    }

    public static function estadoLabel(string $estado): string
    {
        return match ($estado) {
            Turno::ESTADO_PENDIENTE => 'Pendiente',
            Turno::ESTADO_PENDIENTE_PAGO => 'Pendiente de pago',
            Turno::ESTADO_CONFIRMADO => 'Clase pagada',
            Turno::ESTADO_RECHAZADO => 'Rechazado',
            Turno::ESTADO_CANCELADO => 'Cancelado',
            Turno::ESTADO_VENCIDO => 'Vencido',
            Turno::ESTADO_ACEPTADO => 'Aceptado (legacy)',
            default => ucfirst($estado),
        };
    }

    public static function estadoBadgeColors(string $estado): array
    {
        return match ($estado) {
            Turno::ESTADO_PENDIENTE => ['#fef3c7', '#92400e'],
            Turno::ESTADO_PENDIENTE_PAGO => ['#dbeafe', '#1d4ed8'],
            Turno::ESTADO_CONFIRMADO => ['#dcfce7', '#166534'],
            Turno::ESTADO_RECHAZADO => ['#fee2e2', '#991b1b'],
            Turno::ESTADO_CANCELADO => ['#f3f4f6', '#374151'],
            Turno::ESTADO_VENCIDO => ['#e5e7eb', '#4b5563'],
            Turno::ESTADO_ACEPTADO => ['#ede9fe', '#6d28d9'],
            default => ['#f3f4f6', '#374151'],
        };
    }
}