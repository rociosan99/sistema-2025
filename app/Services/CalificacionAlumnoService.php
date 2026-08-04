<?php

namespace App\Services;

use App\Models\CalificacionAlumno;
use App\Models\MotivoCalificacion;
use App\Models\Turno;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class CalificacionAlumnoService
{
    public function calificar(
        int $profesorId,
        int $turnoId,
        mixed $estrellas,
        mixed $motivoIds,
        mixed $comentario,
    ): CalificacionAlumno {
        $datosValidados = Validator::make(
            [
                'estrellas' => $estrellas,
                'motivos' => $motivoIds,
                'comentario' => $comentario,
            ],
            [
                'estrellas' => ['required', 'integer', 'between:1,5'],
                'motivos' => ['required', 'array', 'min:1'],
                'motivos.*' => ['required', 'integer', 'distinct'],
                'comentario' => ['required', 'string', 'max:1000'],
            ],
            [
                'estrellas.between' => 'La calificación debe ser un número entero entre 1 y 5.',
                'motivos.required' => 'Seleccioná al menos un motivo.',
                'motivos.min' => 'Seleccioná al menos un motivo.',
                'comentario.required' => 'El comentario es obligatorio.',
                'comentario.max' => 'El comentario no puede superar los 1000 caracteres.',
            ]
        )->validate();

        $estrellas = (int) $datosValidados['estrellas'];
        $motivoIds = array_values(array_map('intval', $datosValidados['motivos']));
        $comentario = trim($datosValidados['comentario']);

        if ($comentario === '') {
            throw ValidationException::withMessages([
                'comentario' => 'El comentario es obligatorio.',
            ]);
        }

        if (mb_strlen($comentario) > 1000) {
            throw ValidationException::withMessages([
                'comentario' => 'El comentario no puede superar los 1000 caracteres.',
            ]);
        }

        return DB::transaction(function () use ($profesorId, $turnoId, $estrellas, $motivoIds, $comentario) {
            $turno = Turno::query()
                ->where('profesor_id', $profesorId)
                ->lockForUpdate()
                ->findOrFail($turnoId);

            if ($turno->estado !== Turno::ESTADO_CONFIRMADO) {
                throw ValidationException::withMessages([
                    'turno' => 'Este turno no está confirmado/pagado.',
                ]);
            }

            $fin = Carbon::parse($turno->fecha->format('Y-m-d').' '.$turno->hora_fin);

            if ($fin->isFuture()) {
                throw ValidationException::withMessages([
                    'turno' => 'Todavía no terminó la clase.',
                ]);
            }

            if (CalificacionAlumno::query()->where('turno_id', $turno->id)->exists()) {
                throw ValidationException::withMessages([
                    'turno' => 'Este turno ya fue calificado.',
                ]);
            }

            $motivosValidos = MotivoCalificacion::query()
                ->whereIn('id', $motivoIds)
                ->where('tipo_evaluado', MotivoCalificacion::TIPO_ALUMNO)
                ->where('estrellas', $estrellas)
                ->where('activo', true)
                ->pluck('id')
                ->map(fn ($id) => (int) $id)
                ->all();

            if (count($motivosValidos) !== count($motivoIds)) {
                throw ValidationException::withMessages([
                    'motivos' => 'Uno o más motivos no corresponden a la calificación seleccionada.',
                ]);
            }

            $calificacion = CalificacionAlumno::create([
                'turno_id' => $turno->id,
                'profesor_id' => $profesorId,
                'alumno_id' => $turno->alumno_id,
                'estrellas' => $estrellas,
                'comentario' => $comentario,
            ]);

            $calificacion->motivos()->sync($motivosValidos);

            return $calificacion;
        });
    }
}
