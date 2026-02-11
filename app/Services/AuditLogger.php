<?php

namespace App\Services;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Spatie\Activitylog\Models\Activity;

class AuditLogger
{
    /**
     * Loguea un evento de negocio.
     * - $event: ej "turno.cancelado_alumno", "pago.aprobado"
     * - $subject: modelo afectado (Turno/Pago/etc)
     * - $properties: contexto extra (monto, mp_payment_id, etc)
     */
    public function log(string $event, ?Model $subject = null, array $properties = [], ?int $causerId = null): Activity
    {
        $causer = null;

        if ($causerId) {
            // si querés: causer explícito (por ejemplo webhook sin login)
            $causer = \App\Models\User::find($causerId);
        } elseif (Auth::check()) {
            $causer = Auth::user();
        }

        $activity = activity('business')
            ->event($event);

        if ($causer) {
            $activity->causedBy($causer);
        }

        if ($subject) {
            $activity->performedOn($subject);
        }

        return $activity->withProperties($properties)->log($event);
    }
}
