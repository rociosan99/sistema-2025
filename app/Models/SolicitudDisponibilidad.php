<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

// ✅ Spatie Activitylog
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class SolicitudDisponibilidad extends Model
{
    // ✅ Auditoría automática
    use LogsActivity;

    protected $table = 'solicitudes_disponibilidad';

    // Estados
    public const ESTADO_ACTIVA    = 'activa';
    public const ESTADO_TOMADA    = 'tomada';
    public const ESTADO_CANCELADA = 'cancelada';
    public const ESTADO_EXPIRADA  = 'expirada';
    public const ESTADO_VENCIDA   = 'vencida';

    protected $fillable = [
        'alumno_id',
        'materia_id',
        'tema_id',
        'fecha',
        'hora_inicio',
        'hora_fin',
        'estado',
        'expires_at',
    ];

    protected $casts = [
        'fecha' => 'date',
        'hora_inicio' => 'string',
        'hora_fin' => 'string',
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
                'alumno_id',
                'materia_id',
                'tema_id',
                'fecha',
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
        return "solicitud_disponibilidad_{$eventName}";
    }

    public function alumno()
    {
        return $this->belongsTo(User::class, 'alumno_id', 'id');
    }

    public function materia()
    {
        return $this->belongsTo(Materia::class, 'materia_id', 'materia_id');
    }

    public function tema()
    {
        return $this->belongsTo(Tema::class, 'tema_id', 'tema_id');
    }

    public function ofertas()
    {
        return $this->hasMany(OfertaSolicitud::class, 'solicitud_id', 'id');
    }
}
