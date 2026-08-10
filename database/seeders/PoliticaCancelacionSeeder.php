<?php

namespace Database\Seeders;

use App\Models\PoliticaCancelacion;
use Illuminate\Database\Seeder;

class PoliticaCancelacionSeeder extends Seeder
{
    public function run(): void
    {
        $politica = PoliticaCancelacion::query()->firstOrCreate(
            ['codigo' => PoliticaCancelacion::CODIGO_CANCELACION_ALUMNO],
            [
                'horas_cancelacion_sin_penalizacion' => 24,
                'porcentaje_credito_anticipado' => 100,
                'porcentaje_credito_tardio' => 75,
                'porcentaje_penalizacion_tardia' => 25,
                'vigencia_creditos_dias' => 90,
                'porcentaje_profesor_penalizacion' => 80,
                'porcentaje_plataforma_penalizacion' => 20,
            ],
        );

        if ($politica->vigencia_creditos_dias === null) {
            $politica->update(['vigencia_creditos_dias' => 90]);
        }

        if (
            $politica->porcentaje_profesor_penalizacion === null
            && $politica->porcentaje_plataforma_penalizacion === null
        ) {
            $politica->update([
                'porcentaje_profesor_penalizacion' => 80,
                'porcentaje_plataforma_penalizacion' => 20,
            ]);
        }
    }
}
