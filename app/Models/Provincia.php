<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Provincia extends Model
{
    protected $table = 'provincias';
    protected $primaryKey = 'provincia_id';

    protected $fillable = [
        'pais_id',
        'provincia_nombre',
    ];

    public function pais()
    {
        return $this->belongsTo(Pais::class, 'pais_id', 'pais_id');
    }

    public function ciudades()
    {
        return $this->hasMany(Ciudad::class, 'provincia_id', 'provincia_id');
    }
}