<?php

namespace App\Http\Controllers;

use App\Mail\LinkPagoTurno;
use App\Models\Turno;
use App\Services\MercadoPagoService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class TurnoConfirmacionController extends Controller
{
    /**
     * Validación: el link viene firmado + trae alumno_id.
     * Esto permite que funcione aunque el alumno no esté logueado.
     */
    protected function validarAlumno(Request $request, Turno $turno): void
    {
        $alumnoId = (int) $request->query('alumno_id');

        if (! $alumnoId) {
            abort(403, 'Link inválido.');
        }

        if ((int) $turno->alumno_id !== $alumnoId) {
            abort(403, 'No autorizado.');
        }
    }

    public function confirmar(Request $request, Turno $turno, MercadoPagoService $mp)
    {
        $this->validarAlumno($request, $turno);

        if ($turno->estado !== Turno::ESTADO_ACEPTADO) {
            return response()->view('turnos.confirmacion-resultado', [
                'titulo'  => 'No disponible',
                'mensaje' => 'Este turno ya no está disponible para confirmar.',
            ]);
        }

        // 1) pasa a pendiente_pago
        $turno->update(['estado' => Turno::ESTADO_PENDIENTE_PAGO]);

        // 2) generar (o reutilizar) link de pago y guardarlo en tabla pagos
        $pago = $mp->crearLinkDePagoParaTurno($turno);

        // 3) enviar mail con link a Mercado Pago
        if ($turno->alumno?->email && $pago?->mp_init_point) {
            Mail::to($turno->alumno->email)->send(new LinkPagoTurno($turno, $pago));
        }

        return response()->view('turnos.confirmacion-resultado', [
            'titulo'  => '¡Listo!',
            'mensaje' => 'Asistencia confirmada. Te enviamos un mail con el link para pagar la clase.',
        ]);
    }

    public function cancelar(Request $request, Turno $turno)
    {
        $this->validarAlumno($request, $turno);

        if (! in_array($turno->estado, [Turno::ESTADO_ACEPTADO, Turno::ESTADO_PENDIENTE_PAGO], true)) {
            return response()->view('turnos.confirmacion-resultado', [
                'titulo'  => 'No disponible',
                'mensaje' => 'Este turno ya no está disponible para cancelar.',
            ]);
        }

        $turno->update(['estado' => Turno::ESTADO_CANCELADO]);

        return response()->view('turnos.confirmacion-resultado', [
            'titulo'  => 'Cancelado',
            'mensaje' => 'Turno cancelado. Se liberó el horario.',
        ]);
    }
}
