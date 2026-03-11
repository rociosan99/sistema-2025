<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class OptimizacionPrecios extends Page
{
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-currency-dollar';
    protected static ?string $navigationLabel = 'Optimización de precios';
    protected static ?string $title = 'Optimización de precios';
    protected static ?string $slug = 'optimizacion-precios';
    protected static string|\UnitEnum|null $navigationGroup = 'General';

    // Para que NO aparezca en el menú y se abra desde el botón de Estadísticas
    protected static bool $shouldRegisterNavigation = false;

    protected string $view = 'filament.pages.optimizacion-precios';

    public ?string $fechaInicio = null;
    public ?string $fechaFin = null;

    /** @var array<int, array<string, mixed>> */
    public array $registros = [];

    public array $chartLabels = [];
    public array $chartPrecioActual = [];
    public array $chartPrecioSugerido = [];

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

        // Subconsulta: clases confirmadas por profesor + materia
        $turnosConfirmados = DB::table('turnos as t')
            ->selectRaw('
                t.profesor_id,
                t.materia_id,
                COUNT(*) as clases_confirmadas
            ')
            ->where('t.estado', 'confirmado')
            ->whereBetween('t.fecha', [$desde, $hasta])
            ->groupBy('t.profesor_id', 't.materia_id');

        // Subconsulta: promedio de estrellas por profesor
        // OJO: se asume tabla calificaciones_profesor por el modelo que me pasaste.
        $ratingsProfesor = DB::table('calificaciones_profesor as cp')
            ->selectRaw('
                cp.profesor_id,
                AVG(cp.estrellas) as promedio_estrellas,
                COUNT(*) as total_resenas
            ')
            ->groupBy('cp.profesor_id');

        $rows = DB::table('profesor_materia as pm')
            ->join('users as u', 'u.id', '=', 'pm.profesor_id')
            ->join('materias as m', 'm.materia_id', '=', 'pm.materia_id')
            ->leftJoinSub($turnosConfirmados, 'tc', function ($join) {
                $join->on('tc.profesor_id', '=', 'pm.profesor_id')
                    ->on('tc.materia_id', '=', 'pm.materia_id');
            })
            ->leftJoinSub($ratingsProfesor, 'rp', function ($join) {
                $join->on('rp.profesor_id', '=', 'pm.profesor_id');
            })
            ->where('u.role', 'profesor')
            ->selectRaw("
                pm.profesor_id,
                pm.materia_id,
                CONCAT(u.name, ' ', COALESCE(u.apellido, '')) as profesor,
                u.email,
                m.materia_nombre as materia,
                pm.precio_por_hora as precio_actual,
                COALESCE(tc.clases_confirmadas, 0) as clases_confirmadas,
                COALESCE(rp.promedio_estrellas, 0) as promedio_estrellas,
                COALESCE(rp.total_resenas, 0) as total_resenas
            ")
            ->orderByDesc('clases_confirmadas')
            ->orderByDesc('promedio_estrellas')
            ->get();

        $procesados = $rows->map(function ($row) {
            $precioActual = (float) $row->precio_actual;
            $clases = (int) $row->clases_confirmadas;
            $rating = round((float) $row->promedio_estrellas, 2);

            $precioSugerido = $precioActual;
            $recomendacion = 'Mantener';
            $ajusteTexto = '0%';

            // Reglas simples y defendibles
            if ($rating >= 4.5 && $clases >= 10) {
                $precioSugerido = $precioActual * 1.15;
                $recomendacion = 'Subir';
                $ajusteTexto = '+15%';
            } elseif ($rating >= 4.0 && $clases >= 5) {
                $precioSugerido = $precioActual * 1.10;
                $recomendacion = 'Subir';
                $ajusteTexto = '+10%';
            } elseif ($rating < 3.5 && $clases > 0 && $clases < 5) {
                $precioSugerido = $precioActual * 0.90;
                $recomendacion = 'Bajar';
                $ajusteTexto = '-10%';
            } else {
                $precioSugerido = $precioActual;
                $recomendacion = 'Mantener';
                $ajusteTexto = '0%';
            }

            // Límite de seguridad: entre 70% y 150% del precio actual
            $precioSugerido = max($precioActual * 0.70, min($precioSugerido, $precioActual * 1.50));

            return [
                'profesor' => trim((string) $row->profesor),
                'email' => (string) $row->email,
                'materia' => (string) $row->materia,
                'precio_actual' => round($precioActual, 2),
                'clases_confirmadas' => $clases,
                'promedio_estrellas' => $rating,
                'total_resenas' => (int) $row->total_resenas,
                'precio_sugerido' => round($precioSugerido, 2),
                'recomendacion' => $recomendacion,
                'ajuste_texto' => $ajusteTexto,
            ];
        });

        $this->registros = $procesados->all();

        $top = $procesados->take(10);

        $this->chartLabels = $top
            ->map(fn ($r) => $r['profesor'] . ' - ' . $r['materia'])
            ->values()
            ->all();

        $this->chartPrecioActual = $top
            ->pluck('precio_actual')
            ->map(fn ($v) => (float) $v)
            ->values()
            ->all();

        $this->chartPrecioSugerido = $top
            ->pluck('precio_sugerido')
            ->map(fn ($v) => (float) $v)
            ->values()
            ->all();

        $this->dispatch('optimizacion-precios-actualizada');
    }
}