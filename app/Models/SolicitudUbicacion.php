<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class SolicitudUbicacion extends Model
{
    public const TIPO_PAIS = 'pais';
    public const TIPO_PROVINCIA = 'provincia';
    public const TIPO_CIUDAD = 'ciudad';

    public const ESTADO_PENDIENTE = 'pendiente';
    public const ESTADO_APROBADA = 'aprobada';
    public const ESTADO_RECHAZADA = 'rechazada';

    protected $table = 'solicitudes_ubicacion';

    protected $fillable = [
        'tipo',
        'estado',
        'pais_id',
        'provincia_id',
        'nombre_pais_solicitado',
        'nombre_provincia_solicitada',
        'nombre_ciudad_solicitada',
        'observacion_solicitante',
        'observacion_admin',
        'solicitado_por_id',
        'revisado_por_id',
        'pais_creado_id',
        'provincia_creada_id',
        'ciudad_creada_id',
        'revisado_at',
    ];

    protected static function booted(): void
    {
        // Intercepta la solicitud antes de que se guarde en la base de datos
        static::creating(function (SolicitudUbicacion $solicitud) {
            if ($solicitud->nombre_pais_solicitado) {
                $solicitud->nombre_pais_solicitado = mb_convert_case(trim((string) $solicitud->nombre_pais_solicitado), MB_CASE_TITLE, "UTF-8");
            }
            if ($solicitud->nombre_provincia_solicitada) {
                $solicitud->nombre_provincia_solicitada = mb_convert_case(trim((string) $solicitud->nombre_provincia_solicitada), MB_CASE_TITLE, "UTF-8");
            }
            if ($solicitud->nombre_ciudad_solicitada) {
                $solicitud->nombre_ciudad_solicitada = mb_convert_case(trim((string) $solicitud->nombre_ciudad_solicitada), MB_CASE_TITLE, "UTF-8");
            }
        });
    }

    protected function casts(): array
    {
        return [
            'revisado_at' => 'datetime',
        ];
    }

    public function solicitante()
    {
        return $this->belongsTo(User::class, 'solicitado_por_id', 'id');
    }

    public function revisor()
    {
        return $this->belongsTo(User::class, 'revisado_por_id', 'id');
    }

    public function pais()
    {
        return $this->belongsTo(Pais::class, 'pais_id', 'pais_id');
    }

    public function provincia()
    {
        return $this->belongsTo(Provincia::class, 'provincia_id', 'provincia_id');
    }

    public function paisCreado()
    {
        return $this->belongsTo(Pais::class, 'pais_creado_id', 'pais_id');
    }

    public function provinciaCreada()
    {
        return $this->belongsTo(Provincia::class, 'provincia_creada_id', 'provincia_id');
    }

    public function ciudadCreada()
    {
        return $this->belongsTo(Ciudad::class, 'ciudad_creada_id', 'ciudad_id');
    }

    public function getNombreSolicitadoAttribute(): string
    {
        return match ($this->tipo) {
            self::TIPO_PAIS => (string) $this->nombre_pais_solicitado,
            self::TIPO_PROVINCIA => (string) $this->nombre_provincia_solicitada,
            self::TIPO_CIUDAD => (string) $this->nombre_ciudad_solicitada,
            default => '',
        };
    }
}