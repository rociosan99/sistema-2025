<?php

namespace Tests\Feature\Admin;

use App\Filament\Resources\PlanEstudios\Pages\ListPlanEstudios;
use App\Models\Carrera;
use App\Models\Institucion;
use App\Models\PlanEstudio;
use App\Models\User;
use Filament\Facades\Filament;
use Filament\Forms\Components\Select;
use Filament\Tables\Enums\FiltersLayout;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class PlanEstudioResourceFiltersTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $administrador = User::factory()->create([
            'role' => 'admin',
            'activo' => true,
        ]);

        $this->actingAs($administrador);
        Filament::setCurrentPanel(Filament::getPanel('admin'));
    }

    public function test_filtra_por_institucion(): void
    {
        $primero = $this->crearEscenario('Instituto Norte', 'Sistemas', 2020);
        $segundo = $this->crearEscenario('Instituto Sur', 'Electronica', 2020);

        Livewire::test(ListPlanEstudios::class)
            ->filterTable('plan', ['institucion_id' => $primero['institucion']->institucion_id])
            ->assertCanSeeTableRecords([$primero['plan']])
            ->assertCanNotSeeTableRecords([$segundo['plan']]);
    }

    public function test_filtra_por_carrera_limitada_a_su_institucion(): void
    {
        $institucion = $this->crearInstitucion('Instituto Comun');
        $primero = $this->crearEscenarioEnInstitucion($institucion, 'Sistemas', 2020);
        $segundo = $this->crearEscenarioEnInstitucion($institucion, 'Electronica', 2020);

        Livewire::test(ListPlanEstudios::class)
            ->filterTable('plan', [
                'institucion_id' => $institucion->institucion_id,
                'carrera_id' => $primero['carrera']->carrera_id,
            ])
            ->assertCanSeeTableRecords([$primero['plan']])
            ->assertCanNotSeeTableRecords([$segundo['plan']]);
    }

    public function test_institucion_es_searchable_mediante_consulta_dinamica(): void
    {
        $buscada = $this->crearInstitucion('Instituto Tecnologico Buscado');
        $this->crearInstitucion('Universidad Diferente');
        $select = $this->obtenerSelect('institucion_id');

        $this->assertTrue($select->isSearchable());
        $this->assertFalse($select->isPreloaded());
        $this->assertSame(
            [$buscada->institucion_id => $buscada->institucion_nombre],
            $select->getSearchResults('Tecnologico Buscado'),
        );
    }

    public function test_carrera_es_searchable_y_respeta_la_institucion_seleccionada(): void
    {
        $primeraInstitucion = $this->crearInstitucion('Instituto Norte');
        $segundaInstitucion = $this->crearInstitucion('Instituto Sur');
        $buscada = $this->crearCarrera($primeraInstitucion, 'Ingenieria Buscada');
        $this->crearCarrera($segundaInstitucion, 'Ingenieria Buscada Externa');

        $component = Livewire::test(ListPlanEstudios::class)
            ->set('tableDeferredFilters.plan.institucion_id', $primeraInstitucion->institucion_id);
        $select = $this->obtenerSelect('carrera_id', $component);

        $this->assertTrue($select->isSearchable());
        $this->assertFalse($select->isPreloaded());
        $this->assertSame(
            [$buscada->carrera_id => $buscada->carrera_nombre],
            $select->getSearchResults('Ingenieria Buscada'),
        );
    }

    public function test_sin_institucion_permite_buscar_entre_todas_las_carreras(): void
    {
        $primera = $this->crearCarrera($this->crearInstitucion('Instituto Norte'), 'Carrera Norte Buscada');
        $segunda = $this->crearCarrera($this->crearInstitucion('Instituto Sur'), 'Carrera Sur Buscada');
        $select = $this->obtenerSelect('carrera_id');

        $this->assertSame([
            $primera->carrera_id => $primera->carrera_nombre,
            $segunda->carrera_id => $segunda->carrera_nombre,
        ], $select->getSearchResults('Buscada'));
    }

    public function test_cambiar_institucion_limpia_una_carrera_incompatible(): void
    {
        $primero = $this->crearEscenario('Instituto Norte', 'Sistemas', 2020);
        $segundo = $this->crearEscenario('Instituto Sur', 'Electronica', 2020);

        Livewire::test(ListPlanEstudios::class)
            ->set('tableDeferredFilters.plan.institucion_id', $primero['institucion']->institucion_id)
            ->set('tableDeferredFilters.plan.carrera_id', $primero['carrera']->carrera_id)
            ->set('tableDeferredFilters.plan.institucion_id', $segundo['institucion']->institucion_id)
            ->assertSet('tableDeferredFilters.plan.carrera_id', null);
    }

    public function test_filtra_por_anio(): void
    {
        $primero = $this->crearEscenario('Instituto Norte', 'Sistemas', 2020);
        $segundo = $this->crearEscenario('Instituto Sur', 'Electronica', 2024);

        Livewire::test(ListPlanEstudios::class)
            ->filterTable('plan', ['anio' => 2020])
            ->assertCanSeeTableRecords([$primero['plan']])
            ->assertCanNotSeeTableRecords([$segundo['plan']]);
    }

    public function test_combina_institucion_carrera_anio(): void
    {
        $institucion = $this->crearInstitucion('Instituto Objetivo');
        $coincidente = $this->crearEscenarioEnInstitucion($institucion, 'Sistemas', 2020, 'Plan objetivo buscado');
        $otroAnio = $this->crearEscenarioEnInstitucion($institucion, 'Sistemas', 2024, 'Plan objetivo buscado');
        $otraCarrera = $this->crearEscenarioEnInstitucion($institucion, 'Electronica', 2020, 'Plan objetivo buscado');
        $otraInstitucion = $this->crearEscenario('Instituto Alternativo', 'Sistemas', 2020, 'Plan objetivo buscado');

        Livewire::test(ListPlanEstudios::class)
            ->filterTable('plan', [
                'institucion_id' => $institucion->institucion_id,
                'carrera_id' => $coincidente['carrera']->carrera_id,
                'anio' => 2020,
            ])
            ->assertCanSeeTableRecords([$coincidente['plan']])
            ->assertCanNotSeeTableRecords([$otroAnio['plan'], $otraCarrera['plan'], $otraInstitucion['plan']]);
    }

    public function test_reset_limpia_institucion_carrera_y_anio(): void
    {
        $primero = $this->crearEscenario('Instituto Norte', 'Sistemas', 2020, 'Plan buscado');
        $segundo = $this->crearEscenario('Instituto Sur', 'Electronica', 2024, 'Plan diferente');

        Livewire::test(ListPlanEstudios::class)
            ->filterTable('plan', [
                'institucion_id' => $primero['institucion']->institucion_id,
                'carrera_id' => $primero['carrera']->carrera_id,
                'anio' => 2020,
            ])
            ->assertCanNotSeeTableRecords([$segundo['plan']])
            ->call('resetTableFiltersForm')
            ->assertSet('tableFilters.plan.institucion_id', null)
            ->assertSet('tableFilters.plan.carrera_id', null)
            ->assertSet('tableFilters.plan.anio', null)
            ->assertSet('tableDeferredFilters.plan.institucion_id', null)
            ->assertSet('tableDeferredFilters.plan.carrera_id', null)
            ->assertSet('tableDeferredFilters.plan.anio', null)
            ->assertCanSeeTableRecords([$primero['plan'], $segundo['plan']]);
    }

    public function test_muestra_los_filtros_sobre_el_contenido(): void
    {
        $pagina = Livewire::test(ListPlanEstudios::class)
            ->assertSee('Filtros')
            ->assertSee('Resetear los filtros')
            ->assertSee('Aplicar filtros')
            ->assertDontSee('Buscar por carrera o descripción')
            ->assertDontSee('fi-ta-search-field', false)
            ->assertDontSee("content: 'Acción'", false);
        $tabla = $pagina->instance()->getTable();

        $this->assertSame(FiltersLayout::AboveContent, $tabla->getFiltersLayout());
        $this->assertSame(3, $tabla->getFiltersFormColumns());
        $this->assertFalse($tabla->isSearchable());
    }

    public function test_anio_utiliza_unicamente_valores_reales_sin_duplicados(): void
    {
        $this->crearEscenario('Instituto Norte', 'Sistemas', 2020);
        $this->crearEscenario('Instituto Sur', 'Electronica', 2024);
        $this->crearEscenario('Instituto Este', 'Informatica', 2020);

        $this->assertSame([2024 => 2024, 2020 => 2020], $this->obtenerSelect('anio')->getOptions());
    }

    public function test_la_busqueda_general_ya_no_filtra_registros(): void
    {
        $primero = $this->crearEscenario('Instituto Norte', 'Sistemas', 2020, 'Descripcion objetivo');
        $segundo = $this->crearEscenario('Instituto Sur', 'Electronica', 2024, 'Otra descripcion');

        // Incluso una búsqueda anterior conservada en la URL deja de filtrar columnas.
        Livewire::test(ListPlanEstudios::class)
            ->searchTable('objetivo')
            ->assertCanSeeTableRecords([$primero['plan'], $segundo['plan']]);
    }

    private function obtenerSelect(string $nombre, $component = null): Select
    {
        $component ??= Livewire::test(ListPlanEstudios::class);
        $select = $component
            ->instance()
            ->getTable()
            ->getFilter('plan')
            ->getSchema()
            ->getComponent($nombre);

        $this->assertInstanceOf(Select::class, $select);

        return $select;
    }

    private function crearEscenario(
        string $institucion,
        string $carrera,
        int $anio,
        ?string $descripcion = null,
    ): array {
        return $this->crearEscenarioEnInstitucion(
            $this->crearInstitucion($institucion),
            $carrera,
            $anio,
            $descripcion,
        );
    }

    private function crearEscenarioEnInstitucion(
        Institucion $institucion,
        string $carrera,
        int $anio,
        ?string $descripcion = null,
    ): array {
        $carreraCreada = $this->crearCarrera($institucion, $carrera);
        $plan = PlanEstudio::query()->create([
            'plan_carrera_id' => $carreraCreada->carrera_id,
            'plan_anio' => $anio,
            'plan_descripcion' => $descripcion,
        ]);

        return [
            'institucion' => $institucion,
            'carrera' => $carreraCreada,
            'plan' => $plan,
        ];
    }

    private function crearInstitucion(string $nombre): Institucion
    {
        return Institucion::query()->create([
            'institucion_nombre' => $nombre,
            'institucion_descripcion' => null,
        ]);
    }

    private function crearCarrera(Institucion $institucion, string $nombre): Carrera
    {
        return Carrera::query()->create([
            'carrera_institucion_id' => $institucion->institucion_id,
            'carrera_nombre' => $nombre,
            'carrera_descripcion' => null,
        ]);
    }
}
