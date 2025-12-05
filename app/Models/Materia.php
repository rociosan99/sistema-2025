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

    /**
     * Una materia tiene varios programas
     */
    public function programas()
    {
        return $this->hasMany(Programa::class, 'programa_materia_id', 'materia_id');
    }

    /**
     * Temas de la materia (a través de los programas)
     */
    public function temas()
    {
        return $this->hasManyThrough(
            Tema::class,
            Programa::class,
            'programa_materia_id', // FK en Programas → Materia
            'tema_id',             // FK en Temas → Tema
            'materia_id',          // PK Materia
            'programa_id'          // PK Programa
        )->distinct(); 
    }
}
