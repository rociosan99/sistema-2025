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
    use LogsActivity;

    protected $table = 'turnos';

    // Estados
    public const ESTADO_PENDIENTE       = 'pendiente';
    public const ESTADO_ACEPTADO        = 'aceptado'; // legacy
    public const ESTADO_RECHAZADO       = 'rechazado';
    public const ESTADO_PENDIENTE_PAGO  = 'pendiente_pago';
    public const ESTADO_CONFIRMADO      = 'confirmado';
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

        // ✅ NUEVOS (cancelación / reemplazo)
        'cancelado_at',
        'cancelacion_tipo',            // sin_cargo | con_cargo
        'reemplazado_por_turno_id',    // si lo estás usando

        // ✅ si lo tenés en DB (por tus dumps)
        'recordatorio_24h_enviado_at',

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

        // ✅ NUEVOS
        'cancelado_at' => 'datetime',
        'recordatorio_24h_enviado_at' => 'datetime',

        'asistencia_confirmada_at' => 'datetime',
        'asistencia_cancelada_at' => 'datetime',
    ];

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

                // ✅ NUEVOS
                'cancelado_at',
                'cancelacion_tipo',
                'reemplazado_por_turno_id',
                'recordatorio_24h_enviado_at',

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

    // Relaciones
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

    // Helpers
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
}