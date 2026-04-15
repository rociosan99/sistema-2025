<?php

namespace Database\Seeders;

use App\Models\Ciudad;
use App\Models\Pais;
use App\Models\Provincia;
use Illuminate\Database\Seeder;

class UbicacionesSeeder extends Seeder
{
    public function run(): void
    {
        $argentina = Pais::firstOrCreate(
            ['pais_nombre' => 'Argentina']
        );

        $provincias = [
            'Buenos Aires',
            'Catamarca',
            'Chaco',
            'Chubut',
            'Córdoba',
            'Corrientes',
            'Entre Ríos',
            'Formosa',
            'Jujuy',
            'La Pampa',
            'La Rioja',
            'Mendoza',
            'Misiones',
            'Neuquén',
            'Río Negro',
            'Salta',
            'San Juan',
            'San Luis',
            'Santa Cruz',
            'Santa Fe',
            'Santiago del Estero',
            'Tierra del Fuego, Antártida e Islas del Atlántico Sur',
            'Tucumán',
            'Ciudad Autónoma de Buenos Aires',
        ];

        $provinciaIds = [];

        foreach ($provincias as $provinciaNombre) {
            $provincia = Provincia::firstOrCreate([
                'pais_id' => $argentina->pais_id,
                'provincia_nombre' => $provinciaNombre,
            ]);

            $provinciaIds[$provinciaNombre] = $provincia->provincia_id;
        }

        $ciudades = [
            ['provincia' => 'Ciudad Autónoma de Buenos Aires', 'ciudad' => 'Buenos Aires'],
            ['provincia' => 'Córdoba', 'ciudad' => 'Córdoba'],
            ['provincia' => 'Santa Fe', 'ciudad' => 'Rosario'],
            ['provincia' => 'Mendoza', 'ciudad' => 'Mendoza'],
            ['provincia' => 'Buenos Aires', 'ciudad' => 'La Plata'],
            ['provincia' => 'Tucumán', 'ciudad' => 'San Miguel de Tucumán'],
            ['provincia' => 'Buenos Aires', 'ciudad' => 'Mar del Plata'],
            ['provincia' => 'Salta', 'ciudad' => 'Salta'],
            ['provincia' => 'Santa Fe', 'ciudad' => 'Santa Fe'],
            ['provincia' => 'San Juan', 'ciudad' => 'San Juan'],
            ['provincia' => 'Chaco', 'ciudad' => 'Resistencia'],
            ['provincia' => 'Neuquén', 'ciudad' => 'Neuquén'],
            ['provincia' => 'Santiago del Estero', 'ciudad' => 'Santiago del Estero'],
            ['provincia' => 'Corrientes', 'ciudad' => 'Corrientes'],
            ['provincia' => 'Misiones', 'ciudad' => 'Posadas'],
            ['provincia' => 'Buenos Aires', 'ciudad' => 'Bahía Blanca'],
            ['provincia' => 'Entre Ríos', 'ciudad' => 'Paraná'],
            ['provincia' => 'Formosa', 'ciudad' => 'Formosa'],
            ['provincia' => 'Jujuy', 'ciudad' => 'San Salvador de Jujuy'],
            ['provincia' => 'San Luis', 'ciudad' => 'San Luis'],
            ['provincia' => 'La Rioja', 'ciudad' => 'La Rioja'],
            ['provincia' => 'Catamarca', 'ciudad' => 'Catamarca'],
            ['provincia' => 'Córdoba', 'ciudad' => 'Río Cuarto'],
            ['provincia' => 'Chubut', 'ciudad' => 'Comodoro Rivadavia'],
            ['provincia' => 'Chubut', 'ciudad' => 'Trelew'],
            ['provincia' => 'Santa Cruz', 'ciudad' => 'Río Gallegos'],
            ['provincia' => 'Tierra del Fuego, Antártida e Islas del Atlántico Sur', 'ciudad' => 'Ushuaia'],
            ['provincia' => 'Chubut', 'ciudad' => 'Rawson'],
            ['provincia' => 'Río Negro', 'ciudad' => 'Viedma'],
            ['provincia' => 'La Pampa', 'ciudad' => 'Santa Rosa'],
        ];

        foreach ($ciudades as $item) {
            Ciudad::firstOrCreate([
                'provincia_id' => $provinciaIds[$item['provincia']],
                'ciudad_nombre' => $item['ciudad'],
            ]);
        }
    }
}