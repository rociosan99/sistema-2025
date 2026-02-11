<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

// ✅ Spatie Activitylog
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class CalificacionProfesor extends Model
{
    // ✅ Auditoría automática
    use LogsActivity;

    protected $table = 'calificaciones_profesor';

    protected $fillable = [
        'turno_id',
        'alumno_id',
        'profesor_id',
        'estrellas',
        'comentario',
    ];

    /* =========================
     * ✅ Auditoría (Spatie)
     * ========================= */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('audit')
            ->logOnly([
                'turno_id',
                'alumno_id',
                'profesor_id',
                'estrellas',
                // Si preferís NO guardar comentario por privacidad, borrá esta línea:
                'comentario',
            ])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    public function getDescriptionForEvent(string $eventName): string
    {
        return "calificacion_profesor_{$eventName}";
    }

    public function turno()
    {
        return $this->belongsTo(Turno::class, 'turno_id', 'id');
    }

    public function alumno()
    {
        return $this->belongsTo(User::class, 'alumno_id', 'id');
    }

    public function profesor()
    {
        return $this->belongsTo(User::class, 'profesor_id', 'id');
    }
}
