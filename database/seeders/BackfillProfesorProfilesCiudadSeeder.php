<?php

namespace Database\Seeders;

use App\Models\Ciudad;
use App\Models\ProfesorProfile;
use Illuminate\Database\Seeder;

class BackfillProfesorProfilesCiudadSeeder extends Seeder
{
    public function run(): void
    {
        $profiles = ProfesorProfile::query()
            ->whereNull('ciudad_id')
            ->whereNotNull('ciudad')
            ->get();

        foreach ($profiles as $profile) {
            $nombreCiudad = trim((string) $profile->ciudad);

            if ($nombreCiudad === '') {
                continue;
            }

            $ciudad = Ciudad::query()
                ->where('ciudad_nombre', $nombreCiudad)
                ->first();

            if ($ciudad) {
                $profile->ciudad_id = $ciudad->ciudad_id;
                $profile->save();
            }
        }
    }
}