<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Tema extends Model
{
    protected $table = 'temas';
    protected $primaryKey = 'tema_id';
    public $incrementing = true;
    protected $keyType = 'int';

    protected $fillable = [
        'tema_nombre',
        'tema_descripcion',
        'tema_id_tema_padre',
    ];

    // Tema padre (belongsTo a sí mismo)
    public function parent()
    {
        return $this->belongsTo(self::class, 'tema_id_tema_padre', 'tema_id');
    }

    // Hijos del tema (hasMany a sí mismo)
    public function children()
    {
        return $this->hasMany(self::class, 'tema_id_tema_padre', 'tema_id');
    }
}
