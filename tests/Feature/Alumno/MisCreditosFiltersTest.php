<?php

namespace Tests\Feature\Alumno;

use App\Filament\Alumno\Pages\MisCreditos;
use App\Models\Credito;
use App\Models\Materia;
use App\Models\Turno;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\Support\CreatesTurnoScenarios;
use Tests\TestCase;

class MisCreditosFiltersTest extends TestCase
{
    use CreatesTurnoScenarios;
    use RefreshDatabase;

    private User $alumno;

    private User $profesor;

    private Materia $materia;

    protected function setUp(): void
    {
        parent::setUp();

        $this->travelTo('2026-09-25 12:00:00');
        $this->alumno = $this->crearAlumno();
        $this->profesor = $this->crearProfesor();
        $this->materia = $this->crearMateria();
        $this->actingAs($this->alumno);
        Filament::setCurrentPanel(Filament::getPanel('alumno'));
    }

    public function test_sin_filtros_mantiene_el_historial_y_solo_muestra_creditos_propios(): void
    {
        $propioUno = $this->crearCredito(['cancelado_at' => '2026-09-20 10:00:00']);
        $propioDos = $this->crearCredito(['cancelado_at' => '2026-09-21 10:00:00']);
        $otroAlumno = $this->crearAlumno();
        $ajeno = $this->crearCredito(['cancelado_at' => '2026-09-22 10:00:00'], $otroAlumno);

        $ids = $this->ids(Livewire::test(MisCreditos::class));

        $this->assertEqualsCanonicalizing([$propioUno->id, $propioDos->id], $ids);
        $this->assertNotContains($ajeno->id, $ids);
    }

    public function test_filtra_por_cada_estado_real(): void
    {
        $esperando = $this->crearCredito(['estado' => Credito::ESTADO_ESPERANDO_PAGO]);
        $disponible = $this->crearCredito(['estado' => Credito::ESTADO_DISPONIBLE]);
        $noAplica = $this->crearCredito(['estado' => Credito::ESTADO_NO_APLICA]);

        foreach ([
            Credito::ESTADO_ESPERANDO_PAGO => $esperando->id,
            Credito::ESTADO_DISPONIBLE => $disponible->id,
            Credito::ESTADO_NO_APLICA => $noAplica->id,
        ] as $estado => $idEsperado) {
            $component = Livewire::test(MisCreditos::class)
                ->set('estado', $estado)
                ->call('aplicarFiltros');

            $this->assertSame([$idEsperado], $this->ids($component));
        }
    }

    public function test_filtra_desde_inclusive(): void
    {
        $anterior = $this->crearCredito(['cancelado_at' => '2026-09-09 23:59:00']);
        $limite = $this->crearCredito(['cancelado_at' => '2026-09-10 00:00:00']);

        $component = Livewire::test(MisCreditos::class)
            ->set('desde', '2026-09-10')
            ->call('aplicarFiltros');

        $this->assertContains($limite->id, $this->ids($component));
        $this->assertNotContains($anterior->id, $this->ids($component));
    }

    public function test_filtra_hasta_inclusive(): void
    {
        $limite = $this->crearCredito(['cancelado_at' => '2026-09-10 23:59:00']);
        $posterior = $this->crearCredito(['cancelado_at' => '2026-09-11 00:00:00']);

        $component = Livewire::test(MisCreditos::class)
            ->set('hasta', '2026-09-10')
            ->call('aplicarFiltros');

        $this->assertContains($limite->id, $this->ids($component));
        $this->assertNotContains($posterior->id, $this->ids($component));
    }

    public function test_filtra_por_rango_inclusive(): void
    {
        $antes = $this->crearCredito(['cancelado_at' => '2026-09-09 12:00:00']);
        $inicio = $this->crearCredito(['cancelado_at' => '2026-09-10 12:00:00']);
        $fin = $this->crearCredito(['cancelado_at' => '2026-09-20 12:00:00']);
        $despues = $this->crearCredito(['cancelado_at' => '2026-09-21 12:00:00']);

        $component = Livewire::test(MisCreditos::class)
            ->set('desde', '2026-09-10')
            ->set('hasta', '2026-09-20')
            ->call('aplicarFiltros');

        $this->assertEqualsCanonicalizing([$inicio->id, $fin->id], $this->ids($component));
        $this->assertNotContains($antes->id, $this->ids($component));
        $this->assertNotContains($despues->id, $this->ids($component));
    }

