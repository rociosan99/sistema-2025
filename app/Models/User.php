<?php

namespace App\Models;

use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable implements FilamentUser
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'apellido',
        'email',
        'password',
        'role', // admin | profesor | alumno
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

    // Helpers de rol (en base a admin/profesor/alumno)
    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function isProfesor(): bool
    {
        return $this->role === 'profesor';
    }

    public function isAlumno(): bool
    {
        return $this->role === 'alumno';
    }

    /**
     * ✅ Filament: autorización por panel.
     * Admin → panel admin
     * Profesor → panel profesor
     * Alumno → panel alumno
     */
    public function canAccessPanel(Panel $panel): bool
    {
        return match ($panel->getId()) {
            'admin'    => $this->isAdmin(),
            'profesor' => $this->isProfesor(),
            'alumno'   => $this->isAlumno(),
            default    => false,
        };
    }

    /* ==========================
       RELACIONES
       ========================== */

    public function materias()
    {
        return $this->belongsToMany(
            Materia::class,
            'profesor_materia',
            'profesor_id',
            'materia_id',
            'id',
            'materia_id'
        )->withPivot('precio_por_hora');
    }

    public function getPrecioPorHoraParaMateria(int $materiaId): ?float
    {
        $materia = $this->materias()->where('materia_id', $materiaId)->first();
        return $materia?->pivot?->precio_por_hora;
    }

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

    public function disponibilidades()
    {
        return $this->hasMany(Disponibilidad::class, 'profesor_id', 'id');
    }

    public function turnosComoAlumno()
    {
        return $this->hasMany(Turno::class, 'alumno_id', 'id');
    }

    public function turnosComoProfesor()
    {
        return $this->hasMany(Turno::class, 'profesor_id', 'id');
    }

    /**
     * ✅ Calificaciones que HIZO el usuario (cuando es alumno).
     */
    public function calificacionesHechas()
    {
        return $this->hasMany(CalificacionProfesor::class, 'alumno_id', 'id');
    }

    /**
     * ✅ Calificaciones que RECIBE el usuario (cuando es profesor).
     */
    public function calificacionesRecibidasComoProfesor()
    {
        return $this->hasMany(CalificacionProfesor::class, 'profesor_id', 'id');
    }
}
