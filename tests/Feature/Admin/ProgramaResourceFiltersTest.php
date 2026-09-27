<?php

namespace Tests\Feature\Admin;

use App\Filament\Resources\Programas\Pages\ListProgramas;
use App\Models\Carrera;
use App\Models\Institucion;
use App\Models\Materia;
use App\Models\PlanEstudio;
use App\Models\Programa;
use App\Models\Tema;
use App\Models\User;
use Filament\Facades\Filament;
use Filament\Tables\Enums\FiltersLayout;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ProgramaResourceFiltersTest extends TestCase
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
        $primera = $this->crearEscenario('Universidad Norte', 'Ingenieria', 2020, 'Algebra', 2025);
        $segunda = $this->crearEscenario('Universidad Sur', 'Licenciatura', 2021, 'Fisica', 2025);

        Livewire::test(ListProgramas::class)
            ->filterTable('programa', ['institucion_id' => $primera['institucion']->institucion_id])
            ->assertCanSeeTableRecords([$primera['programa']])
            ->assertCanNotSeeTableRecords([$segunda['programa']]);
    }

    public function test_filtra_por_carrera(): void
    {
        $institucion = $this->crearInstitucion('Universidad Comun');
        $primera = $this->crearEscenarioEnInstitucion($institucion, 'Sistemas', 2020, 'Algebra', 2025);
        $segunda = $this->crearEscenarioEnInstitucion($institucion, 'Electronica', 2020, 'Fisica', 2025);

        Livewire::test(ListProgramas::class)
            ->filterTable('programa', ['carrera_id' => $primera['carrera']->carrera_id])
            ->assertCanSeeTableRecords([$primera['programa']])
            ->assertCanNotSeeTableRecords([$segunda['programa']]);
    }

    public function test_filtra_por_plan(): void
    {
        $institucion = $this->crearInstitucion('Universidad Comun');
        $carrera = $this->crearCarrera($institucion, 'Sistemas');
        $primera = $this->crearEscenarioEnCarrera($carrera, 2020, 'Algebra', 2025);
        $segunda = $this->crearEscenarioEnCarrera($carrera, 2024, 'Fisica', 2025);

        Livewire::test(ListProgramas::class)
            ->filterTable('programa', ['plan_id' => $primera['plan']->plan_id])
            ->assertCanSeeTableRecords([$primera['programa']])
            ->assertCanNotSeeTableRecords([$segunda['programa']]);
    }

    public function test_filtra_por_anio_real_del_programa(): void
    {
        $primero = $this->crearEscenario('Universidad Norte', 'Sistemas', 2020, 'Algebra', 2024);
        $segundo = $this->crearEscenario('Universidad Sur', 'Electronica', 2020, 'Fisica', 2025);

        Livewire::test(ListProgramas::class)
            ->filterTable('programa', ['anio' => 2024])
            ->assertCanSeeTableRecords([$primero['programa']])
            ->assertCanNotSeeTableRecords([$segundo['programa']]);
    }

    public function test_combina_institucion_y_carrera(): void
    {
        $institucion = $this->crearInstitucion('Universidad Objetivo');
        $coincidente = $this->crearEscenarioEnInstitucion($institucion, 'Sistemas', 2020, 'Algebra', 2025);
        $otraCarrera = $this->crearEscenarioEnInstitucion($institucion, 'Electronica', 2020, 'Fisica', 2025);
        $otraInstitucion = $this->crearEscenario('Universidad Alternativa', 'Sistemas', 2020, 'Quimica', 2025);

        Livewire::test(ListProgramas::class)
            ->filterTable('programa', [
                'institucion_id' => $institucion->institucion_id,
                'carrera_id' => $coincidente['carrera']->carrera_id,
            ])
            ->assertCanSeeTableRecords([$coincidente['programa']])
            ->assertCanNotSeeTableRecords([$otraCarrera['programa'], $otraInstitucion['programa']]);
    }

    public function test_combina_institucion_carrera_y_plan(): void
    {
        $institucion = $this->crearInstitucion('Universidad Objetivo');
        $carrera = $this->crearCarrera($institucion, 'Sistemas');
        $coincidente = $this->crearEscenarioEnCarrera($carrera, 2020, 'Algebra', 2025);
        $otroPlan = $this->crearEscenarioEnCarrera($carrera, 2024, 'Fisica', 2025);

        Livewire::test(ListProgramas::class)
            ->filterTable('programa', [
                'institucion_id' => $institucion->institucion_id,
                'carrera_id' => $carrera->carrera_id,
                'plan_id' => $coincidente['plan']->plan_id,
            ])
            ->assertCanSeeTableRecords([$coincidente['programa']])
            ->assertCanNotSeeTableRecords([$otroPlan['programa']]);
    }

    public function test_cambiar_institucion_limpia_carrera_y_plan_incompatibles(): void
    {
        $primera = $this->crearEscenario('Universidad Norte', 'Sistemas', 2020, 'Algebra', 2025);
        $segunda = $this->crearEscenario('Universidad Sur', 'Electronica', 2024, 'Fisica', 2025);

        Livewire::test(ListProgramas::class)
            ->set('tableDeferredFilters.programa.institucion_id', $primera['institucion']->institucion_id)
            ->set('tableDeferredFilters.programa.carrera_id', $primera['carrera']->carrera_id)
            ->set('tableDeferredFilters.programa.plan_id', $primera['plan']->plan_id)
            ->set('tableDeferredFilters.programa.institucion_id', $segunda['institucion']->institucion_id)
            ->assertSet('tableDeferredFilters.programa.carrera_id', null)
            ->assertSet('tableDeferredFilters.programa.plan_id', null);
    }

    public function test_cambiar_carrera_limpia_un_plan_incompatible(): void
    {
        $institucion = $this->crearInstitucion('Universidad Comun');
        $primera = $this->crearEscenarioEnInstitucion($institucion, 'Sistemas', 2020, 'Algebra', 2025);
        $segunda = $this->crearEscenarioEnInstitucion($institucion, 'Electronica', 2024, 'Fisica', 2025);

        Livewire::test(ListProgramas::class)
            ->set('tableDeferredFilters.programa.institucion_id', $institucion->institucion_id)
            ->set('tableDeferredFilters.programa.carrera_id', $primera['carrera']->carrera_id)
            ->set('tableDeferredFilters.programa.plan_id', $primera['plan']->plan_id)
            ->set('tableDeferredFilters.programa.carrera_id', $segunda['carrera']->carrera_id)
            ->assertSet('tableDeferredFilters.programa.plan_id', null);
    }

    public function test_busca_por_materia(): void
    {
        $encontrado = $this->crearEscenario('Universidad Norte', 'Sistemas', 2020, 'Materia Buscada', 2025);
        $distinto = $this->crearEscenario('Universidad Sur', 'Electronica', 2020, 'Materia Distinta', 2025);

        Livewire::test(ListProgramas::class)
            ->searchTable('Materia Buscada')
            ->assertCanSeeTableRecords([$encontrado['programa']])
            ->assertCanNotSeeTableRecords([$distinto['programa']]);
    }

    public function test_busca_por_tema_sin_duplicar_programas(): void
    {
        $encontrado = $this->crearEscenario('Universidad Norte', 'Sistemas', 2020, 'Algebra', 2025);
        $distinto = $this->crearEscenario('Universidad Sur', 'Electronica', 2020, 'Fisica', 2025);
        $temaUno = $this->crearTema('Normalizacion Buscada');
        $temaDos = $this->crearTema('Normalizacion Buscada Avanzada');
        $encontrado['programa']->temas()->attach([$temaUno->tema_id, $temaDos->tema_id]);

        Livewire::test(ListProgramas::class)
            ->searchTable('Normalizacion Buscada')
            ->assertCanSeeTableRecords([$encontrado['programa']])
            ->assertCanNotSeeTableRecords([$distinto['programa']]);
    }

    public function test_combina_filtro_y_busqueda(): void
    {
        $institucion = $this->crearInstitucion('Universidad Objetivo');
        $coincidente = $this->crearEscenarioEnInstitucion($institucion, 'Sistemas', 2020, 'Materia Buscada', 2025);
        $mismaInstitucion = $this->crearEscenarioEnInstitucion($institucion, 'Electronica', 2020, 'Otra Materia', 2025);
        $otraInstitucion = $this->crearEscenario('Universidad Alternativa', 'Industrial', 2020, 'Materia Buscada', 2025);

        Livewire::test(ListProgramas::class)
            ->filterTable('programa', ['institucion_id' => $institucion->institucion_id])
            ->searchTable('Materia Buscada')
            ->assertCanSeeTableRecords([$coincidente['programa']])
            ->assertCanNotSeeTableRecords([$mismaInstitucion['programa'], $otraInstitucion['programa']]);
    }

    public function test_reset_limpia_filtros_y_busqueda(): void
    {
        $primero = $this->crearEscenario('Universidad Norte', 'Sistemas', 2020, 'Materia Buscada', 2025);
        $segundo = $this->crearEscenario('Universidad Sur', 'Electronica', 2020, 'Materia Distinta', 2024);

        Livewire::test(ListProgramas::class)
            ->filterTable('programa', ['institucion_id' => $primero['institucion']->institucion_id])
            ->searchTable('Materia Buscada')
            ->assertCanSeeTableRecords([$primero['programa']])
            ->assertCanNotSeeTableRecords([$segundo['programa']])
            ->call('resetTableFiltersForm')
            ->assertSet('tableSearch', '')
            ->assertCanSeeTableRecords([$primero['programa'], $segundo['programa']]);
    }

    public function test_muestra_los_filtros_sobre_el_contenido(): void
    {
        $tabla = Livewire::test(ListProgramas::class)->instance()->getTable();

        $this->assertSame(FiltersLayout::AboveContent, $tabla->getFiltersLayout());
        $this->assertSame(4, $tabla->getFiltersFormColumns());
    }

    private function crearEscenario(
        string $institucion,
        string $carrera,
        int $plan,
        string $materia,
        int $anioPrograma,
    ): array {
        return $this->crearEscenarioEnInstitucion(
            $this->crearInstitucion($institucion),
            $carrera,
            $plan,
            $materia,
            $anioPrograma,
        );
    }

    private function crearEscenarioEnInstitucion(
        Institucion $institucion,
        string $carrera,
        int $plan,
        string $materia,
        int $anioPrograma,
    ): array {
        return $this->crearEscenarioEnCarrera(
            $this->crearCarrera($institucion, $carrera),
            $plan,
            $materia,
            $anioPrograma,
        ) + ['institucion' => $institucion];
    }

    private function crearEscenarioEnCarrera(
        Carrera $carrera,
        int $plan,
        string $materia,
        int $anioPrograma,
    ): array {
        $planCreado = PlanEstudio::query()->create([
            'plan_carrera_id' => $carrera->carrera_id,
            'plan_anio' => $plan,
            'plan_descripcion' => null,
        ]);
        $materiaCreada = Materia::query()->create([
            'materia_nombre' => $materia,
            'materia_descripcion' => null,
            'materia_anio' => $anioPrograma,
        ]);
        $programa = Programa::query()->create([
            'programa_plan_id' => $planCreado->plan_id,
            'programa_materia_id' => $materiaCreada->materia_id,
            'programa_anio' => $anioPrograma,
            'programa_descripcion' => null,
        ]);

        return [
            'institucion' => $carrera->institucion,
            'carrera' => $carrera,
            'plan' => $planCreado,
            'materia' => $materiaCreada,
            'programa' => $programa,
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

    private function crearTema(string $nombre): Tema
    {
        return Tema::query()->create([
            'tema_nombre' => $nombre,
            'tema_descripcion' => null,
            'tema_id_tema_padre' => null,
        ]);
    }
}
