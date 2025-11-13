<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Materia extends Model
{
    protected $table = 'materias';
    protected $primaryKey = 'materia_id';
    public $incrementing = true;
    protected $keyType = 'int';

    protected $fillable = [
        'materia_nombre',
        'materia_descripcion',
        'materia_anio',
    ];

    // 🔹 Relación con carreras
    public function carreras()
    {
        return $this->belongsToMany(Carrera::class, 'carrera_materias', 'carreramateria_materia_id', 'carreramateria_carrera_id')
                    ->withTimestamps();
    }

    // 🔹 Relación muchos a muchos con temas
    public function temas()
    {
        return $this->belongsToMany(Tema::class, 'materia_tema', 'materia_id', 'tema_id')
                    ->withTimestamps();
    }
}
