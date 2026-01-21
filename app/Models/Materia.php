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
     * ✅ Temas de la materia (a través de los programas) - SOLO LECTURA / CONSULTA
     * ⚠️ NO sirve para sync()
     */
    public function temas()
    {
        return $this->hasManyThrough(
            Tema::class,
            Programa::class,
            'programa_materia_id', // FK en Programas → Materia
            'tema_id',             // PK/FK en Temas (ojo: esto depende de tu estructura real)
            'materia_id',          // PK Materia
            'programa_id'          // PK Programa
        )->distinct();
    }

    /**
     * ✅ Temas asignados DIRECTAMENTE a la materia (tabla pivot materia_tema)
     * ✅ ESTA es la relación que soporta sync()
     */
    public function temasPivot()
    {
        return $this->belongsToMany(
            Tema::class,
            'materia_tema',
            'materia_id',  // FK en pivot → materias
            'tema_id',     // FK en pivot → temas
            'materia_id',  // PK materias
            'tema_id'      // PK temas
        )->withTimestamps();
    }

    /**
     * Profesores que dictan esta materia (con precio por clase en el pivot).
     */
    public function profesores()
    {
        return $this->belongsToMany(
            User::class,
            'profesor_materia',
            'materia_id',
            'profesor_id',
            'materia_id',
            'id'
        )->withPivot('precio_por_clase');
    }
}
