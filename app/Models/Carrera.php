<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Carrera extends Model
{
    protected $table = 'carreras';
    protected $primaryKey = 'carrera_id';
    public $incrementing = true;
    protected $keyType = 'int';

    protected $fillable = [
        'carrera_institucion_id',
        'carrera_nombre',
        'carrera_descripcion',
    ];

    // Cada carrera pertenece a una institución
    public function institucion()
    {
        return $this->belongsTo(Institucion::class, 'carrera_institucion_id', 'institucion_id');
    }

    public function materias()
    {
        return $this->belongsToMany(Materia::class, 'carrera_materias', 'carreramateria_carrera_id', 'carreramateria_materia_id')
                    ->withTimestamps();
    }

}
