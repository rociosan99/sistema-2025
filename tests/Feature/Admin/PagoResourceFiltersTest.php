<?php

namespace Tests\Feature\Admin;

use App\Filament\Resources\Pagos\Pages\ListPagos;
use App\Filament\Resources\Pagos\PagoResource;
use App\Models\Materia;
use App\Models\Pago;
use App\Models\Turno;
use App\Models\User;
use Filament\Tables\Enums\FiltersLayout;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Support\CreatesTurnoScenarios;
use Tests\TestCase;

class PagoResourceFiltersTest extends TestCase
{
    use CreatesTurnoScenarios;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $administrador = User::factory()->create([
            'role' => 'admin',
            'activo' => true,
        ]);

        $this->actingAs($administrador);
    }

    #[DataProvider('estadosPago')]
    public function test_filtra_por_cada_estado_real(string $estado): void
    {
        $pagoDelEstado = $this->crearEscenarioPago(['estado' => $estado])['pago'];
        $otroEstado = $estado === Pago::ESTADO_APROBADO
            ? Pago::ESTADO_PENDIENTE
            : Pago::ESTADO_APROBADO;
        $pagoDeOtroEstado = $this->crearEscenarioPago(['estado' => $otroEstado])['pago'];

        Livewire::test(ListPagos::class)
            ->filterTable('estado', $estado)
            ->assertCanSeeTableRecords([$pagoDelEstado])
            ->assertCanNotSeeTableRecords([$pagoDeOtroEstado]);
    }

    public static function estadosPago(): array
    {
        return [
            'Pendiente' => [Pago::ESTADO_PENDIENTE],
            'Aprobado' => [Pago::ESTADO_APROBADO],
            'Rechazado' => [Pago::ESTADO_RECHAZADO],
            'Error' => [Pago::ESTADO_ERROR],
        ];
    }

    public function test_filtra_por_materia(): void
    {
        $escenarioBuscado = $this->crearEscenarioPago([], [], [], ['materia_nombre' => 'Álgebra Aplicada']);
        $escenarioDistinto = $this->crearEscenarioPago([], [], [], ['materia_nombre' => 'Física General']);

        Livewire::test(ListPagos::class)
            ->filterTable('materia_id', $escenarioBuscado['materia']->materia_id)
            ->assertCanSeeTableRecords([$escenarioBuscado['pago']])
            ->assertCanNotSeeTableRecords([$escenarioDistinto['pago']]);
    }

    public function test_filtra_desde_una_fecha_de_clase_inclusive(): void
    {
        $anterior = $this->crearEscenarioPago([], ['fecha' => '2026-09-10'])['pago'];
        $desde = $this->crearEscenarioPago([], ['fecha' => '2026-09-15'])['pago'];

        Livewire::test(ListPagos::class)
            ->filterTable('fecha_clase', ['desde' => '2026-09-15', 'hasta' => null])
            ->assertCanSeeTableRecords([$desde])
            ->assertCanNotSeeTableRecords([$anterior]);
    }

    public function test_filtra_hasta_una_fecha_de_clase_inclusive(): void
    {
        $hasta = $this->crearEscenarioPago([], ['fecha' => '2026-09-15'])['pago'];
        $posterior = $this->crearEscenarioPago([], ['fecha' => '2026-09-20'])['pago'];

        Livewire::test(ListPagos::class)
            ->filterTable('fecha_clase', ['desde' => null, 'hasta' => '2026-09-15'])
            ->assertCanSeeTableRecords([$hasta])
            ->assertCanNotSeeTableRecords([$posterior]);
    }

    public function test_filtra_por_rango_inclusive_de_fecha_de_clase(): void
    {
        $antes = $this->crearEscenarioPago([], ['fecha' => '2026-09-09'])['pago'];
        $inicio = $this->crearEscenarioPago([], ['fecha' => '2026-09-10'])['pago'];
        $fin = $this->crearEscenarioPago([], ['fecha' => '2026-09-20'])['pago'];
        $despues = $this->crearEscenarioPago([], ['fecha' => '2026-09-21'])['pago'];

        Livewire::test(ListPagos::class)
            ->filterTable('fecha_clase', ['desde' => '2026-09-10', 'hasta' => '2026-09-20'])
            ->assertCanSeeTableRecords([$inicio, $fin])
            ->assertCanNotSeeTableRecords([$antes, $despues]);
    }

    #[DataProvider('busquedas')]
    public function test_busca_por_datos_de_alumno_profesor_y_materia(
        string $tipo,
        string $campo,
        string $valor,
        string $busqueda,
    ): void {
        $alumno = [];
        $profesor = [];
        $materia = [];

        match ($tipo) {
            'alumno' => $alumno[$campo] = $valor,
            'profesor' => $profesor[$campo] = $valor,
            'materia' => $materia[$campo] = $valor,
        };

        $encontrado = $this->crearEscenarioPago([], [], $alumno, $materia, $profesor)['pago'];
        $noRelacionado = $this->crearEscenarioPago(
            [],
            [],
            ['name' => 'AlumnoDistinto', 'apellido' => 'SinCoincidencia'],
            ['materia_nombre' => 'Materia Diferente'],
            ['name' => 'ProfesorDistinto', 'apellido' => 'SinRelacion'],
        )['pago'];

        Livewire::test(ListPagos::class)
            ->searchTable($busqueda)
            ->assertCanSeeTableRecords([$encontrado])
            ->assertCanNotSeeTableRecords([$noRelacionado]);
    }

    public static function busquedas(): array
    {
        return [
            'Nombre del alumno' => ['alumno', 'name', 'MicaelaBuscada', 'MicaelaBuscada'],
            'Apellido del alumno' => ['alumno', 'apellido', 'SosaBuscada', 'SosaBuscada'],
            'Email del alumno' => ['alumno', 'email', 'alumno.buscado@example.test', 'alumno.buscado@example.test'],
            'Nombre del profesor' => ['profesor', 'name', 'LuciaBuscada', 'LuciaBuscada'],
            'Apellido del profesor' => ['profesor', 'apellido', 'GomezBuscada', 'GomezBuscada'],
            'Email del profesor' => ['profesor', 'email', 'profesor.buscado@example.test', 'profesor.buscado@example.test'],
            'Materia' => ['materia', 'materia_nombre', 'Base de Datos Buscada', 'Base de Datos Buscada'],
        ];
    }

    public function test_combina_estado_y_materia(): void
    {
        $materia = $this->crearMateria(['materia_nombre' => 'Programación Concurrente']);
        $coincidente = $this->crearEscenarioPago(
            ['estado' => Pago::ESTADO_APROBADO],
            [],
            [],
            [],
            [],
            $materia,
        )['pago'];
        $estadoDistinto = $this->crearEscenarioPago(
            ['estado' => Pago::ESTADO_PENDIENTE],
            [],
            [],
            [],
            [],
            $materia,
        )['pago'];
        $materiaDistinta = $this->crearEscenarioPago(['estado' => Pago::ESTADO_APROBADO])['pago'];

        Livewire::test(ListPagos::class)
            ->filterTable('estado', Pago::ESTADO_APROBADO)
            ->filterTable('materia_id', $materia->materia_id)
            ->assertCanSeeTableRecords([$coincidente])
            ->assertCanNotSeeTableRecords([$estadoDistinto, $materiaDistinta]);
    }

    public function test_puede_resetear_los_filtros(): void
    {
        $aprobado = $this->crearEscenarioPago(['estado' => Pago::ESTADO_APROBADO])['pago'];
        $pendiente = $this->crearEscenarioPago(['estado' => Pago::ESTADO_PENDIENTE])['pago'];

        Livewire::test(ListPagos::class)
            ->filterTable('estado', Pago::ESTADO_APROBADO)
            ->assertCanSeeTableRecords([$aprobado])
            ->assertCanNotSeeTableRecords([$pendiente])
            ->resetTableFilters()
            ->assertCanSeeTableRecords([$aprobado, $pendiente]);
    }

    public function test_conserva_layout_visible_y_resource_de_solo_lectura(): void
    {
        $pago = $this->crearEscenarioPago()['pago'];
        $tabla = Livewire::test(ListPagos::class)->instance()->getTable();

        $this->assertSame(FiltersLayout::AboveContent, $tabla->getFiltersLayout());
        $this->assertSame(4, $tabla->getFiltersFormColumns());
        $this->assertFalse(PagoResource::canCreate());
        $this->assertFalse(PagoResource::canEdit($pago));
        $this->assertFalse(PagoResource::canDelete($pago));
        $this->assertFalse(PagoResource::canDeleteAny());
    }

    /**
     * @return array{pago: Pago, turno: Turno, alumno: User, profesor: User, materia: Materia}
     */
    private function crearEscenarioPago(
        array $pago = [],
        array $turno = [],
        array $alumno = [],
        array $materia = [],
        array $profesor = [],
        ?Materia $materiaExistente = null,
    ): array {
        $alumnoCreado = $this->crearAlumno($alumno);
        $profesorCreado = $this->crearProfesor($profesor);
        $materiaCreada = $materiaExistente ?? $this->crearMateria($materia);
        $turnoCreado = $this->crearTurno($alumnoCreado, $profesorCreado, $materiaCreada, $turno);
        $pagoCreado = $this->crearPago($turnoCreado, $pago);

        return [
            'pago' => $pagoCreado,
            'turno' => $turnoCreado,
            'alumno' => $alumnoCreado,
            'profesor' => $profesorCreado,
            'materia' => $materiaCreada,
        ];
    }
}
