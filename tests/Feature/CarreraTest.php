<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\Carrera;
use App\Models\Institucion;

class CarreraTest extends TestCase
{
    use RefreshDatabase;

    public function test_se_puede_crear_una_carrera()
    {
        $institucion = Institucion::create([
            'institucion_nombre' => 'UNaM',
        ]);

        $carrera = Carrera::create([
            'carrera_nombre' => 'Licenciatura en Sistemas',
            'carrera_descripcion' => 'Carrera de prueba',
            'carrera_institucion_id' => $institucion->institucion_id,
        ]);

        $this->assertDatabaseHas('carreras', [
            'carrera_id' => $carrera->carrera_id,
            'carrera_nombre' => 'Licenciatura en Sistemas',
        ]);
    }

    public function test_se_puede_modificar_una_carrera()
    {
        $institucion = Institucion::create([
            'institucion_nombre' => 'UNaM',
        ]);

        $carrera = Carrera::create([
            'carrera_nombre' => 'Licenciatura en Sistemas',
            'carrera_institucion_id' => $institucion->institucion_id,
        ]);

        $carrera->update([
            'carrera_nombre' => 'Ingeniería en Sistemas',
        ]);

        $this->assertDatabaseHas('carreras', [
            'carrera_id' => $carrera->carrera_id,
            'carrera_nombre' => 'Ingeniería en Sistemas',
        ]);
    }

    public function test_se_puede_eliminar_una_carrera()
    {
        $institucion = Institucion::create([
            'institucion_nombre' => 'UNaM',
        ]);

        $carrera = Carrera::create([
            'carrera_nombre' => 'Licenciatura en Sistemas',
            'carrera_institucion_id' => $institucion->institucion_id,
        ]);

        $id = $carrera->carrera_id;

        $carrera->delete();

        $this->assertDatabaseMissing('carreras', [
            'carrera_id' => $id,
        ]);
    }

    public function test_una_carrera_pertenece_a_una_institucion()
    {
        $institucion = Institucion::create([
            'institucion_nombre' => 'UNaM',
        ]);

        $carrera = Carrera::create([
            'carrera_nombre' => 'Licenciatura en Sistemas',
            'carrera_institucion_id' => $institucion->institucion_id,
        ]);

        $this->assertEquals(
            $institucion->institucion_id,
            $carrera->institucion->institucion_id
        );
    }

    public function test_una_institucion_puede_tener_muchas_carreras()
    {
        $institucion = Institucion::create([
            'institucion_nombre' => 'UNaM',
        ]);

        Carrera::create([
            'carrera_nombre' => 'Licenciatura en Sistemas',
            'carrera_institucion_id' => $institucion->institucion_id,
        ]);

        Carrera::create([
            'carrera_nombre' => 'Profesorado en Computación',
            'carrera_institucion_id' => $institucion->institucion_id,
        ]);

        $this->assertCount(
            2,
            $institucion->carreras
        );
    }
}