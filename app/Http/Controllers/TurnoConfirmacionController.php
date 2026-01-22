<?php

namespace App\Http\Controllers;

use App\Models\Turno;
use Illuminate\Http\Request;

class TurnoConfirmacionController extends Controller
{
    /**
     * Validación: el link viene firmado + trae alumno_id.
     * Esto permite que funcione aunque el alumno no esté logueado.
     */
    protected function validarAlumno(Request $request, Turno $turno): void
    {
        $alumnoId = (int) $request->query('alumno_id');

        // alumno_id debe venir en el link
        if (! $alumnoId) {
            abort(403, 'Link inválido.');
        }

        // Debe coincidir con el alumno real del turno
        if ((int) $turno->alumno_id !== $alumnoId) {
            abort(403, 'No autorizado.');
        }
    }

    public function confirmar(Request $request, Turno $turno)
    {
        $this->validarAlumno($request, $turno);

        if ($turno->estado !== 'aceptado') {
            return response()->view('turnos.confirmacion-resultado', [
                'titulo' => 'No disponible',
                'mensaje' => 'Este turno ya no está disponible para confirmar.',
            ]);
        }

        $turno->update(['estado' => 'pendiente_pago']);

        return response()->view('turnos.confirmacion-resultado', [
            'titulo' => '¡Listo!',
            'mensaje' => 'Asistencia confirmada. Turno pendiente de pago.',
        ]);
    }

    public function cancelar(Request $request, Turno $turno)
    {
        $this->validarAlumno($request, $turno);

        if (! in_array($turno->estado, ['aceptado', 'pendiente_pago'], true)) {
            return response()->view('turnos.confirmacion-resultado', [
                'titulo' => 'No disponible',
                'mensaje' => 'Este turno ya no está disponible para cancelar.',
            ]);
        }

        $turno->update(['estado' => 'cancelado']);

        return response()->view('turnos.confirmacion-resultado', [
            'titulo' => 'Cancelado',
            'mensaje' => 'Turno cancelado. Se liberó el horario.',
        ]);
    }
}
