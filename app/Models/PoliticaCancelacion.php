<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PoliticaCancelacion extends Model
{
    public const CODIGO_CANCELACION_ALUMNO = 'cancelacion_alumno';

    protected $table = 'politicas_cancelacion';

    protected $fillable = [
        'codigo',
        'horas_cancelacion_sin_penalizacion',
        'porcentaje_credito_anticipado',
        'porcentaje_credito_tardio',
        'porcentaje_penalizacion_tardia',
    ];

    protected $casts = [
        'horas_cancelacion_sin_penalizacion' => 'integer',
        'porcentaje_credito_anticipado' => 'decimal:2',
        'porcentaje_credito_tardio' => 'decimal:2',
        'porcentaje_penalizacion_tardia' => 'decimal:2',
    ];
}
