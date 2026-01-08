<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Turno extends Model
{
    use HasFactory;

    protected $table = 'turnos';

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

    /**
     * Casts automáticos
     */
    protected $casts = [
        'fecha' => 'date',
        'hora_inicio' => 'datetime:H:i',
        'hora_fin' => 'datetime:H:i',
        'precio_por_hora' => 'decimal:2',
        'precio_total' => 'decimal:2',
    ];

    /* =========================
     * Relaciones
     * ========================= */

    /**
     * Alumno que solicitó el turno
     */
    public function alumno()
    {
        return $this->belongsTo(User::class, 'alumno_id');
    }

    /**
     * Profesor del turno
     */
    public function profesor()
    {
        return $this->belongsTo(User::class, 'profesor_id');
    }

    /**
     * Materia del turno
     */
    public function materia()
    {
        return $this->belongsTo(Materia::class, 'materia_id', 'materia_id');
    }

    /**
     * Tema del turno
     */
    public function tema()
    {
        return $this->belongsTo(Tema::class, 'tema_id', 'tema_id');
    }

    /* =========================
     * Helpers (muy útiles)
     * ========================= */

    /**
     * Fecha formateada (para mails y vistas)
     */
    public function getFechaFormateadaAttribute(): string
    {
        return Carbon::parse($this->fecha)->format('d/m/Y');
    }

    /**
     * Horario formateado
     */
    public function getHorarioAttribute(): string
    {
        return substr($this->hora_inicio, 0, 5) . ' - ' . substr($this->hora_fin, 0, 5);
    }

    /**
     * Saber si está pendiente
     */
    public function estaPendiente(): bool
    {
        return $this->estado === 'pendiente';
    }

    /**
     * Saber si está confirmado
     */
    public function estaConfirmado(): bool
    {
        return $this->estado === 'confirmado';
    }

    /**
     * Saber si está rechazado
     */
    public function estaRechazado(): bool
    {
        return $this->estado === 'rechazado';
    }
}
