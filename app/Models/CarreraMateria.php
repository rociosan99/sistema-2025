<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CarreraMateria extends Model
{
    protected $table = 'carrera_materias';
    protected $primaryKey = 'carreramateria_id';
    public $incrementing = true;
    protected $keyType = 'int';

    protected $fillable = [
        'carreramateria_carrera_id',
        'carreramateria_materia_id',
    ];

    // Relación con Carrera
    public function carrera()
    {
        return $this->belongsTo(Carrera::class, 'carreramateria_carrera_id', 'carrera_id');
    }

    // Relación con Materia
    public function materia()
    {
        return $this->belongsTo(Materia::class, 'carreramateria_materia_id', 'materia_id');
    }
}
