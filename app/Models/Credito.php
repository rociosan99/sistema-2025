<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Credito extends Model
{
    public const ESTADO_ESPERANDO_PAGO = 'esperando_pago';

    public const ESTADO_DISPONIBLE = 'disponible';

    public const ESTADO_NO_APLICA = 'no_aplica';

    protected $table = 'creditos';

    protected $fillable = [
        'alumno_id',
        'turno_id',
        'pago_id',
        'importe_pagado',
        'importe_credito',
        'importe_penalizacion',
        'importe_penalizacion_profesor',
        'importe_penalizacion_plataforma',
        'saldo_disponible',
        'porcentaje_credito_aplicado',
        'porcentaje_penalizacion_aplicado',
        'porcentaje_profesor_penalizacion_aplicado',
        'porcentaje_plataforma_penalizacion_aplicado',
        'horas_limite_aplicadas',
        'vigencia_dias_aplicada',
        'estado',
        'idempotency_key',
        'cancelado_at',
        'vence_at',
    ];

    protected $casts = [
        'importe_pagado' => 'decimal:2',
        'importe_credito' => 'decimal:2',
        'importe_penalizacion' => 'decimal:2',
        'importe_penalizacion_profesor' => 'decimal:2',
        'importe_penalizacion_plataforma' => 'decimal:2',
        'saldo_disponible' => 'decimal:2',
        'porcentaje_credito_aplicado' => 'decimal:2',
        'porcentaje_penalizacion_aplicado' => 'decimal:2',
        'porcentaje_profesor_penalizacion_aplicado' => 'decimal:2',
        'porcentaje_plataforma_penalizacion_aplicado' => 'decimal:2',
        'horas_limite_aplicadas' => 'integer',
        'vigencia_dias_aplicada' => 'integer',
        'cancelado_at' => 'datetime',
        'vence_at' => 'datetime',
    ];

    public function alumno(): BelongsTo
    {
        return $this->belongsTo(User::class, 'alumno_id', 'id');
    }

    public function turno(): BelongsTo
    {
        return $this->belongsTo(Turno::class, 'turno_id', 'id');
    }

    public function pago(): BelongsTo
    {
        return $this->belongsTo(Pago::class, 'pago_id', 'pago_id');
    }

    public function aplicaciones(): HasMany
    {
        return $this->hasMany(CreditoAplicacion::class, 'credito_id', 'id');
    }
}
