<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MotivoCalificacion extends Model
{
    public const TIPO_PROFESOR = 'profesor';

    public const TIPO_ALUMNO = 'alumno';

    protected $table = 'motivos_calificacion';

    protected $fillable = [
        'tipo_evaluado',
        'estrellas',
        'descripcion',
        'activo',
        'orden',
    ];

    protected $casts = [
        'estrellas' => 'integer',
        'activo' => 'boolean',
        'orden' => 'integer',
    ];

    public function calificacionesProfesor()
    {
        return $this->belongsToMany(
            CalificacionProfesor::class,
            'calificacion_profesor_motivo',
            'motivo_calificacion_id',
            'calificacion_profesor_id'
        )->withTimestamps();
    }

    public function calificacionesAlumno()
    {
        return $this->belongsToMany(
            CalificacionAlumno::class,
            'calificacion_alumno_motivo',
            'motivo_calificacion_id',
            'calificacion_alumno_id'
        )->withTimestamps();
    }
}
