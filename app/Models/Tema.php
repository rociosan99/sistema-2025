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

    // 🔹 Etiquetas jerárquicas con colores
    public static function flattenTreeWithIndent(): array
    {
        $tree = self::getTree();
        $result = [];

        $walk = function ($nodes, $level = 0) use (&$walk, &$result) {
            foreach ($nodes as $node) {
                $indent = str_repeat('&nbsp;&nbsp;&nbsp;', $level);

                if ($level === 0) {
                    $color = '#005bbb';
                    $style = "color: {$color}; font-weight: bold; font-size: 1.05rem;";
                } elseif ($level === 1) {
                    $color = '#00994d';
                    $style = "color: {$color};";
                } else {
                    $color = '#66cc99';
                    $style = "color: {$color};";
                }

                $label = "<span style=\"{$style}\">{$indent}{$node->tema_nombre}</span>";

                $result[$node->tema_id] = $label;

                if ($node->childrenRecursive->isNotEmpty()) {
                    $walk($node->childrenRecursive, $level + 1);
                }
            }
        };

        $walk($tree);

        return $result;
    }

    /**
     * 🔹 Devuelve todos los descendientes (hijos, nietos, etc.) de un tema
     */
    public static function getDescendantIds(int $temaId): array
    {
        $tema = self::with('childrenRecursive')->find($temaId);

        if (! $tema) {
            return [];
        }

        $ids = [];

        $walk = function ($node) use (&$ids, &$walk) {
            foreach ($node->childrenRecursive as $child) {
                $ids[] = $child->tema_id;
                $walk($child);
            }
        };

        $walk($tema);

        return $ids;
    }
}
