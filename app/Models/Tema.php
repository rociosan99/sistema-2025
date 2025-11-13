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

    public function materias()
    {
        return $this->belongsToMany(Materia::class, 'materia_tema', 'tema_id', 'materia_id')
                    ->withTimestamps();
    }

    public function parent()
    {
        return $this->belongsTo(self::class, 'tema_id_tema_padre', 'tema_id');
    }

    public function children()
    {
        return $this->hasMany(self::class, 'tema_id_tema_padre', 'tema_id');
    }

    public function childrenRecursive()
    {
        return $this->children()->with('childrenRecursive');
    }

    public static function getTree()
    {
        return self::whereNull('tema_id_tema_padre')
            ->with('childrenRecursive')
            ->get();
    }

    /**
     * Genera lista jerárquica con colores y HTML aplicado
     */
    public static function flattenTreeWithIndent(): array
    {
        $tree = self::getTree();
        $result = [];

        $walk = function ($nodes, $level = 0) use (&$walk, &$result) {
            foreach ($nodes as $node) {

                // INDENTACIÓN
                $indent = str_repeat('— ', $level);

                // 🎨 COLORES POR NIVEL
                if ($level === 0) {
                    // PADRE — azul fuerte + negrita
                    $label = "<span style='color:#005bbb; font-weight:bold; font-size:1.05rem;'>{$node->tema_nombre}</span>";
                } elseif ($level === 1) {
                    // HIJO — verde
                    $label = "<span style='color:#00994d;'>{$indent}{$node->tema_nombre}</span>";
                } else {
                    // NIETO — verde claro
                    $label = "<span style='color:#66cc99;'>{$indent}{$node->tema_nombre}</span>";
                }

                $result[$node->tema_id] = $label;

                if ($node->childrenRecursive->isNotEmpty()) {
                    $walk($node->childrenRecursive, $level + 1);
                }
            }
        };

        $walk($tree);

        return $result;
    }
}
