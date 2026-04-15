<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Pais extends Model
{
    protected $table = 'paises';
    protected $primaryKey = 'pais_id';

    protected $fillable = [
        'pais_nombre',
    ];

    public function provincias()
    {
        return $this->hasMany(Provincia::class, 'pais_id', 'pais_id');
    }
}