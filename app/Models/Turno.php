<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

// ✅ Spatie Activitylog
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class Turno extends Model
{
    use HasFactory;
    use LogsActivity; // ✅ Auditoría automática

    protected $table = 'turnos';

    // Estados
    public const ESTADO_PENDIENTE       = 'pendiente';
    public const ESTADO_ACEPTADO        = 'aceptado'; // legacy
    public const ESTADO_RECHAZADO       = 'rechazado';
    public const ESTADO_PENDIENTE_PAGO  = 'pendiente_pago';
    public const ESTADO_CONFIRMADO      = 'confirmado'; // pago OK
    public const ESTADO_CANCELADO       = 'cancelado';
    public const ESTADO_VENCIDO         = 'vencido';

    protected $fillable = [
        'alumno_id',
        'profesor_id',
        'materia_id',
        'tema_id',
        'fecha',
        'hora_inicio',
        'hora_fin',
        'estado',
        'precio_por_hora',
        'precio_total',

        // legacy
        'asistencia_confirmada_at',
        'asistencia_cancelada_at',
    ];

    protected $casts = [
        'fecha' => 'date',
        'hora_inicio' => 'string',
        'hora_fin' => 'string',
        'precio_por_hora' => 'decimal:2',
        'precio_total' => 'decimal:2',

        'asistencia_confirmada_at' => 'datetime',
        'asistencia_cancelada_at' => 'datetime',
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
                'profesor_id',
                'materia_id',
                'tema_id',
                'fecha',
                'hora_inicio',
                'hora_fin',
                'estado',
                'precio_por_hora',
                'precio_total',
                'asistencia_confirmada_at',
                'asistencia_cancelada_at',
            ])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    public function getDescriptionForEvent(string $eventName): string
    {
        return "turno_{$eventName}";
    }

    /* =========================
     * Relaciones
     * ========================= */

    public function alumno()
    {
        return $this->belongsTo(User::class, 'alumno_id');
    }

    public function profesor()
    {
        return $this->belongsTo(User::class, 'profesor_id');
    }

    public function materia()
    {
        return $this->belongsTo(Materia::class, 'materia_id', 'materia_id');
    }

    public function tema()
    {
        return $this->belongsTo(Tema::class, 'tema_id', 'tema_id');
    }

    public function pago()
    {
        return $this->hasOne(Pago::class, 'turno_id', 'id');
    }

    public function calificacionProfesor()
    {
        return $this->hasOne(CalificacionProfesor::class, 'turno_id', 'id');
    }

    public function calificacionAlumno()
    {
        return $this->hasOne(\App\Models\CalificacionAlumno::class, 'turno_id', 'id');
    }

    /* =========================
     * Accessors / Helpers
     * ========================= */

    public function getFechaFormateadaAttribute(): string
    {
        return Carbon::parse($this->fecha)->format('d/m/Y');
    }

    public function getHorarioAttribute(): string
    {
        $inicio = $this->hora_inicio ? substr((string) $this->hora_inicio, 0, 5) : '--:--';
        $fin    = $this->hora_fin ? substr((string) $this->hora_fin, 0, 5) : '--:--';

        return "{$inicio} - {$fin}";
    }

    public function estaPendiente(): bool
    {
        return $this->estado === self::ESTADO_PENDIENTE;
    }

    public function estaPendientePago(): bool { return $this->estado === self::ESTADO_PENDIENTE_PAGO; }
    public function estaConfirmado(): bool { return $this->estado === self::ESTADO_CONFIRMADO; }
    public function estaRechazado(): bool { return $this->estado === self::ESTADO_RECHAZADO; }
    public function estaCancelado(): bool { return $this->estado === self::ESTADO_CANCELADO; }
    public function estaVencido(): bool { return $this->estado === self::ESTADO_VENCIDO; }

    /**
     * ✅ Fecha+hora_inicio
     */
    public function inicioDateTime(): Carbon
    {
        $fechaStr = $this->fecha instanceof Carbon
            ? $this->fecha->format('Y-m-d')
            : Carbon::parse($this->fecha)->format('Y-m-d');

        $horaInicioStr = (string) ($this->hora_inicio ?? '00:00:00');

        if (preg_match('/^\d{4}-\d{2}-\d{2}\s+\d{2}:\d{2}:\d{2}$/', $horaInicioStr)) {
            $horaInicioStr = Carbon::parse($horaInicioStr)->format('H:i:s');
        }

        if (preg_match('/^\d{2}:\d{2}$/', $horaInicioStr)) {
            $horaInicioStr .= ':00';
        }

        return Carbon::parse("{$fechaStr} {$horaInicioStr}");
    }

    /**
     * ✅ Fecha+hora_fin
     */
    public function finDateTime(): Carbon
    {
        $fechaStr = $this->fecha instanceof Carbon
            ? $this->fecha->format('Y-m-d')
            : Carbon::parse($this->fecha)->format('Y-m-d');

        $horaFinStr = (string) ($this->hora_fin ?? '00:00:00');

        if (preg_match('/^\d{4}-\d{2}-\d{2}\s+\d{2}:\d{2}:\d{2}$/', $horaFinStr)) {
            $horaFinStr = Carbon::parse($horaFinStr)->format('H:i:s');
        }

        if (preg_match('/^\d{2}:\d{2}$/', $horaFinStr)) {
            $horaFinStr .= ':00';
        }

        return Carbon::parse("{$fechaStr} {$horaFinStr}");
    }

    public function deberiaMarcarseVencido(): bool
    {
        if (in_array($this->estado, [
            self::ESTADO_CONFIRMADO,
            self::ESTADO_CANCELADO,
            self::ESTADO_RECHAZADO,
            self::ESTADO_VENCIDO,
        ], true)) {
            return false;
        }

        return $this->finDateTime()->isPast();
    }
}