    public function test_busca_por_id_del_turno_de_origen(): void
    {
        $buscado = $this->crearCredito();
        $distinto = $this->crearCredito();

        $component = Livewire::test(MisCreditos::class)
            ->set('buscar', (string) $buscado->turno_id)
            ->call('aplicarFiltros');

        $this->assertSame([$buscado->id], $this->ids($component));
        $this->assertNotContains($distinto->id, $this->ids($component));
    }

    public function test_buscar_turno_inexistente_devuelve_listado_vacio(): void
    {
        $this->crearCredito();

        $component = Livewire::test(MisCreditos::class)
            ->set('buscar', '999999')
            ->call('aplicarFiltros');

        $this->assertSame([], $this->ids($component));
    }

    public function test_combina_estado_y_fechas(): void
    {
        $coincidente = $this->crearCredito([
            'estado' => Credito::ESTADO_DISPONIBLE,
            'cancelado_at' => '2026-09-15 12:00:00',
        ]);
        $estadoDistinto = $this->crearCredito([
            'estado' => Credito::ESTADO_NO_APLICA,
            'cancelado_at' => '2026-09-15 12:00:00',
        ]);
        $fueraDeRango = $this->crearCredito([
            'estado' => Credito::ESTADO_DISPONIBLE,
            'cancelado_at' => '2026-09-05 12:00:00',
        ]);

        $component = Livewire::test(MisCreditos::class)
            ->set('estado', Credito::ESTADO_DISPONIBLE)
            ->set('desde', '2026-09-10')
            ->set('hasta', '2026-09-20')
            ->call('aplicarFiltros');

        $this->assertSame([$coincidente->id], $this->ids($component));
        $this->assertNotContains($estadoDistinto->id, $this->ids($component));
        $this->assertNotContains($fueraDeRango->id, $this->ids($component));
    }

    public function test_combina_estado_y_busqueda_por_turno(): void
    {
        $coincidente = $this->crearCredito(['estado' => Credito::ESTADO_DISPONIBLE]);
        $this->crearCredito(['estado' => Credito::ESTADO_NO_APLICA]);

        $component = Livewire::test(MisCreditos::class)
            ->set('estado', Credito::ESTADO_DISPONIBLE)
            ->set('buscar', (string) $coincidente->turno_id)
            ->call('aplicarFiltros');

        $this->assertSame([$coincidente->id], $this->ids($component));
    }

    public function test_mantiene_orden_descendente_por_cancelacion_e_id(): void
    {
        $antiguo = $this->crearCredito(['cancelado_at' => '2026-09-10 12:00:00']);
        $recienteUno = $this->crearCredito(['cancelado_at' => '2026-09-20 12:00:00']);
        $recienteDos = $this->crearCredito(['cancelado_at' => '2026-09-20 12:00:00']);

        $this->assertSame(
            [$recienteDos->id, $recienteUno->id, $antiguo->id],
            $this->ids(Livewire::test(MisCreditos::class)),
        );
    }

    public function test_pagina_diez_registros_por_defecto_y_permite_10_25_50(): void
    {
        foreach (range(1, 30) as $indice) {
            $this->crearCredito(['cancelado_at' => now()->subMinutes($indice)]);
        }

        $component = Livewire::test(MisCreditos::class);
        $this->assertSame(10, $component->instance()->historialPaginado()->perPage());
        $this->assertCount(10, $component->instance()->historialPaginado()->items());

        foreach ([10, 25, 50] as $cantidad) {
            $component->set('porPagina', $cantidad);
            $this->assertSame($cantidad, $component->instance()->historialPaginado()->perPage());
            $this->assertCount(min($cantidad, 30), $component->instance()->historialPaginado()->items());
        }
    }

