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

    /*
    |--------------------------------------------------------------------------
    | RELACIONES
    |--------------------------------------------------------------------------
    */

    // 🔹 Relación muchos a muchos con materias (histórico)
    public function materias()
    {
        return $this->belongsToMany(Materia::class, 'materia_tema', 'tema_id', 'materia_id')
                    ->withTimestamps();
    }

    // 🔹 Relación muchos a muchos con programas (ACTUAL)
    public function programas()
    {
        return $this->belongsToMany(Programa::class, 'programa_tema', 'tema_id', 'programa_id')
                    ->withTimestamps();
    }

    // 🔹 Tema padre
    public function parent()
    {
        return $this->belongsTo(self::class, 'tema_id_tema_padre', 'tema_id');
    }

    // 🔹 Hijos directos
    public function children()
    {
        return $this->hasMany(self::class, 'tema_id_tema_padre', 'tema_id');
    }

    // 🔹 Carga recursiva de hijos (subniveles infinitos)
    public function childrenRecursive()
    {
        return $this->children()->with('childrenRecursive');
    }

    /*
    |--------------------------------------------------------------------------
    | OBTENER ÁRBOL COMPLETO
    |--------------------------------------------------------------------------
    */
    public static function getTree()
    {
        return self::whereNull('tema_id_tema_padre')
            ->with('childrenRecursive')
            ->get();
    }

    /*
    |--------------------------------------------------------------------------
    | LISTA APLANADA CON INDENTACIÓN Y COLORES
    |--------------------------------------------------------------------------
    | Esta función ya la venías usando en materias/programas.
    | La dejamos igual, solo optimizada.
    |--------------------------------------------------------------------------
    */
    public static function flattenTreeWithIndent(): array
    {
        $tree = self::getTree();
        $result = [];

        $walk = function ($nodes, $level = 0) use (&$walk, &$result) {
            foreach ($nodes as $node) {

                // Espaciado según nivel
                $indent = str_repeat('&nbsp;&nbsp;&nbsp;', $level);

                // 🎨 COLORES
                if ($level === 0) {
                    // Tema padre
                    $style = "color:#005bbb; font-weight:bold; font-size:1.05rem;";
                } elseif ($level === 1) {
                    // Hijo nivel 1
                    $style = "color:#00994d;";
                } else {
                    // Hijo nivel 2+
                    $style = "color:#66cc99;";
                }

                // Etiqueta HTML del tema
                $label = "<span style=\"$style\">{$indent}{$node->tema_nombre}</span>";

                // Agregar a la lista final
                $result[$node->tema_id] = $label;

                // Recorrer hijos
                if ($node->childrenRecursive->isNotEmpty()) {
                    $walk($node->childrenRecursive, $level + 1);
                }
            }
        };

        $walk($tree);

        return $result;
    }

    /*
    |--------------------------------------------------------------------------
    | OBTENER TODOS LOS DESCENDIENTES (para marcar hijos automáticamente)
    |--------------------------------------------------------------------------
    */
    public static function getDescendantIds(int $temaId): array
    {
        $tema = self::with('childrenRecursive')->find($temaId);

        if (!$tema) {
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

