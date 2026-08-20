<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CreditoAplicacion extends Model
{
    public const ESTADO_RESERVADO = 'reservado';

    public const ESTADO_APLICADO = 'aplicado';

    public const ESTADO_LIBERADO = 'liberado';

    protected $table = 'credito_aplicaciones';

    protected $fillable = [
        'credito_id',
        'turno_id',
        'importe',
        'estado',
        'idempotency_key',
    ];

    protected $casts = [
        'importe' => 'decimal:2',
    ];

    public function credito(): BelongsTo
    {
        return $this->belongsTo(Credito::class, 'credito_id', 'id');
    }

    public function turno(): BelongsTo
    {
        return $this->belongsTo(Turno::class, 'turno_id', 'id');
    }
}
