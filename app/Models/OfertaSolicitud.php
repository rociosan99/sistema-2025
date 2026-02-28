<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class OfertaSolicitud extends Model
{
    use LogsActivity;

    protected $table = 'ofertas_solicitud';

    public const ESTADO_PENDIENTE = 'pendiente';
    public const ESTADO_ACEPTADA  = 'aceptada';
    public const ESTADO_RECHAZADA = 'rechazada';
    public const ESTADO_EXPIRADA  = 'expirada';

    protected $fillable = [
        'solicitud_id',
        'profesor_id',
        'hora_inicio',
        'hora_fin',
        'estado',
        'expires_at',

        // ✅ RECOMENDACIÓN (NUEVO)
        'recommended_turno_id',
        'recommended_reason',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'hora_inicio' => 'string',
        'hora_fin' => 'string',
        'recommended_turno_id' => 'integer',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('audit')
            ->logOnly([
                'solicitud_id',
                'profesor_id',
                'hora_inicio',
                'hora_fin',
                'estado',
                'expires_at',
                'recommended_turno_id',
                'recommended_reason',
            ])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    public function getDescriptionForEvent(string $eventName): string
    {
        return "oferta_solicitud_{$eventName}";
    }

    public function solicitud()
    {
        return $this->belongsTo(SolicitudDisponibilidad::class, 'solicitud_id', 'id');
    }

    public function profesor()
    {
        return $this->belongsTo(User::class, 'profesor_id', 'id');
    }

    // ✅ Turno que originó la recomendación (turno cancelado)
    public function recommendedTurno()
    {
        return $this->belongsTo(Turno::class, 'recommended_turno_id', 'id');
    }
}
