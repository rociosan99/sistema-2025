<?php

namespace Database\Seeders;

use App\Models\MotivoCalificacion;
use Illuminate\Database\Seeder;

class MotivosCalificacionAlumnoSeeder extends Seeder
{
    public function run(): void
    {
        $motivos = [
            5 => [
                'Participó activamente.',
                'Llegó preparado.',
                'Fue puntual.',
                'Mostró mucho interés.',
                'Cumplió los objetivos de la clase.',
            ],
            4 => [
                'Buena participación.',
                'Comprendió la mayoría de los temas.',
                'Mostró interés.',
                'Algunos aspectos por reforzar.',
            ],
            3 => [
                'Participación irregular.',
                'Preparación insuficiente.',
                'Dificultades de comprensión.',
                'Necesita mayor práctica.',
            ],
            2 => [
                'Poco compromiso.',
                'Llegó tarde.',
                'No realizó las actividades.',
                'Escasa participación.',
            ],
            1 => [
                'No se presentó.',
                'Conducta inapropiada.',
                'Falta total de preparación.',
                'Incumplimiento grave.',
            ],
        ];

        foreach ($motivos as $estrellas => $descripciones) {
            foreach ($descripciones as $indice => $descripcion) {
                MotivoCalificacion::query()->firstOrCreate(
                    [
                        'tipo_evaluado' => MotivoCalificacion::TIPO_ALUMNO,
                        'estrellas' => $estrellas,
                        'descripcion' => $descripcion,
                    ],
                    [
                        'activo' => true,
                        'orden' => $indice + 1,
                    ]
                );
            }
        }
    }
}
