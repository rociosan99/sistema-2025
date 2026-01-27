<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Turno extends Model
{
    use HasFactory;

    protected $table = 'turnos';

    // Estados (para no “hardcodear” strings en todos lados)
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
        // time en DB => mejor string (evita Carbon con fecha inventada)
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

    /* =========================
     * Accessors / Helpers
     * ========================= */

    public function getFechaFormateadaAttribute(): string
    {
        return Carbon::parse($this->fecha)->format('d/m/Y');
    }

    public function getHorarioAttribute(): string
    {
        // hora_inicio/hora_fin suelen venir como "HH:MM:SS"
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

    /** Confirmado = pago aprobado */
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

    public function pago()
    {
        return $this->hasOne(\App\Models\Pago::class, 'turno_id', 'id');
    }

}
