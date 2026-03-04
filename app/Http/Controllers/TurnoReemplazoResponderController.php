<?php

namespace App\Http\Controllers;

use App\Jobs\NotificarReemplazoNoConseguidoJob;
use App\Mail\ProfesorReemplazoConfirmado;
use App\Models\Turno;
use App\Models\TurnoReemplazo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

class TurnoReemplazoResponderController extends Controller
{
    public function __invoke(Request $request, TurnoReemplazo $turnoReemplazo, string $accion)
    {
        // ✅ Si no está logueado: mandar al login del panel alumno
        if (! Auth::check()) {
            return redirect()->guest(route('filament.alumno.auth.login'));
        }

        // ✅ Solo el alumno dueño de la invitación puede responder
        if ((int) $turnoReemplazo->alumno_id !== (int) Auth::id()) {
            abort(403);
        }

        if (! in_array($accion, ['aceptar', 'rechazar'], true)) {
            abort(404);
        }

        // ✅ Si expiró, marcar como expirada
        if ($turnoReemplazo->expires_at && $turnoReemplazo->expires_at->isPast()) {
            $turnoReemplazo->update(['estado' => TurnoReemplazo::ESTADO_EXPIRADA]);
            return back()->with('error', 'La invitación expiró.');
        }

        return $accion === 'rechazar'
            ? $this->rechazar($turnoReemplazo)
            : $this->aceptar($turnoReemplazo);
    }

    private function rechazar(TurnoReemplazo $inv)
    {
        DB::transaction(function () use ($inv) {
            $invLocked = TurnoReemplazo::whereKey($inv->id)->lockForUpdate()->first();

            if (! $invLocked) return;
            if ($invLocked->estado !== TurnoReemplazo::ESTADO_PENDIENTE) return;

            $invLocked->update(['estado' => TurnoReemplazo::ESTADO_RECHAZADA]);
        });

        // ✅ Si ya NO quedan invitaciones pendientes vigentes, disparar aviso "no conseguido"
        $pendientes = TurnoReemplazo::query()
            ->where('turno_cancelado_id', $inv->turno_cancelado_id)
            ->where('estado', TurnoReemplazo::ESTADO_PENDIENTE)
            ->where(function ($q) {
                $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
            })
            ->exists();

        if (! $pendientes) {
            dispatch(new NotificarReemplazoNoConseguidoJob($inv->turno_cancelado_id));
        }

        return back()->with('success', 'Invitación rechazada.');
    }

    private function aceptar(TurnoReemplazo $inv)
    {
        /** @var Turno|null $turnoCancelado */
        $turnoCancelado = null;

        /** @var Turno|null $turnoNuevo */
        $turnoNuevo = null;

        DB::transaction(function () use ($inv, &$turnoCancelado, &$turnoNuevo) {

            $invLocked = TurnoReemplazo::whereKey($inv->id)->lockForUpdate()->first();
            if (! $invLocked) return;

            if ($invLocked->estado !== TurnoReemplazo::ESTADO_PENDIENTE) return;

            if ($invLocked->expires_at && $invLocked->expires_at->isPast()) {
                $invLocked->update(['estado' => TurnoReemplazo::ESTADO_EXPIRADA]);
                return;
            }

            $turnoCanceladoLocked = Turno::whereKey($invLocked->turno_cancelado_id)->lockForUpdate()->first();
            if (! $turnoCanceladoLocked) return;

            // si ya se reemplazó, no hacer nada
            if (! empty($turnoCanceladoLocked->reemplazado_por_turno_id)) return;

            // ✅ anti-choque con turnos del profesor
            $hayChoque = Turno::where('profesor_id', $invLocked->profesor_id)
                ->whereDate('fecha', $invLocked->fecha)
                ->where(function ($q) use ($invLocked) {
                    $q->where('hora_inicio', '<', $invLocked->hora_fin)
                      ->where('hora_fin', '>', $invLocked->hora_inicio);
                })
                ->whereIn('estado', [
                    Turno::ESTADO_PENDIENTE,
                    Turno::ESTADO_ACEPTADO,
                    Turno::ESTADO_PENDIENTE_PAGO,
                    Turno::ESTADO_CONFIRMADO,
                ])
                ->lockForUpdate()
                ->exists();

            if ($hayChoque) {
                return;
            }

            // ✅ Crear turno nuevo (pendiente_pago)
            $turnoNuevoCreated = Turno::create([
                'alumno_id'       => $invLocked->alumno_id,
                'profesor_id'     => $invLocked->profesor_id,
                'materia_id'      => $invLocked->materia_id,
                'tema_id'         => $invLocked->tema_id,
                'fecha'           => $invLocked->fecha,
                'hora_inicio'     => $invLocked->hora_inicio,
                'hora_fin'        => $invLocked->hora_fin,
                'estado'          => Turno::ESTADO_PENDIENTE_PAGO,
                'precio_por_hora' => $turnoCanceladoLocked->precio_por_hora,
                'precio_total'    => $turnoCanceladoLocked->precio_total,
            ]);

            // ✅ Marcar el cancelado como reemplazado
            $turnoCanceladoLocked->update([
                'reemplazado_por_turno_id' => $turnoNuevoCreated->id,
                'reemplazado_at' => now(),
            ]);

            // ✅ Marcar invitación aceptada y expirar otras
            $invLocked->update(['estado' => TurnoReemplazo::ESTADO_ACEPTADA]);

            TurnoReemplazo::query()
                ->where('turno_cancelado_id', $turnoCanceladoLocked->id)
                ->where('id', '!=', $invLocked->id)
                ->where('estado', TurnoReemplazo::ESTADO_PENDIENTE)
                ->update(['estado' => TurnoReemplazo::ESTADO_EXPIRADA]);

            // devolver modelos para fuera de la tx
            $turnoCancelado = $turnoCanceladoLocked;
            $turnoNuevo = $turnoNuevoCreated;
        });

        if (! $turnoNuevo || ! $turnoCancelado) {
            return back()->with('error', 'No se pudo aceptar (quizás ya no está disponible).');
        }

        // ✅ Mail al profesor: tu mailable requiere 2 argumentos
        $turnoCancelado->loadMissing(['alumno', 'materia', 'tema', 'profesor']);
        $turnoNuevo->loadMissing(['alumno', 'materia', 'tema', 'profesor']);

        if ($turnoNuevo->profesor?->email) {
            Mail::to($turnoNuevo->profesor->email)->send(
                new ProfesorReemplazoConfirmado($turnoCancelado, $turnoNuevo)
            );
        }

        return back()->with('success', '¡Aceptaste la clase! Te aparecerá para pagar.');
    }
}