<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CalificacionAlumno extends Model
{
    protected $table = 'calificaciones_alumno';

    protected $fillable = [
        'turno_id',
        'profesor_id',
        'alumno_id',
        'estrellas',
        'comentario',
    ];

    protected $casts = [
        'estrellas' => 'integer',
    ];

    public function turno()
    {
        return $this->belongsTo(Turno::class, 'turno_id', 'id');
    }

    public function profesor()
    {
        return $this->belongsTo(User::class, 'profesor_id', 'id');
    }

    public function alumno()
    {
        return $this->belongsTo(User::class, 'alumno_id', 'id');
    }
}
