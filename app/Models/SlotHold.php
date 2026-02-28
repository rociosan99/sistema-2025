<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SlotHold extends Model
{
    protected $table = 'slot_holds';

    public const ESTADO_ACTIVO    = 'activo';
    public const ESTADO_CONSUMIDO = 'consumido';
    public const ESTADO_EXPIRADO  = 'expirado';

    protected $fillable = [
        'profesor_id',
        'fecha',
        'hora_inicio',
        'hora_fin',
        'motivo',
        'estado',
        'expires_at',
        'meta',
    ];

    protected $casts = [
        'fecha' => 'date',
        'expires_at' => 'datetime',
        'meta' => 'array',
    ];

    public function profesor()
    {
        return $this->belongsTo(User::class, 'profesor_id');
    }
}
