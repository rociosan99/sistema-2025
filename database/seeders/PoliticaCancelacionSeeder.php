<?php

namespace Database\Seeders;

use App\Models\PoliticaCancelacion;
use Illuminate\Database\Seeder;

class PoliticaCancelacionSeeder extends Seeder
{
    public function run(): void
    {
        PoliticaCancelacion::query()->firstOrCreate(
            ['codigo' => PoliticaCancelacion::CODIGO_CANCELACION_ALUMNO],
            [
                'horas_cancelacion_sin_penalizacion' => 24,
                'porcentaje_credito_anticipado' => 100,
                'porcentaje_credito_tardio' => 75,
                'porcentaje_penalizacion_tardia' => 25,
            ],
        );
    }
}
