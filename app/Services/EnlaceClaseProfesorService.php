<?php

namespace App\Services;

use App\Models\ProfesorProfile;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class EnlaceClaseProfesorService
{
    private const MENSAJE_NO_CONFIGURADO =
        'Configurá tu enlace de clase predeterminado en Mi perfil antes de aceptar una clase.';

    public function obtenerPredeterminado(int $profesorId): string
    {
        $enlace = trim((string) ProfesorProfile::query()
            ->where('user_id', $profesorId)
            ->value('enlace_clase_default'));

        $validator = Validator::make(
            ['enlace_clase_default' => $enlace],
            ['enlace_clase_default' => ['required', 'url:http,https', 'max:2048']],
        );

        if ($validator->fails()) {
            throw ValidationException::withMessages([
                'enlace_clase_default' => self::MENSAJE_NO_CONFIGURADO,
            ]);
        }

        return $enlace;
    }

    /**
     * Regla prevista para etapas posteriores, todavía no conectada a Turno:
     * - mismo profesor: conservar el enlace que ya tenga el turno;
     * - cambio de profesor: usar el enlace predeterminado del nuevo profesor.
     */
}
