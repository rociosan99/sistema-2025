<?php

namespace App\Http\Controllers;

use App\Models\Turno;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TurnoAsistenciaController extends Controller
{
    public function confirmar(Request $request, Turno $turno)
    {
        // Seguridad: solo el alumno dueño del turno
        if ((int) $turno->alumno_id !== (int) Auth::id()) {
            abort(403);
        }

        // Solo se puede confirmar si el profesor lo aceptó
        if ($turno->estado !== Turno::ESTADO_ACEPTADO) {
            return back()->with('error', 'Este turno no está en estado "aceptado".');
        }

        // Si ya confirmó antes, no hagas nada
        if ((bool) ($turno->asistencia_confirmada ?? false)) {
            return back()->with('success', 'Ya habías confirmado asistencia.');
        }

        // Confirmar asistencia + habilitar pago
        $turno->update([
            'asistencia_confirmada' => true,
            'asistencia_confirmada_at' => now(),
            'estado' => Turno::ESTADO_PENDIENTE_PAGO,
        ]);

        return back()->with('success', 'Asistencia confirmada. Ya podés pagar.');
    }
}
