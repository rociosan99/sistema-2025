<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

// ✅ Spatie Activitylog
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class OfertaSolicitud extends Model
{
    // ✅ Auditoría automática
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
    ];

    protected $casts = [
        'expires_at' => 'datetime',
    ];

    /* =========================
     * ✅ Auditoría (Spatie)
     * ========================= */
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
}
