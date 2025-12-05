<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MateriaTema extends Model
{
    protected $table = 'materia_tema';

    protected $fillable = [
        'materia_id',
        'tema_id',
    ];
}
