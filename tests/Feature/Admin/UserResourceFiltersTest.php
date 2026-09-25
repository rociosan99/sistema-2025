<?php

namespace Tests\Feature\Admin;

use App\Filament\Resources\Users\Pages\ListUsers;
use App\Models\User;
use Filament\Tables\Enums\FiltersLayout;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class UserResourceFiltersTest extends TestCase
{
    use RefreshDatabase;

    private User $administradorAutenticado;

    protected function setUp(): void
    {
        parent::setUp();

        $this->administradorAutenticado = User::factory()->create([
            'role' => 'admin',
            'activo' => true,
        ]);

        $this->actingAs($this->administradorAutenticado);
    }

    #[DataProvider('roles')]
    public function test_filtra_usuarios_por_rol(string $rol): void
    {
        $usuarioDelRol = User::factory()->create(['role' => $rol]);
        $otroRol = $rol === 'alumno' ? 'profesor' : 'alumno';
        $usuarioDeOtroRol = User::factory()->create(['role' => $otroRol]);

        Livewire::test(ListUsers::class)
            ->filterTable('role', $rol)
            ->assertCanSeeTableRecords([$usuarioDelRol])
            ->assertCanNotSeeTableRecords([$usuarioDeOtroRol]);
    }

    public static function roles(): array
    {
        return [
            'Alumno' => ['alumno'],
            'Profesor' => ['profesor'],
            'Administrador' => ['admin'],
        ];
    }

    #[DataProvider('estados')]
    public function test_filtra_usuarios_por_estado(bool $activo): void
    {
        $usuarioDelEstado = User::factory()->create(['activo' => $activo]);
        $usuarioDeOtroEstado = User::factory()->create(['activo' => ! $activo]);

        Livewire::test(ListUsers::class)
            ->filterTable('activo', $activo ? 1 : 0)
            ->assertCanSeeTableRecords([$usuarioDelEstado])
            ->assertCanNotSeeTableRecords([$usuarioDeOtroEstado]);
    }

    public static function estados(): array
    {
        return [
            'Activo' => [true],
            'Dado de baja' => [false],
        ];
    }

    public function test_muestra_los_filtros_sobre_la_tabla_en_dos_columnas(): void
    {
        $componente = Livewire::test(ListUsers::class);
        $tabla = $componente->instance()->getTable();

        $this->assertSame(FiltersLayout::AboveContent, $tabla->getFiltersLayout());
        $this->assertSame(2, $tabla->getFiltersFormColumns());

        $componente
            ->assertTableFilterVisible('role')
            ->assertTableFilterVisible('activo');
    }

    public function test_combina_los_filtros_de_rol_y_estado(): void
    {
        $profesorActivo = User::factory()->create([
            'role' => 'profesor',
            'activo' => true,
        ]);
        $profesorInactivo = User::factory()->create([
            'role' => 'profesor',
            'activo' => false,
        ]);
        $alumnoActivo = User::factory()->create([
            'role' => 'alumno',
            'activo' => true,
        ]);

        Livewire::test(ListUsers::class)
            ->filterTable('role', 'profesor')
            ->filterTable('activo', 1)
            ->assertCanSeeTableRecords([$profesorActivo])
            ->assertCanNotSeeTableRecords([$profesorInactivo, $alumnoActivo]);
    }

    #[DataProvider('camposBusqueda')]
    public function test_mantiene_la_busqueda_existente(string $campo, string $valor, string $busqueda): void
    {
        $usuarioEncontrado = User::factory()->create([$campo => $valor]);
        $usuarioNoRelacionado = User::factory()->create([
            'name' => 'NombreDistinto',
            'apellido' => 'ApellidoDistinto',
            'email' => 'correo.distinto@example.test',
        ]);

        Livewire::test(ListUsers::class)
            ->searchTable($busqueda)
            ->assertCanSeeTableRecords([$usuarioEncontrado])
            ->assertCanNotSeeTableRecords([$usuarioNoRelacionado]);
    }

    public static function camposBusqueda(): array
    {
        return [
            'Nombre' => ['name', 'ValentinaUnica', 'ValentinaUnica'],
            'Apellido' => ['apellido', 'QuirogaUnico', 'QuirogaUnico'],
            'Email' => ['email', 'busqueda.unica@example.test', 'busqueda.unica@example.test'],
        ];
    }

    public function test_ordena_por_fecha_de_creacion(): void
    {
        $usuarioAnterior = User::factory()->create([
            'created_at' => '2026-01-10 10:00:00',
        ]);
        $usuarioReciente = User::factory()->create([
            'created_at' => '2026-02-10 10:00:00',
        ]);

        Livewire::test(ListUsers::class)
            ->sortTable('created_at', 'desc')
            ->assertCanSeeTableRecords([$usuarioReciente, $usuarioAnterior], inOrder: true);
    }
}
