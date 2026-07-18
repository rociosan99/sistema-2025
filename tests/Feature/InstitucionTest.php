<?php

namespace Tests\Feature;

use App\Models\Institucion;
use App\Models\Carrera;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InstitucionTest extends TestCase
{
    use RefreshDatabase;

    public function test_se_puede_crear_una_institucion()
    {
        $institucion = Institucion::create([
            'institucion_nombre' => 'UNaM',
            'institucion_descripcion' => 'Universidad Nacional de Misiones',
        ]);

        $this->assertDatabaseHas('instituciones', [
            'institucion_id' => $institucion->institucion_id,
            'institucion_nombre' => 'UNaM',
        ]);
    }

    public function test_se_puede_modificar_una_institucion()
    {
        $institucion = Institucion::create([
            'institucion_nombre' => 'UNaM',
        ]);

        $institucion->update([
            'institucion_nombre' => 'UNaM Actualizada',
        ]);

        $this->assertDatabaseHas('instituciones', [
            'institucion_id' => $institucion->institucion_id,
            'institucion_nombre' => 'UNaM Actualizada',
        ]);
    }

    public function test_se_puede_eliminar_una_institucion()
    {
        $institucion = Institucion::create([
            'institucion_nombre' => 'UNaM',
        ]);

        $institucion->delete();

        $this->assertDatabaseMissing('instituciones', [
            'institucion_id' => $institucion->institucion_id,
        ]);
    }

    public function test_una_institucion_puede_tener_muchas_carreras()
    {
        $institucion = Institucion::create([
            'institucion_nombre' => 'UNaM',
        ]);

        Carrera::create([
            'carrera_nombre' => 'Analista',
            'carrera_institucion_id' => $institucion->institucion_id,
        ]);

        Carrera::create([
            'carrera_nombre' => 'Profesorado',
            'carrera_institucion_id' => $institucion->institucion_id,
        ]);

        $this->assertCount(2, $institucion->carreras);
    }
}