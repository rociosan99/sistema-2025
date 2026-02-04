<?php

namespace App\Http\Controllers;

use App\Models\Turno;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TurnoCancelarPanelController extends Controller
{
    public function __invoke(Request $request, Turno $turno)
    {
        if ((int) $turno->alumno_id !== (int) Auth::id()) {
            abort(403);
        }

        // No cancelar si ya es final
        if (in_array($turno->estado, [
            Turno::ESTADO_CONFIRMADO,
            Turno::ESTADO_RECHAZADO,
            Turno::ESTADO_VENCIDO,
            Turno::ESTADO_CANCELADO,
        ], true)) {
            return back()->with('error', 'Este turno no se puede cancelar.');
        }

        // Si ya pasó la hora, no debería cancelarse (por las dudas)
        if ($turno->finDateTime()->isPast()) {
            $turno->update(['estado' => Turno::ESTADO_VENCIDO]);
            return back()->with('error', 'Este turno ya venció.');
        }

        // Estados permitidos para cancelar
        if (! in_array($turno->estado, [
            Turno::ESTADO_PENDIENTE,
            Turno::ESTADO_ACEPTADO,
            Turno::ESTADO_PENDIENTE_PAGO,
        ], true)) {
            return back()->with('error', 'Estado inválido para cancelar.');
        }

        $turno->update(['estado' => Turno::ESTADO_CANCELADO]);

        return back()->with('success', 'Turno cancelado.');
    }
}
