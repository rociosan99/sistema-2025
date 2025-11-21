<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PlanEstudio extends Model
{
    protected $table = 'planes_estudio';
    protected $primaryKey = 'plan_id';

    protected $fillable = [
        'plan_carrera_id',
        'plan_anio',
        'plan_descripcion',
    ];

    // Relación: un plan pertenece a una carrera
    public function carrera()
    {
        return $this->belongsTo(Carrera::class, 'plan_carrera_id', 'carrera_id');
    }

     // 🔹 Un plan tiene muchos programas
    public function programas()
    {
        return $this->hasMany(Programa::class, 'programa_plan_id', 'plan_id');
    }
}

