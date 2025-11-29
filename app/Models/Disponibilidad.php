<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Disponibilidad extends Model
{
    use HasFactory;

    protected $table = 'disponibilidades';

    protected $fillable = [
        'profesor_id',
        'dia_semana',
        'hora_inicio',
        'hora_fin',
    ];

    /**
     * Profesor dueño de esta disponibilidad.
     */
    public function profesor()
    {
        return $this->belongsTo(User::class, 'profesor_id', 'id');
    }

    /**
     * Helper para mostrar el día como texto.
     */
    public function getDiaSemanaLabelAttribute(): string
    {
        return match ($this->dia_semana) {
            1 => 'Lunes',
            2 => 'Martes',
            3 => 'Miércoles',
            4 => 'Jueves',
            5 => 'Viernes',
            6 => 'Sábado',
            7 => 'Domingo',
            default => 'Desconocido',
        };
    }
}
