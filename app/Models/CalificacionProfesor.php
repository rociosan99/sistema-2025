<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CalificacionProfesor extends Model
{
    protected $table = 'calificaciones_profesor';

    protected $fillable = [
        'turno_id',
        'alumno_id',
        'profesor_id',
        'estrellas',
        'comentario',
    ];

    public function turno()
    {
        return $this->belongsTo(Turno::class, 'turno_id', 'id');
    }

    public function alumno()
    {
        return $this->belongsTo(User::class, 'alumno_id', 'id');
    }

    public function profesor()
    {
        return $this->belongsTo(User::class, 'profesor_id', 'id');
    }
}
