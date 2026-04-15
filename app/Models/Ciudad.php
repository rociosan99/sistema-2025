<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Ciudad extends Model
{
    protected $table = 'ciudades';
    protected $primaryKey = 'ciudad_id';

    protected $fillable = [
        'provincia_id',
        'ciudad_nombre',
    ];

    public function provincia()
    {
        return $this->belongsTo(Provincia::class, 'provincia_id', 'provincia_id');
    }

    public function profesorProfiles()
    {
        return $this->hasMany(ProfesorProfile::class, 'ciudad_id', 'ciudad_id');
    }
}