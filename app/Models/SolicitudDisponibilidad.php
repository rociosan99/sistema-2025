<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SolicitudDisponibilidad extends Model
{
    protected $table = 'solicitudes_disponibilidad';

    public const ESTADO_ACTIVA    = 'activa';
    public const ESTADO_TOMADA    = 'tomada';
    public const ESTADO_CANCELADA = 'cancelada';
    public const ESTADO_EXPIRADA  = 'expirada';

    protected $fillable = [
        'alumno_id',
        'materia_id',
        'tema_id',
        'fecha',
        'hora_inicio',
        'hora_fin',
        'estado',
        'expires_at',
    ];

    protected $casts = [
        'fecha' => 'date',
        'expires_at' => 'datetime',
    ];

    public function alumno()
    {
        return $this->belongsTo(User::class, 'alumno_id', 'id');
    }

    public function materia()
    {
        return $this->belongsTo(Materia::class, 'materia_id', 'materia_id');
    }

    public function tema()
    {
        return $this->belongsTo(Tema::class, 'tema_id', 'tema_id');
    }

    public function ofertas()
    {
        return $this->hasMany(OfertaSolicitud::class, 'solicitud_id', 'id');
    }
}
