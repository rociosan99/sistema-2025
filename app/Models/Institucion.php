<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Institucion extends Model
{
    protected $table = 'instituciones';
    protected $primaryKey = 'institucion_id';
    public $incrementing = true;
    protected $keyType = 'int';

    protected $fillable = [
        'institucion_nombre',
        'institucion_descripcion',
    ];
}