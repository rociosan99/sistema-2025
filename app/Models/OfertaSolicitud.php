<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OfertaSolicitud extends Model
{
    protected $table = 'ofertas_solicitud';

    public const ESTADO_PENDIENTE = 'pendiente';
    public const ESTADO_ACEPTADA  = 'aceptada';
    public const ESTADO_RECHAZADA = 'rechazada';
    public const ESTADO_EXPIRADA  = 'expirada';

    protected $fillable = [
        'solicitud_id',
        'profesor_id',

        // ✅ tramo ofrecido (match parcial)
        'hora_inicio',
        'hora_fin',

        'estado',
        'expires_at',
    ];

    protected $casts = [
        'expires_at' => 'datetime',

        // opcional: te quedan como string, que es lo más seguro con time
        'hora_inicio' => 'string',
        'hora_fin'    => 'string',
    ];

    public function solicitud()
    {
        return $this->belongsTo(SolicitudDisponibilidad::class, 'solicitud_id', 'id');
    }

    public function profesor()
    {
        return $this->belongsTo(User::class, 'profesor_id', 'id');
    }
}
