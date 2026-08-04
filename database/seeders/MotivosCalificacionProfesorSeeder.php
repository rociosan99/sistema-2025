<?php

namespace Database\Seeders;

use App\Models\MotivoCalificacion;
use Illuminate\Database\Seeder;

class MotivosCalificacionProfesorSeeder extends Seeder
{
    public function run(): void
    {
        $motivos = [
            5 => ['Excelente explicación.', 'Dominio del tema.', 'Muy puntual.', 'Excelente predisposición.', 'Clase dinámica.'],
            4 => ['Buena explicación.', 'Algunos aspectos a mejorar.', 'Ritmo mejorable.', 'Faltó profundizar algunos temas.'],
            3 => ['Explicación poco clara.', 'Contenido insuficiente.', 'Organización mejorable.', 'Problemas de comunicación.'],
            2 => ['Llegó tarde.', 'Falta de preparación.', 'Problemas técnicos.', 'Poco compromiso.'],
            1 => ['No se presentó.', 'La clase no se realizó correctamente.', 'Conducta inapropiada.', 'Incumplimiento grave.'],
        ];

        foreach ($motivos as $estrellas => $descripciones) {
            foreach ($descripciones as $indice => $descripcion) {
                MotivoCalificacion::query()->firstOrCreate(
                    [
                        'tipo_evaluado' => MotivoCalificacion::TIPO_PROFESOR,
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
