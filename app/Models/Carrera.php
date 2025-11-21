<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Carrera extends Model
{
    protected $table = 'carreras';
    protected $primaryKey = 'carrera_id';

    protected $fillable = [
        'carrera_nombre',
        'carrera_descripcion',
        'carrera_institucion_id',
    ];

    // RELACIÓN: Una carrera pertenece a una institución
    public function institucion()
    {
        return $this->belongsTo(Institucion::class, 'carrera_institucion_id', 'institucion_id');
    }

    // RELACIÓN: Una carrera tiene muchos planes de estudio
    public function planesEstudio(): HasMany
    {
        return $this->hasMany(PlanEstudio::class, 'plan_carrera_id', 'carrera_id');
    }

   
}
