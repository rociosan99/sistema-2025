<?php

namespace App\Http\Controllers;

use App\Filament\Alumno\Pages\VerTurnoAlumno;
use App\Filament\Alumno\Resources\Turnos\TurnoResource;
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
    public function __invoke(Request $request, TurnoReemplazo $turnoReemplazo, string $accion, SolicitudMatchingService $matcher)
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

        return $accion === 'rechazar'
            ? $this->rechazar($turnoReemplazo)
            : $this->aceptar($turnoReemplazo, $matcher);
    }

    private function rechazar(TurnoReemplazo $inv)
    {
        $resultado = DB::transaction(function () use ($inv) {
            $invLocked = TurnoReemplazo::whereKey($inv->id)->lockForUpdate()->first();

            if (! $invLocked) {
                return ['estado' => 'no_disponible'];
            }

            if ($invLocked->estado === TurnoReemplazo::ESTADO_ACEPTADA) {
                return ['estado' => 'aceptada', 'turno' => $this->buscarTurnoCreado($invLocked)];
            }

            if (in_array($invLocked->estado, [TurnoReemplazo::ESTADO_RECHAZADA, TurnoReemplazo::ESTADO_EXPIRADA], true)) {
                return ['estado' => 'respondida'];
            }

            if ($invLocked->expires_at && $invLocked->expires_at->isPast()) {
                $invLocked->update(['estado' => TurnoReemplazo::ESTADO_EXPIRADA]);

                return ['estado' => 'expirada'];
            }

            $invLocked->update(['estado' => TurnoReemplazo::ESTADO_RECHAZADA]);

            return ['estado' => 'rechazada'];
        });

        if ($resultado['estado'] === 'aceptada') {
            return $this->redirigirATurnoAceptado($resultado['turno'], 'Esta invitación ya fue aceptada.');
        }

        if ($resultado['estado'] !== 'rechazada') {
            $mensaje = $resultado['estado'] === 'expirada'
                ? 'La invitación expiró.'
                : 'Esta invitación ya fue respondida.';

            return redirect(TurnoResource::getUrl('index', panel: 'alumno'))->with('error', $mensaje);
        }

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

        return redirect(TurnoResource::getUrl('index', panel: 'alumno'))->with('success', 'Invitación rechazada.');
    }

    private function aceptar(TurnoReemplazo $inv, SolicitudMatchingService $matcher)
    {
        /** @var Turno|null $turnoCancelado */
        $turnoCancelado = null;
        /** @var Turno|null $turnoNuevo */
        $turnoNuevo = null;
        $mensajeError = null;
        $yaAceptada = false;

        DB::transaction(function () use ($inv, $matcher, &$turnoCancelado, &$turnoNuevo, &$mensajeError, &$yaAceptada) {
            $invLocked = TurnoReemplazo::whereKey($inv->id)->lockForUpdate()->first();

            if (! $invLocked) {
                return;
            }

            if ($invLocked->estado === TurnoReemplazo::ESTADO_ACEPTADA) {
                $turnoNuevo = $this->buscarTurnoCreado($invLocked);
                $yaAceptada = true;

                return;
            }

            if (in_array($invLocked->estado, [TurnoReemplazo::ESTADO_RECHAZADA, TurnoReemplazo::ESTADO_EXPIRADA], true)) {
                $mensajeError = 'Esta invitación ya fue respondida.';

                return;
            }

            if ($invLocked->expires_at && $invLocked->expires_at->isPast()) {
                $invLocked->update(['estado' => TurnoReemplazo::ESTADO_EXPIRADA]);
                $mensajeError = 'La invitación expiró.';

                return;
            }

            $turnoCanceladoLocked = Turno::whereKey($invLocked->turno_cancelado_id)->lockForUpdate()->first();

            if (! $turnoCanceladoLocked || ! empty($turnoCanceladoLocked->reemplazado_por_turno_id)) {
                return;
            }

            User::query()->whereKey($invLocked->alumno_id)->lockForUpdate()->firstOrFail();

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
                ->whereIn('estado', [Turno::ESTADO_PENDIENTE, Turno::ESTADO_ACEPTADO, Turno::ESTADO_PENDIENTE_PAGO, Turno::ESTADO_CONFIRMADO])
                ->lockForUpdate()
                ->exists();

            if ($hayChoque) {
                return;
            }

            $turnoNuevoCreated = Turno::create([
                'alumno_id' => $invLocked->alumno_id,
                'profesor_id' => $invLocked->profesor_id,
                'materia_id' => $invLocked->materia_id,
                'tema_id' => $invLocked->tema_id,
                'fecha' => $invLocked->fecha,
                'hora_inicio' => $invLocked->hora_inicio,
                'hora_fin' => $invLocked->hora_fin,
                'estado' => Turno::ESTADO_PENDIENTE_PAGO,
                'enlace_clase' => $turnoCanceladoLocked->enlace_clase,
                'precio_por_hora' => $turnoCanceladoLocked->precio_por_hora,
                'precio_total' => $turnoCanceladoLocked->precio_total,
            ]);

            $turnoCanceladoLocked->update([
                'reemplazado_por_turno_id' => $turnoNuevoCreated->id,
                'reemplazado_at' => now(),
            ]);
            $invLocked->update(['estado' => TurnoReemplazo::ESTADO_ACEPTADA]);

            TurnoReemplazo::query()
                ->where('turno_cancelado_id', $turnoCanceladoLocked->id)
                ->where('id', '!=', $invLocked->id)
                ->where('estado', TurnoReemplazo::ESTADO_PENDIENTE)
                ->update(['estado' => TurnoReemplazo::ESTADO_EXPIRADA]);

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

        if ($yaAceptada) {
            return $this->redirigirATurnoAceptado($turnoNuevo, 'Esta invitación ya fue aceptada.');
        }

        if (! $turnoNuevo || ! $turnoCancelado) {
            return redirect(TurnoResource::getUrl('index', panel: 'alumno'))
                ->with('error', $mensajeError ?? 'No se pudo aceptar (quizás ya no está disponible).');
        }

        return $this->redirigirATurnoAceptado($turnoNuevo, '¡Aceptaste la clase! Ya podés continuar con el pago.');
    }

    private function buscarTurnoCreado(TurnoReemplazo $invitacion): ?Turno
    {
        $turnoCancelado = Turno::query()
            ->whereKey($invitacion->turno_cancelado_id)
            ->lockForUpdate()
            ->first();

        if (! $turnoCancelado?->reemplazado_por_turno_id) {
            return null;
        }

        return Turno::query()
            ->whereKey($turnoCancelado->reemplazado_por_turno_id)
            ->where('alumno_id', Auth::id())
            ->first();
    }

    private function redirigirATurnoAceptado(?Turno $turno, string $mensaje)
    {
        if (! $turno || (int) $turno->alumno_id !== (int) Auth::id()) {
            return redirect(TurnoResource::getUrl('index', panel: 'alumno'))
                ->with('error', 'La invitación ya fue respondida, pero no se encontró el turno asociado.');
        }

        return redirect(VerTurnoAlumno::getUrl(['record' => $turno->id], panel: 'alumno'))
            ->with('success', $mensaje);
    }
}
