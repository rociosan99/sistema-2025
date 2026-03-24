<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class Estadisticas extends Page
{
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-chart-bar';
    protected static ?string $navigationLabel = 'Estadísticas';
    protected static ?string $title = 'Estadísticas';
    protected static ?string $slug = 'estadisticas';
    protected static string|\UnitEnum|null $navigationGroup = 'General';

    protected string $view = 'filament.pages.estadisticas';

    public ?string $dimension = 'materias';
    public ?string $metrica = 'solicitados';
    public ?string $fechaInicio = null;
    public ?string $fechaFin = null;

    public array $resumen = [];
    public array $tabla = [];
    public array $chartLabels = [];
    public array $chartValues = [];
    public array $columnas = [];

    public function mount(): void
    {
        $this->fechaFin = now()->toDateString();
        $this->fechaInicio = now()->subDays(30)->toDateString();

        $this->cargarDatos();
    }

    public function updated($property): void
    {
        if (in_array($property, ['dimension', 'metrica', 'fechaInicio', 'fechaFin'])) {
            if ($this->fechaInicio && $this->fechaFin && $this->fechaInicio <= $this->fechaFin) {
                $this->cargarDatos();
            }
        }
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

    private function cargarDatos(): void
    {
        $this->validarFechas();

        $this->cargarResumen();

        match ($this->dimension) {
            'materias' => $this->cargarMaterias(),
            'temas' => $this->cargarTemas(),
            'estados' => $this->cargarEstados(),
            'profesores' => $this->cargarProfesores(),
            default => $this->cargarMaterias(),
        };

        $this->dispatch('estadisticas-actualizadas');
    }

    private function cargarResumen(): void
    {
        $desde = $this->fechaInicio;
        $hasta = $this->fechaFin;

        $base = DB::table('turnos')
            ->whereBetween('fecha', [$desde, $hasta]);

        $this->resumen = [
            'total' => (clone $base)->count(),
            'confirmados' => (clone $base)->where('estado', 'confirmado')->count(),
            'cancelados' => (clone $base)->where('estado', 'cancelado')->count(),
            'vencidos' => (clone $base)->where('estado', 'vencido')->count(),
        ];
    }

    private function cargarMaterias(): void
    {
        $desde = $this->fechaInicio;
        $hasta = $this->fechaFin;

        $rows = DB::table('turnos as t')
            ->join('materias as m', 'm.materia_id', '=', 't.materia_id')
            ->selectRaw("
                m.materia_id,
                m.materia_nombre as nombre,
                COUNT(*) as solicitados,
                SUM(CASE WHEN t.estado = 'confirmado' THEN 1 ELSE 0 END) as confirmados,
                SUM(CASE WHEN t.estado = 'cancelado' THEN 1 ELSE 0 END) as cancelados,
                SUM(CASE WHEN t.estado = 'vencido' THEN 1 ELSE 0 END) as vencidos
            ")
            ->whereBetween('t.fecha', [$desde, $hasta])
            ->groupBy('m.materia_id', 'm.materia_nombre')
            ->orderByDesc($this->mapMetrica())
            ->get()
            ->map(fn ($r) => [
                'nombre' => (string) $r->nombre,
                'solicitados' => (int) $r->solicitados,
                'confirmados' => (int) $r->confirmados,
                'cancelados' => (int) $r->cancelados,
                'vencidos' => (int) $r->vencidos,
            ])
            ->values();

        $this->setSalidaComun($rows->all(), 'nombre');
    }

    private function cargarTemas(): void
    {
        $desde = $this->fechaInicio;
        $hasta = $this->fechaFin;

        $rows = DB::table('turnos as t')
            ->join('materias as m', 'm.materia_id', '=', 't.materia_id')
            ->leftJoin('temas as te', 'te.tema_id', '=', 't.tema_id')
            ->selectRaw("
                COALESCE(te.tema_nombre, CONCAT('Temas de ', m.materia_nombre)) as nombre,
                COUNT(*) as solicitados,
                SUM(CASE WHEN t.estado = 'confirmado' THEN 1 ELSE 0 END) as confirmados,
                SUM(CASE WHEN t.estado = 'cancelado' THEN 1 ELSE 0 END) as cancelados,
                SUM(CASE WHEN t.estado = 'vencido' THEN 1 ELSE 0 END) as vencidos
            ")
            ->whereBetween('t.fecha', [$desde, $hasta])
            ->groupBy(DB::raw("COALESCE(te.tema_nombre, CONCAT('Temas de ', m.materia_nombre))"))
            ->orderByDesc($this->mapMetrica())
            ->get()
            ->map(fn ($r) => [
                'nombre' => (string) $r->nombre,
                'solicitados' => (int) $r->solicitados,
                'confirmados' => (int) $r->confirmados,
                'cancelados' => (int) $r->cancelados,
                'vencidos' => (int) $r->vencidos,
            ])
            ->values();

        $this->setSalidaComun($rows->all(), 'nombre');
    }

    private function cargarEstados(): void
    {
        $desde = $this->fechaInicio;
        $hasta = $this->fechaFin;

        $rows = DB::table('turnos')
            ->selectRaw("
                estado as nombre,
                COUNT(*) as total
            ")
            ->whereBetween('fecha', [$desde, $hasta])
            ->groupBy('estado')
            ->orderByDesc('total')
            ->get()
            ->map(fn ($r) => [
                'nombre' => (string) $r->nombre,
                'total' => (int) $r->total,
            ])
            ->values();

        $this->tabla = $rows->all();
        $this->chartLabels = $rows->pluck('nombre')->all();
        $this->chartValues = $rows->pluck('total')->all();
        $this->columnas = ['nombre', 'total'];
    }

    private function cargarProfesores(): void
    {
        $desde = $this->fechaInicio;
        $hasta = $this->fechaFin;

        $rows = DB::table('turnos as t')
            ->join('users as u', 'u.id', '=', 't.profesor_id')
            ->selectRaw("
                CONCAT(u.name, ' ', COALESCE(u.apellido, '')) as nombre,
                u.email,
                COUNT(*) as solicitados,
                SUM(CASE WHEN t.estado = 'confirmado' THEN 1 ELSE 0 END) as confirmados,
                SUM(CASE WHEN t.estado = 'cancelado' THEN 1 ELSE 0 END) as cancelados,
                SUM(CASE WHEN t.estado = 'vencido' THEN 1 ELSE 0 END) as vencidos
            ")
            ->where('u.role', 'profesor')
            ->whereBetween('t.fecha', [$desde, $hasta])
            ->groupBy('u.id', 'u.name', 'u.apellido', 'u.email')
            ->orderByDesc($this->mapMetrica())
            ->get()
            ->map(fn ($r) => [
                'nombre' => trim((string) $r->nombre),
                'email' => (string) $r->email,
                'solicitados' => (int) $r->solicitados,
                'confirmados' => (int) $r->confirmados,
                'cancelados' => (int) $r->cancelados,
                'vencidos' => (int) $r->vencidos,
            ])
            ->values();

        $this->tabla = $rows->all();
        $this->chartLabels = $rows->pluck('nombre')->take(10)->all();
        $this->chartValues = $rows->pluck($this->mapMetrica())->take(10)->all();
        $this->columnas = ['nombre', 'email', 'solicitados', 'confirmados', 'cancelados', 'vencidos'];
    }

    private function mapMetrica(): string
    {
        return match ($this->metrica) {
            'confirmados' => 'confirmados',
            'cancelados' => 'cancelados',
            'vencidos' => 'vencidos',
            default => 'solicitados',
        };
    }

    private function setSalidaComun(array $rows, string $campoNombre = 'nombre'): void
    {
        $this->tabla = $rows;
        $this->chartLabels = collect($rows)->pluck($campoNombre)->take(10)->values()->all();
        $this->chartValues = collect($rows)->pluck($this->mapMetrica())->take(10)->values()->all();
        $this->columnas = ['nombre', 'solicitados', 'confirmados', 'cancelados', 'vencidos'];
    }

    public function getTituloGraficoProperty(): string
    {
        $dim = match ($this->dimension) {
            'materias' => 'Materias',
            'temas' => 'Temas',
            'estados' => 'Estados',
            'profesores' => 'Profesores',
            default => 'Estadísticas',
        };

        $met = match ($this->metrica) {
            'confirmados' => 'Confirmados',
            'cancelados' => 'Cancelados',
            'vencidos' => 'Vencidos',
            default => 'Solicitudes',
        };

        if ($this->dimension === 'estados') {
            return 'Turnos por Estado';
        }

        return "{$dim} por {$met}";
    }
}