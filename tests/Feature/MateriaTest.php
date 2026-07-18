<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Materia;
use App\Models\Tema;
use Illuminate\Foundation\Testing\RefreshDatabase;

class MateriaTest extends TestCase
{
    use RefreshDatabase;

    public function test_se_puede_crear_una_materia(): void
    {
        $materia = Materia::create([
            'materia_nombre' => 'Programación I',
            'materia_descripcion' => 'Materia de prueba',
            'materia_anio' => 2,
        ]);

        $this->assertDatabaseHas('materias', [
            'materia_id' => $materia->materia_id,
            'materia_nombre' => 'Programación I',
        ]);
    }

    public function test_se_puede_modificar_una_materia(): void
    {
        $materia = Materia::create([
            'materia_nombre' => 'Programacion I',
            'materia_descripcion' => 'Descripcion original',
            'materia_anio' => 1,
        ]);

        $materia->update([
            'materia_nombre' => 'Programacion Avanzada',
            'materia_descripcion' => 'Descripcion modificada',
        ]);

        $this->assertDatabaseHas('materias', [
            'materia_id' => $materia->materia_id,
            'materia_nombre' => 'Programacion Avanzada',
            'materia_descripcion' => 'Descripcion modificada',
        ]);
    }

    public function test_se_puede_eliminar_una_materia(): void
    {
        $materia = Materia::create([
            'materia_nombre' => 'Materia a eliminar',
            'materia_descripcion' => 'Prueba',
            'materia_anio' => 1,
        ]);

        $id = $materia->materia_id;

        $materia->delete();

        $this->assertDatabaseMissing('materias', [
            'materia_id' => $id,
        ]);
    }

    public function test_se_puede_asignar_un_tema_a_una_materia(): void
    {
        $materia = Materia::create([
            'materia_nombre' => 'Programacion I',
            'materia_descripcion' => 'Materia de prueba',
            'materia_anio' => 1,
        ]);

        $tema = Tema::create([
            'tema_nombre' => 'Variables',
            'tema_descripcion' => 'Tema de prueba',
        ]);

        $materia->temasPivot()->attach($tema->tema_id);

        $this->assertDatabaseHas('materia_tema', [
            'materia_id' => $materia->materia_id,
            'tema_id' => $tema->tema_id,
        ]);
    }
    public function test_una_materia_puede_obtener_sus_temas(): void
    {
        $materia = Materia::create([
            'materia_nombre' => 'Programacion I',
            'materia_descripcion' => 'Materia de prueba',
            'materia_anio' => 1,
        ]);

        $tema = Tema::create([
            'tema_nombre' => 'Variables',
            'tema_descripcion' => 'Tema de prueba',
        ]);

        $materia->temasPivot()->attach($tema->tema_id);

        $this->assertCount(1, $materia->temasPivot);

        $this->assertEquals(
            'Variables',
            $materia->temasPivot->first()->tema_nombre
        );
    }
    
}