<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TurnoReemplazo extends Model
{
    protected $table = 'turno_reemplazos';

    // ✅ Estados (canon)
    public const ESTADO_PENDIENTE = 'pendiente';
    public const ESTADO_ACEPTADA  = 'aceptada';
    public const ESTADO_RECHAZADA = 'rechazada';
    public const ESTADO_EXPIRADA  = 'expirada';

    // ✅ Alias para que NO se rompa tu Dashboard (que usa masculino)
    public const ESTADO_ACEPTADO  = self::ESTADO_ACEPTADA;
    public const ESTADO_RECHAZADO = self::ESTADO_RECHAZADA;
    public const ESTADO_EXPIRADO  = self::ESTADO_EXPIRADA;

    protected $fillable = [
        'turno_cancelado_id',
        'profesor_id',
        'alumno_id',

        'materia_id',
        'tema_id',

        'fecha',
        'hora_inicio',
        'hora_fin',

        'estado',
        'expires_at',

        // opcional si lo tenés
        'meta',
    ];

    protected $casts = [
        'fecha' => 'date',
        'hora_inicio' => 'string',
        'hora_fin' => 'string',
        'expires_at' => 'datetime',
        'meta' => 'array',
        'notificado_at' => 'datetime',
    ];

    /* =========================
     * Relaciones (IMPORTANTE)
     * ========================= */

    public function turnoCancelado()
    {
        return $this->belongsTo(Turno::class, 'turno_cancelado_id', 'id');
    }

    public function profesor()
    {
        return $this->belongsTo(User::class, 'profesor_id', 'id');
    }

    public function alumno()
    {
        return $this->belongsTo(User::class, 'alumno_id', 'id');
    }

    // ✅ Si tu Dashboard hace ->with(['materia','tema']) esto evita el error RelationNotFound
    public function materia()
    {
        return $this->belongsTo(Materia::class, 'materia_id', 'materia_id');
    }

    public function tema()
    {
        return $this->belongsTo(Tema::class, 'tema_id', 'tema_id');
    }
}