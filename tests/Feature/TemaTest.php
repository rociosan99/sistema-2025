<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\Tema;

class TemaTest extends TestCase
{
    use RefreshDatabase;

    public function se_puede_crear_un_tema()
    {
        $tema = Tema::create([
            'tema_nombre' => 'Álgebra',
            'tema_descripcion' => 'Tema de prueba',
        ]);

        $this->assertDatabaseHas('temas', [
            'tema_id' => $tema->tema_id,
            'tema_nombre' => 'Álgebra',
        ]);
    }

    
    public function se_puede_modificar_un_tema()
    {
        $tema = Tema::create([
            'tema_nombre' => 'Álgebra',
        ]);

        $tema->update([
            'tema_nombre' => 'Álgebra Lineal',
        ]);

        $this->assertDatabaseHas('temas', [
            'tema_id' => $tema->tema_id,
            'tema_nombre' => 'Álgebra Lineal',
        ]);
    }

    
    public function se_puede_eliminar_un_tema()
    {
        $tema = Tema::create([
            'tema_nombre' => 'Álgebra',
        ]);

        $id = $tema->tema_id;

        $tema->delete();

        $this->assertDatabaseMissing('temas', [
            'tema_id' => $id,
        ]);
    }

    
    public function un_tema_puede_tener_un_padre()
    {
        $padre = Tema::create([
            'tema_nombre' => 'Matemática',
        ]);

        $hijo = Tema::create([
            'tema_nombre' => 'Álgebra',
            'tema_id_tema_padre' => $padre->tema_id,
        ]);

        $this->assertEquals(
            $padre->tema_id,
            $hijo->parent->tema_id
        );
    }

    
    public function un_tema_puede_obtener_sus_hijos()
    {
        $padre = Tema::create([
            'tema_nombre' => 'Programación',
        ]);

        Tema::create([
            'tema_nombre' => 'Variables',
            'tema_id_tema_padre' => $padre->tema_id,
        ]);

        Tema::create([
            'tema_nombre' => 'Funciones',
            'tema_id_tema_padre' => $padre->tema_id,
        ]);

        $this->assertCount(2, $padre->children);
    }

    public function puede_obtener_todos_los_descendientes()
    {
        $padre = Tema::create([
            'tema_nombre' => 'Programación',
        ]);

        $hijo = Tema::create([
            'tema_nombre' => 'POO',
            'tema_id_tema_padre' => $padre->tema_id,
        ]);

        $nieto = Tema::create([
            'tema_nombre' => 'Herencia',
            'tema_id_tema_padre' => $hijo->tema_id,
        ]);

        $descendientes = Tema::getDescendantIds($padre->tema_id);

        $this->assertContains($hijo->tema_id, $descendientes);
        $this->assertContains($nieto->tema_id, $descendientes);
    }
}