<?php

namespace App\Models;

use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class User extends Authenticatable implements FilamentUser
{
    use HasFactory, Notifiable, LogsActivity;

    protected $fillable = [
        'name',
        'apellido',
        'email',
        'password',
        'role',
        'activo',
        'carrera_activa_id',
        'profile_photo_path',
        'google_id',
        'google_avatar_url',
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
            'activo' => 'boolean',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('usuarios')
            ->logOnly([
                'name',
                'apellido',
                'email',
                'password',
                'role',
                'activo',
                'carrera_activa_id',
                'profile_photo_path',
                'google_id',
                'google_avatar_url',
            ])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

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

    public function canAccessPanel(Panel $panel): bool
    {
        if (! $this->activo) {
            return false;
        }

        return match ($panel->getId()) {
            'admin'    => $this->isAdmin(),
            'profesor' => $this->isProfesor(),
            'alumno'   => $this->isAlumno(),
            default    => false,
        };
    }

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

    public function calificacionesHechas()
    {
        return $this->hasMany(CalificacionProfesor::class, 'alumno_id', 'id');
    }

    public function calificacionesRecibidasComoProfesor()
    {
        return $this->hasMany(CalificacionProfesor::class, 'profesor_id', 'id');
    }

    public function carrerasComoAlumno()
    {
        return $this->belongsToMany(
            Carrera::class,
            'alumno_carreras',
            'alumno_id',
            'carrera_id',
            'id',
            'carrera_id'
        )->withTimestamps();
    }

    public function carreraActiva()
    {
        return $this->belongsTo(Carrera::class, 'carrera_activa_id', 'carrera_id');
    }

    public function profesorProfile()
    {
        return $this->hasOne(\App\Models\ProfesorProfile::class, 'user_id', 'id');
    }
}