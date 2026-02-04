<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Pago extends Model
{
    protected $table = 'pagos';
    protected $primaryKey = 'pago_id';
    public $incrementing = true;
    protected $keyType = 'int';

    public const ESTADO_PENDIENTE = 'pendiente';
    public const ESTADO_APROBADO  = 'aprobado';
    public const ESTADO_RECHAZADO = 'rechazado';
    public const ESTADO_ERROR     = 'error';

    protected $fillable = [
        'turno_id',
        'monto',
        'moneda',
        'estado',
        'provider',
        'mp_preference_id',
        'mp_init_point',
        'mp_payment_id',
        'mp_status',
        'mp_status_detail',
        'mp_payment_type',
        'mp_payment_method',
        'external_reference',
        'detalle_externo',
        'fecha_aprobado',
    ];

    protected $casts = [
        'detalle_externo' => 'array',
        'monto' => 'decimal:2',
        'fecha_aprobado' => 'datetime',
    ];

    public function turno()
    {
        return $this->belongsTo(Turno::class, 'turno_id', 'id');
    }

    public function estaAprobado(): bool
    {
        return $this->estado === self::ESTADO_APROBADO;
    }
}
