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

    public ?string $fechaInicio = null;
    public ?string $fechaFin = null;

    // Debug (se mantiene por si querés usarlo, pero NO se muestra en la vista)
    public int $debugTotalTurnosEnRango = 0;
    public int $debugMateriasDistintas = 0;
    public int $debugTemasDistintos = 0;
    public int $debugTurnosConTemaEnRango = 0;

    /** @var array<int, array{tema:string, solicitados:int}> */
    public array $debugTopTemas = [];

    /** @var array<int, array<string, mixed>> */
    public array $materias = [];
    public array $materiasChartLabels = [];
    public array $materiasChartSolicitados = [];

    /** @var array<int, array<string, mixed>> */
    public array $temas = [];
    public array $temasChartLabels = [];
    public array $temasChartSolicitados = [];

    /** @var array<int, array<string, mixed>> */
    public array $estados = [];
    public array $estadosChartLabels = [];
    public array $estadosChartTotales = [];

    /** @var array<int, array<string, mixed>> */
    public array $profesores = [];
    public array $profesoresChartLabels = [];
    public array $profesoresChartConfirmados = [];

    public function mount(): void
    {
        $this->fechaFin = now()->toDateString();
        $this->fechaInicio = now()->subDays(30)->toDateString();

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

    private function cargarDatos(): void
    {
        $desde = $this->fechaInicio;
        $hasta = $this->fechaFin;

        // Debug base (no visible en UI)
        $this->debugTotalTurnosEnRango = (int) DB::table('turnos')
            ->whereBetween('fecha', [$desde, $hasta])
            ->count();

        $this->debugTurnosConTemaEnRango = (int) DB::table('turnos')
            ->whereBetween('fecha', [$desde, $hasta])
            ->whereNotNull('tema_id')
            ->count();

        // 1) Materias
        $materias = DB::table('turnos as t')
            ->join('materias as m', 'm.materia_id', '=', 't.materia_id')
            ->selectRaw("
                m.materia_id,
                m.materia_nombre as materia,
                COUNT(*) as solicitados,
                SUM(CASE WHEN t.estado = 'confirmado' THEN 1 ELSE 0 END) as pagados,
                SUM(CASE WHEN t.estado = 'cancelado' THEN 1 ELSE 0 END) as cancelados,
                SUM(CASE WHEN t.estado = 'vencido' THEN 1 ELSE 0 END) as vencidos
            ")
            ->whereBetween('t.fecha', [$desde, $hasta])
            ->groupBy('m.materia_id', 'm.materia_nombre')
            ->orderByDesc('solicitados')
            ->get();

        $this->debugMateriasDistintas = $materias->count();
        $this->materias = $materias->map(fn ($r) => (array) $r)->all();

        $topMaterias = $materias->take(10);
        $this->materiasChartLabels = $topMaterias->pluck('materia')->values()->all();
        $this->materiasChartSolicitados = $topMaterias->pluck('solicitados')->map(fn ($v) => (int) $v)->values()->all();

        // 2) Temas
        $temas = DB::table('turnos as t')
            ->join('materias as m', 'm.materia_id', '=', 't.materia_id')
            ->leftJoin('temas as te', 'te.tema_id', '=', 't.tema_id')
            ->selectRaw("
                COALESCE(te.tema_nombre, CONCAT('Temas de ', m.materia_nombre)) as tema,
                COUNT(*) as solicitados,
                SUM(CASE WHEN t.estado = 'confirmado' THEN 1 ELSE 0 END) as pagados,
                SUM(CASE WHEN t.estado = 'cancelado' THEN 1 ELSE 0 END) as cancelados,
                SUM(CASE WHEN t.estado = 'vencido' THEN 1 ELSE 0 END) as vencidos
            ")
            ->whereBetween('t.fecha', [$desde, $hasta])
            ->groupBy(DB::raw("COALESCE(te.tema_nombre, CONCAT('Temas de ', m.materia_nombre))"))
            ->orderByDesc('solicitados')
            ->get();

        $this->debugTemasDistintos = $temas->count();
        $this->temas = $temas->map(fn ($r) => (array) $r)->all();

        $topTemas = $temas->take(10);
        $this->temasChartLabels = $topTemas->pluck('tema')->values()->all();
        $this->temasChartSolicitados = $topTemas->pluck('solicitados')->map(fn ($v) => (int) $v)->values()->all();

        $this->debugTopTemas = $temas
            ->take(5)
            ->map(fn ($r) => ['tema' => (string) $r->tema, 'solicitados' => (int) $r->solicitados])
            ->values()
            ->all();

        // 3) Turnos por estado
        $estados = DB::table('turnos')
            ->selectRaw("
                estado,
                COUNT(*) as total
            ")
            ->whereBetween('fecha', [$desde, $hasta])
            ->groupBy('estado')
            ->orderByDesc('total')
            ->get();

        $this->estados = $estados->map(fn ($r) => [
            'estado' => (string) $r->estado,
            'total' => (int) $r->total,
        ])->all();

        $this->estadosChartLabels = $estados
            ->pluck('estado')
            ->map(fn ($v) => (string) $v)
            ->values()
            ->all();

        $this->estadosChartTotales = $estados
            ->pluck('total')
            ->map(fn ($v) => (int) $v)
            ->values()
            ->all();

        // 4) Profesores con más clases confirmadas
        $profesores = DB::table('turnos as t')
            ->join('users as u', 'u.id', '=', 't.profesor_id')
            ->selectRaw("
                u.id,
                CONCAT(u.name, ' ', COALESCE(u.apellido, '')) as profesor,
                u.email,
                COUNT(*) as confirmados
            ")
            ->where('u.role', 'profesor')
            ->where('t.estado', 'confirmado')
            ->whereBetween('t.fecha', [$desde, $hasta])
            ->groupBy('u.id', 'u.name', 'u.apellido', 'u.email')
            ->orderByDesc('confirmados')
            ->get();

        $this->profesores = $profesores->map(fn ($r) => [
            'profesor' => trim((string) $r->profesor),
            'email' => (string) $r->email,
            'confirmados' => (int) $r->confirmados,
        ])->all();

        $topProfesores = $profesores->take(10);

        $this->profesoresChartLabels = $topProfesores
            ->map(fn ($r) => trim((string) $r->profesor))
            ->values()
            ->all();

        $this->profesoresChartConfirmados = $topProfesores
            ->pluck('confirmados')
            ->map(fn ($v) => (int) $v)
            ->values()
            ->all();

        $this->dispatch('estadisticas-actualizadas');
    }
}