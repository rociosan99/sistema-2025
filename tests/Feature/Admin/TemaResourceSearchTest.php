<?php

namespace Tests\Feature\Admin;

use App\Filament\Resources\Temas\Pages\ListTemas;
use App\Models\Tema;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class TemaResourceSearchTest extends TestCase
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

    public function test_busca_por_nombre_exacto_del_tema(): void
    {
        $variables = $this->crearTema('Variables');
        $funciones = $this->crearTema('Funciones');

        Livewire::test(ListTemas::class)
            ->searchTable('Variables')
            ->assertCanSeeTableRecords([$variables])
            ->assertCanNotSeeTableRecords([$funciones]);
    }

    public function test_busca_parcialmente_por_nombre_del_tema(): void
    {
        $encontrado = $this->crearTema('Programación orientada a objetos');
        $distinto = $this->crearTema('Bases de datos');

        Livewire::test(ListTemas::class)
            ->searchTable('orientada')
            ->assertCanSeeTableRecords([$encontrado])
            ->assertCanNotSeeTableRecords([$distinto]);
    }

    public function test_busca_subtemas_por_nombre_del_tema_padre(): void
    {
        $programacion = $this->crearTema('Programación');
        $variables = $this->crearTema('Variables', $programacion);
        $otroPadre = $this->crearTema('Matemática');
        $matrices = $this->crearTema('Matrices', $otroPadre);

        Livewire::test(ListTemas::class)
            ->searchTable('Programación')
            ->assertCanSeeTableRecords([$programacion, $variables])
            ->assertCanNotSeeTableRecords([$otroPadre, $matrices]);
    }

    public function test_termino_inexistente_no_devuelve_resultados(): void
    {
        $tema = $this->crearTema('Algoritmos');

        Livewire::test(ListTemas::class)
            ->searchTable('Termino que no existe')
            ->assertCanNotSeeTableRecords([$tema]);
    }

    public function test_busqueda_no_modifica_los_registros(): void
    {
        $padre = $this->crearTema('Programación');
        $tema = $this->crearTema('Variables', $padre);
        $antes = $tema->only(['tema_nombre', 'tema_descripcion', 'tema_id_tema_padre']);

        Livewire::test(ListTemas::class)->searchTable('Programación');

        $this->assertSame($antes, $tema->fresh()->only(array_keys($antes)));
        $this->assertDatabaseCount('temas', 2);
    }

    public function test_sin_busqueda_conserva_el_listado_original(): void
    {
        $padre = $this->crearTema('Programación');
        $hijo = $this->crearTema('Variables', $padre);
        $independiente = $this->crearTema('Álgebra');

        Livewire::test(ListTemas::class)
            ->assertCanSeeTableRecords([$padre, $hijo, $independiente]);
    }

    private function crearTema(string $nombre, ?Tema $padre = null): Tema
    {
        return Tema::query()->create([
            'tema_nombre' => $nombre,
            'tema_descripcion' => "Descripción de {$nombre}",
            'tema_id_tema_padre' => $padre?->tema_id,
        ]);
    }
}
