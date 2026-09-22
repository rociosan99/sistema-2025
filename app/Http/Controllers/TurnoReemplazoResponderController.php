<?php

namespace App\Http\Controllers;

use App\Jobs\NotificarReemplazoNoConseguidoJob;
use App\Mail\ProfesorReemplazoConfirmado;
use App\Models\Turno;
use App\Models\TurnoReemplazo;
use App\Models\User;
use App\Services\SolicitudMatchingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

class TurnoReemplazoResponderController extends Controller
{
    public function __invoke(
        Request $request,
        TurnoReemplazo $turnoReemplazo,
        string $accion,
        SolicitudMatchingService $matcher,
    )
    {
        if (! Auth::check()) {
            return redirect()->guest(route('filament.alumno.auth.login'));
        }

        if ((int) $turnoReemplazo->alumno_id !== (int) Auth::id()) {
            $intendedUrl = $request->fullUrl();

            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
            $request->session()->put('url.intended', $intendedUrl);

            return redirect()->route('filament.alumno.auth.login');
        }

        if (! in_array($accion, ['aceptar', 'rechazar'], true)) {
            abort(404);
        }

        if ($turnoReemplazo->expires_at && $turnoReemplazo->expires_at->isPast()) {
            $turnoReemplazo->update(['estado' => TurnoReemplazo::ESTADO_EXPIRADA]);

            return back()->with('error', 'La invitación expiró.');
        }

        return $accion === 'rechazar'
            ? $this->rechazar($turnoReemplazo)
            : $this->aceptar($turnoReemplazo, $matcher);
    }

    private function rechazar(TurnoReemplazo $inv)
    {
        DB::transaction(function () use ($inv) {
            $invLocked = TurnoReemplazo::whereKey($inv->id)->lockForUpdate()->first();

            if (! $invLocked) {
                return;
            }

            if ($invLocked->estado !== TurnoReemplazo::ESTADO_PENDIENTE) {
                return;
            }

            $invLocked->update([
                'estado' => TurnoReemplazo::ESTADO_RECHAZADA,
            ]);
        });

        $pendientes = TurnoReemplazo::query()
            ->where('turno_cancelado_id', $inv->turno_cancelado_id)
            ->where('estado', TurnoReemplazo::ESTADO_PENDIENTE)
            ->where(function ($q) {
                $q->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            })
            ->exists();

        if (! $pendientes) {
            dispatch(new NotificarReemplazoNoConseguidoJob($inv->turno_cancelado_id));
        }

        return back()->with('success', 'Invitación rechazada.');
    }

    private function aceptar(TurnoReemplazo $inv, SolicitudMatchingService $matcher)
    {
        /** @var Turno|null $turnoCancelado */
        $turnoCancelado = null;

        /** @var Turno|null $turnoNuevo */
        $turnoNuevo = null;

        $mensajeError = null;

        DB::transaction(function () use ($inv, $matcher, &$turnoCancelado, &$turnoNuevo, &$mensajeError) {
            $invLocked = TurnoReemplazo::whereKey($inv->id)->lockForUpdate()->first();

            if (! $invLocked) {
                return;
            }

            if ($invLocked->estado !== TurnoReemplazo::ESTADO_PENDIENTE) {
                return;
            }

            if ($invLocked->expires_at && $invLocked->expires_at->isPast()) {
                $invLocked->update([
                    'estado' => TurnoReemplazo::ESTADO_EXPIRADA,
                ]);

                return;
            }

            $turnoCanceladoLocked = Turno::whereKey($invLocked->turno_cancelado_id)
                ->lockForUpdate()
                ->first();

            if (! $turnoCanceladoLocked) {
                return;
            }

            if (! empty($turnoCanceladoLocked->reemplazado_por_turno_id)) {
                return;
            }

            User::query()
                ->whereKey($invLocked->alumno_id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($matcher->alumnoTieneChoque(
                (int) $invLocked->alumno_id,
                $invLocked->fecha->toDateString(),
                (string) $invLocked->hora_inicio,
                (string) $invLocked->hora_fin,
            )) {
                $mensajeError = 'Ya tenés otro turno en ese día y horario. Esta invitación ya no está disponible para vos.';

                return;
            }

            $hayChoque = Turno::query()
                ->where('profesor_id', $invLocked->profesor_id)
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

            $turnoNuevoCreated = Turno::create([
                'alumno_id'       => $invLocked->alumno_id,
                'profesor_id'     => $invLocked->profesor_id,
                'materia_id'      => $invLocked->materia_id,
                'tema_id'         => $invLocked->tema_id,
                'fecha'           => $invLocked->fecha,
                'hora_inicio'     => $invLocked->hora_inicio,
                'hora_fin'        => $invLocked->hora_fin,
                'estado'          => Turno::ESTADO_PENDIENTE_PAGO,
                'enlace_clase'    => $turnoCanceladoLocked->enlace_clase,
                'precio_por_hora' => $turnoCanceladoLocked->precio_por_hora,
                'precio_total'    => $turnoCanceladoLocked->precio_total,
            ]);

            $turnoCanceladoLocked->update([
                'reemplazado_por_turno_id' => $turnoNuevoCreated->id,
                'reemplazado_at' => now(),
            ]);

            $invLocked->update([
                'estado' => TurnoReemplazo::ESTADO_ACEPTADA,
            ]);

            TurnoReemplazo::query()
                ->where('turno_cancelado_id', $turnoCanceladoLocked->id)
                ->where('id', '!=', $invLocked->id)
                ->where('estado', TurnoReemplazo::ESTADO_PENDIENTE)
                ->update([
                    'estado' => TurnoReemplazo::ESTADO_EXPIRADA,
                ]);

            $turnoCancelado = $turnoCanceladoLocked;
            $turnoNuevo = $turnoNuevoCreated;

            DB::afterCommit(function () use ($turnoCanceladoLocked, $turnoNuevoCreated) {
                $turnoCanceladoLocked->loadMissing(['alumno', 'materia', 'tema', 'profesor']);
                $turnoNuevoCreated->loadMissing(['alumno', 'materia', 'tema', 'profesor']);

                if ($turnoCanceladoLocked->profesor?->email) {
                    Mail::to($turnoCanceladoLocked->profesor->email)
                        ->queue(new ProfesorReemplazoConfirmado($turnoCanceladoLocked, $turnoNuevoCreated));
                }
            });
        });

        if (! $turnoNuevo || ! $turnoCancelado) {
            return back()->with(
                'error',
                $mensajeError ?? 'No se pudo aceptar (quizás ya no está disponible).',
            );
        }

        return back()->with('success', '¡Aceptaste la clase! Te aparecerá para pagar.');
    }
}
