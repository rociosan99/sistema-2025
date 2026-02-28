<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProfesorProfile extends Model
{
    protected $table = 'profesor_profiles';

    protected $fillable = [
        'user_id',
        'ciudad',
        'bio',
        'experiencia_anios',
        'nivel',
        'precio_por_hora_default',
        'titulo_profesional',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}