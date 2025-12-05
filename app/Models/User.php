<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;


class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'apellido',
        'email',
        'password',
        'role',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    // Roles
    public function isAdministrador(): bool
    {
        return $this->role === 'administrador';
    }

    public function isProfesor(): bool
    {
        return $this->role === 'profesor';
    }

    public function isAlumno(): bool
    {
        return $this->role === 'alumno';
    }

    // RELACIÓN MATERIAS — CORREGIDA
    public function materias()
    {
        return $this->belongsToMany(
            Materia::class,
            'profesor_materia',
            'profesor_id',
            'materia_id',
            'id',
            'materia_id'
        );
    }

    // RELACIÓN TEMAS — CORREGIDA
    public function temas()
    {
        return $this->belongsToMany(
            Tema::class,
            'profesor_tema',
            'profesor_id',
            'tema_id',
            'id',
            'tema_id'
        );
    }

        /**
     * Disponibilidades semanales del profesor.
     */
    public function disponibilidades()
    {
        return $this->hasMany(Disponibilidad::class, 'profesor_id', 'id');
    }

        /**
     * Turnos donde el usuario es ALUMNO.
     */
    public function turnosComoAlumno()
    {
        return $this->hasMany(Turno::class, 'alumno_id', 'id');
    }

    /**
     * Turnos donde el usuario es PROFESOR.
     */
    public function turnosComoProfesor()
    {
        return $this->hasMany(Turno::class, 'profesor_id', 'id');
    }


}