    public function test_modificar_un_filtro_vuelve_a_pagina_uno(): void
    {
        foreach (range(1, 15) as $indice) {
            $this->crearCredito(['cancelado_at' => now()->subMinutes($indice)]);
        }

        Livewire::test(MisCreditos::class)
            ->call('setPage', 2, 'creditosPage')
            ->assertSet('paginators.creditosPage', 2)
            ->set('estado', Credito::ESTADO_DISPONIBLE)
            ->assertSet('paginators.creditosPage', 1);
    }

    public function test_reset_limpia_filtros_busqueda_y_vuelve_a_pagina_uno(): void
    {
        foreach (range(1, 15) as $indice) {
            $this->crearCredito(['cancelado_at' => now()->subMinutes($indice)]);
        }

        Livewire::test(MisCreditos::class)
            ->set('estado', Credito::ESTADO_DISPONIBLE)
            ->set('desde', '2026-09-01')
            ->set('hasta', '2026-09-30')
            ->set('buscar', '123')
            ->call('setPage', 2, 'creditosPage')
            ->call('resetearFiltros')
            ->assertSet('estado', null)
            ->assertSet('desde', null)
            ->assertSet('hasta', null)
            ->assertSet('buscar', '')
            ->assertSet('paginators.creditosPage', 1);
    }

    public function test_aplicar_filtros_no_modifica_datos_economicos(): void
    {
        $credito = $this->crearCredito([
            'importe_pagado' => '200.00',
            'importe_credito' => '150.00',
            'importe_penalizacion' => '50.00',
            'saldo_disponible' => '125.00',
            'estado' => Credito::ESTADO_DISPONIBLE,
            'vence_at' => '2026-12-31 23:59:59',
            'porcentaje_credito_aplicado' => '75.00',
            'porcentaje_penalizacion_aplicado' => '25.00',
        ]);
        $antes = $credito->only([
            'importe_pagado', 'importe_credito', 'importe_penalizacion', 'saldo_disponible',
            'estado', 'vence_at', 'porcentaje_credito_aplicado', 'porcentaje_penalizacion_aplicado',
        ]);
        $antes['vence_at'] = $credito->vence_at?->toDateTimeString();

        Livewire::test(MisCreditos::class)
            ->set('estado', Credito::ESTADO_DISPONIBLE)
            ->set('buscar', (string) $credito->turno_id)
            ->call('aplicarFiltros');

        $despues = $credito->fresh()->only(array_keys($antes));
        $despues['vence_at'] = $credito->fresh()->vence_at?->toDateTimeString();

        $this->assertSame($antes, $despues);
    }

    private function ids($component): array
    {
        return collect($component->instance()->historialPaginado()->items())
            ->pluck('id')
            ->values()
            ->all();
    }

    private function crearCredito(array $attributes = [], ?User $alumno = null): Credito
    {
        $alumno ??= $this->alumno;
        $turno = $this->crearTurno($alumno, $this->profesor, $this->materia, [
            'fecha' => '2026-09-20',
            'estado' => Turno::ESTADO_CANCELADO,
        ]);

        return Credito::query()->create(array_merge([
            'alumno_id' => $alumno->id,
            'turno_id' => $turno->id,
            'pago_id' => null,
            'importe_pagado' => '100.00',
            'importe_credito' => '100.00',
            'importe_penalizacion' => '0.00',
            'importe_penalizacion_profesor' => '0.00',
            'importe_penalizacion_plataforma' => '0.00',
            'saldo_disponible' => '100.00',
            'porcentaje_credito_aplicado' => '100.00',
            'porcentaje_penalizacion_aplicado' => '0.00',
            'porcentaje_profesor_penalizacion_aplicado' => '80.00',
            'porcentaje_plataforma_penalizacion_aplicado' => '20.00',
            'horas_limite_aplicadas' => 24,
            'vigencia_dias_aplicada' => 90,
            'estado' => Credito::ESTADO_DISPONIBLE,
            'idempotency_key' => "mis-creditos-test:{$turno->id}",
            'cancelado_at' => '2026-09-20 12:00:00',
            'vence_at' => '2026-12-20 12:00:00',
        ], $attributes));
    }
}
