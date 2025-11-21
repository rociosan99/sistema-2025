<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Programa extends Model
{
    protected $table = 'programas';
    protected $primaryKey = 'programa_id';

    protected $fillable = [
        'programa_plan_id',
        'programa_materia_id',
        'programa_anio',
        'programa_descripcion',
    ];

    // 🔹 Pertenece a un Plan de Estudio
    public function plan()
    {
        return $this->belongsTo(PlanEstudio::class, 'programa_plan_id', 'plan_id');
    }

    // 🔹 Pertenece a una Materia
    public function materia()
    {
        return $this->belongsTo(Materia::class, 'programa_materia_id', 'materia_id');
    }

    // 🔹 Muchos a muchos con Temas
    public function temas()
    {
        return $this->belongsToMany(Tema::class, 'programa_tema', 'programa_id', 'tema_id')
                    ->withTimestamps();
    }
}
