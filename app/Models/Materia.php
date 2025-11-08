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

    public function carreras()
    {
        return $this->belongsToMany(Carrera::class, 'carrera_materias', 'carreramateria_materia_id', 'carreramateria_carrera_id')
                    ->withTimestamps();
    }

}

