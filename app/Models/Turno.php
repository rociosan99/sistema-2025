<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Turno extends Model
{
    use HasFactory;

    protected $table = 'turnos';

    // Estados
    public const ESTADO_PENDIENTE       = 'pendiente';
    public const ESTADO_ACEPTADO        = 'aceptado'; // (legacy) ya no se usa en Flujo A
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

        // legacy (no usado en Flujo A, pero lo dejamos por compatibilidad)
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

    public function estaPendiente(): bool { return $this->estado === self::ESTADO_PENDIENTE; }
    public function estaPendientePago(): bool { return $this->estado === self::ESTADO_PENDIENTE_PAGO; }
    public function estaConfirmado(): bool { return $this->estado === self::ESTADO_CONFIRMADO; }
    public function estaRechazado(): bool { return $this->estado === self::ESTADO_RECHAZADO; }
    public function estaCancelado(): bool { return $this->estado === self::ESTADO_CANCELADO; }
    public function estaVencido(): bool { return $this->estado === self::ESTADO_VENCIDO; }

    /**
     * ✅ Devuelve Carbon con fecha + hora_fin del turno (robusto).
     * Acepta hora_fin como "19:00", "19:00:00" o "2026-01-22 19:00:00"
     */
    public function finDateTime(): Carbon
    {
        $fechaStr = $this->fecha instanceof Carbon
            ? $this->fecha->format('Y-m-d')
            : Carbon::parse($this->fecha)->format('Y-m-d');

        $horaFinStr = (string) ($this->hora_fin ?? '00:00:00');

        // Si viene con fecha completa, nos quedamos con la hora
        if (preg_match('/^\d{4}-\d{2}-\d{2}\s+\d{2}:\d{2}:\d{2}$/', $horaFinStr)) {
            $horaFinStr = Carbon::parse($horaFinStr)->format('H:i:s');
        }

        // Si viene "19:00" -> "19:00:00"
        if (preg_match('/^\d{2}:\d{2}$/', $horaFinStr)) {
            $horaFinStr .= ':00';
        }

        return Carbon::parse("{$fechaStr} {$horaFinStr}");
    }

    /**
     * ✅ Un turno se puede marcar vencido sólo si:
     * - ya pasó el fin del turno
     * - y NO está pagado / cancelado / rechazado
     */
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

    public function calificacionAlumno()
    {
        return $this->hasOne(\App\Models\CalificacionAlumno::class, 'turno_id', 'id');
    }

}
