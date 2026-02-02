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
    public const ESTADO_PENDIENTE = 'pendiente';
    public const ESTADO_ACEPTADO = 'aceptado';
    public const ESTADO_RECHAZADO = 'rechazado';
    public const ESTADO_PENDIENTE_PAGO = 'pendiente_pago';
    public const ESTADO_CONFIRMADO = 'confirmado'; // pago OK
    public const ESTADO_CANCELADO = 'cancelado';
    public const ESTADO_VENCIDO = 'vencido';

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
    ];

    protected $casts = [
        'fecha' => 'date',
        'hora_inicio' => 'string',
        'hora_fin' => 'string',
        'precio_por_hora' => 'decimal:2',
        'precio_total' => 'decimal:2',
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
        $inicio = substr((string) $this->hora_inicio, 0, 5);
        $fin    = substr((string) $this->hora_fin, 0, 5);

        return "{$inicio} - {$fin}";
    }

    public function estaPendiente(): bool
    {
        return $this->estado === self::ESTADO_PENDIENTE;
    }

    public function estaAceptado(): bool
    {
        return $this->estado === self::ESTADO_ACEPTADO;
    }

    public function estaPendientePago(): bool
    {
        return $this->estado === self::ESTADO_PENDIENTE_PAGO;
    }

    public function estaConfirmado(): bool
    {
        return $this->estado === self::ESTADO_CONFIRMADO;
    }

    public function estaRechazado(): bool
    {
        return $this->estado === self::ESTADO_RECHAZADO;
    }

    public function estaCancelado(): bool
    {
        return $this->estado === self::ESTADO_CANCELADO;
    }

    /**
     * Devuelve un Carbon con fecha + hora_fin del turno.
     */
    public function finDateTime(): Carbon
    {
        return Carbon::parse($this->fecha->format('Y-m-d') . ' ' . $this->hora_fin);
    }
}
