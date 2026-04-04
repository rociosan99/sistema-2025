<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Quita las acciones ON DELETE CASCADE y ON DELETE SET NULL
     * sin desactivar las foreign keys.
     *
     * Esta versión evita el error errno 121 de MySQL/MariaDB
     * recreando cada constraint en pasos separados.
     */
    public function up(): void
    {
        foreach ($this->foreignKeys() as $foreignKey) {
            $this->replaceDeleteAction($foreignKey, null);
        }
    }

    /**
     * Restaura las acciones ON DELETE originales detectadas en el dump.
     */
    public function down(): void
    {
        foreach ($this->foreignKeys() as $foreignKey) {
            $this->replaceDeleteAction($foreignKey, $foreignKey['original_delete']);
        }
    }

    private function foreignKeys(): array
    {
        return [
            [
                'table' => 'alumno_carreras',
                'name' => 'alumno_carreras_alumno_id_foreign',
                'columns' => ['alumno_id'],
                'referenced_table' => 'users',
                'referenced_columns' => ['id'],
                'original_delete' => 'CASCADE',
            ],
            [
                'table' => 'alumno_carreras',
                'name' => 'alumno_carreras_carrera_id_foreign',
                'columns' => ['carrera_id'],
                'referenced_table' => 'carreras',
                'referenced_columns' => ['carrera_id'],
                'original_delete' => 'CASCADE',
            ],
            [
                'table' => 'calificaciones_alumno',
                'name' => 'calificaciones_alumno_alumno_id_foreign',
                'columns' => ['alumno_id'],
                'referenced_table' => 'users',
                'referenced_columns' => ['id'],
                'original_delete' => 'CASCADE',
            ],
            [
                'table' => 'calificaciones_alumno',
                'name' => 'calificaciones_alumno_profesor_id_foreign',
                'columns' => ['profesor_id'],
                'referenced_table' => 'users',
                'referenced_columns' => ['id'],
                'original_delete' => 'CASCADE',
            ],
            [
                'table' => 'calificaciones_alumno',
                'name' => 'calificaciones_alumno_turno_id_foreign',
                'columns' => ['turno_id'],
                'referenced_table' => 'turnos',
                'referenced_columns' => ['id'],
                'original_delete' => 'CASCADE',
            ],
            [
                'table' => 'calificaciones_profesor',
                'name' => 'calificaciones_profesor_alumno_id_foreign',
                'columns' => ['alumno_id'],
                'referenced_table' => 'users',
                'referenced_columns' => ['id'],
                'original_delete' => 'CASCADE',
            ],
            [
                'table' => 'calificaciones_profesor',
                'name' => 'calificaciones_profesor_profesor_id_foreign',
                'columns' => ['profesor_id'],
                'referenced_table' => 'users',
                'referenced_columns' => ['id'],
                'original_delete' => 'CASCADE',
            ],
            [
                'table' => 'calificaciones_profesor',
                'name' => 'calificaciones_profesor_turno_id_foreign',
                'columns' => ['turno_id'],
                'referenced_table' => 'turnos',
                'referenced_columns' => ['id'],
                'original_delete' => 'CASCADE',
            ],
            [
                'table' => 'carreras',
                'name' => 'carreras_carrera_institucion_id_foreign',
                'columns' => ['carrera_institucion_id'],
                'referenced_table' => 'instituciones',
                'referenced_columns' => ['institucion_id'],
                'original_delete' => 'CASCADE',
            ],
            [
                'table' => 'carrera_materias',
                'name' => 'carrera_materias_carreramateria_carrera_id_foreign',
                'columns' => ['carreramateria_carrera_id'],
                'referenced_table' => 'carreras',
                'referenced_columns' => ['carrera_id'],
                'original_delete' => 'CASCADE',
            ],
            [
                'table' => 'carrera_materias',
                'name' => 'carrera_materias_carreramateria_materia_id_foreign',
                'columns' => ['carreramateria_materia_id'],
                'referenced_table' => 'materias',
                'referenced_columns' => ['materia_id'],
                'original_delete' => 'CASCADE',
            ],
            [
                'table' => 'disponibilidades',
                'name' => 'disponibilidades_profesor_id_foreign',
                'columns' => ['profesor_id'],
                'referenced_table' => 'users',
                'referenced_columns' => ['id'],
                'original_delete' => 'CASCADE',
            ],
            [
                'table' => 'materia_tema',
                'name' => 'materia_tema_materia_id_foreign',
                'columns' => ['materia_id'],
                'referenced_table' => 'materias',
                'referenced_columns' => ['materia_id'],
                'original_delete' => 'CASCADE',
            ],
            [
                'table' => 'materia_tema',
                'name' => 'materia_tema_tema_id_foreign',
                'columns' => ['tema_id'],
                'referenced_table' => 'temas',
                'referenced_columns' => ['tema_id'],
                'original_delete' => 'CASCADE',
            ],
            [
                'table' => 'ofertas_solicitud',
                'name' => 'ofertas_solicitud_profesor_id_foreign',
                'columns' => ['profesor_id'],
                'referenced_table' => 'users',
                'referenced_columns' => ['id'],
                'original_delete' => 'CASCADE',
            ],
            [
                'table' => 'ofertas_solicitud',
                'name' => 'ofertas_solicitud_solicitud_id_foreign',
                'columns' => ['solicitud_id'],
                'referenced_table' => 'solicitudes_disponibilidad',
                'referenced_columns' => ['id'],
                'original_delete' => 'CASCADE',
            ],
            [
                'table' => 'pagos',
                'name' => 'pagos_turno_id_foreign',
                'columns' => ['turno_id'],
                'referenced_table' => 'turnos',
                'referenced_columns' => ['id'],
                'original_delete' => 'CASCADE',
            ],
            [
                'table' => 'planes_estudio',
                'name' => 'planes_estudio_plan_carrera_id_foreign',
                'columns' => ['plan_carrera_id'],
                'referenced_table' => 'carreras',
                'referenced_columns' => ['carrera_id'],
                'original_delete' => 'CASCADE',
            ],
            [
                'table' => 'profesor_materia',
                'name' => 'profesor_materia_materia_id_foreign',
                'columns' => ['materia_id'],
                'referenced_table' => 'materias',
                'referenced_columns' => ['materia_id'],
                'original_delete' => 'CASCADE',
            ],
            [
                'table' => 'profesor_materia',
                'name' => 'profesor_materia_profesor_id_foreign',
                'columns' => ['profesor_id'],
                'referenced_table' => 'users',
                'referenced_columns' => ['id'],
                'original_delete' => 'CASCADE',
            ],
            [
                'table' => 'profesor_profiles',
                'name' => 'profesor_profiles_user_id_foreign',
                'columns' => ['user_id'],
                'referenced_table' => 'users',
                'referenced_columns' => ['id'],
                'original_delete' => 'CASCADE',
            ],
            [
                'table' => 'profesor_tema',
                'name' => 'profesor_tema_profesor_id_foreign',
                'columns' => ['profesor_id'],
                'referenced_table' => 'users',
                'referenced_columns' => ['id'],
                'original_delete' => 'CASCADE',
            ],
            [
                'table' => 'profesor_tema',
                'name' => 'profesor_tema_tema_id_foreign',
                'columns' => ['tema_id'],
                'referenced_table' => 'temas',
                'referenced_columns' => ['tema_id'],
                'original_delete' => 'CASCADE',
            ],
            [
                'table' => 'programas',
                'name' => 'programas_programa_materia_id_foreign',
                'columns' => ['programa_materia_id'],
                'referenced_table' => 'materias',
                'referenced_columns' => ['materia_id'],
                'original_delete' => 'CASCADE',
            ],
            [
                'table' => 'programas',
                'name' => 'programas_programa_plan_id_foreign',
                'columns' => ['programa_plan_id'],
                'referenced_table' => 'planes_estudio',
                'referenced_columns' => ['plan_id'],
                'original_delete' => 'CASCADE',
            ],
            [
                'table' => 'programa_tema',
                'name' => 'programa_tema_programa_id_foreign',
                'columns' => ['programa_id'],
                'referenced_table' => 'programas',
                'referenced_columns' => ['programa_id'],
                'original_delete' => 'CASCADE',
            ],
            [
                'table' => 'programa_tema',
                'name' => 'programa_tema_tema_id_foreign',
                'columns' => ['tema_id'],
                'referenced_table' => 'temas',
                'referenced_columns' => ['tema_id'],
                'original_delete' => 'CASCADE',
            ],
            [
                'table' => 'slot_holds',
                'name' => 'slot_holds_profesor_id_foreign',
                'columns' => ['profesor_id'],
                'referenced_table' => 'users',
                'referenced_columns' => ['id'],
                'original_delete' => 'CASCADE',
            ],
            [
                'table' => 'solicitudes_disponibilidad',
                'name' => 'solicitudes_disponibilidad_alumno_id_foreign',
                'columns' => ['alumno_id'],
                'referenced_table' => 'users',
                'referenced_columns' => ['id'],
                'original_delete' => 'CASCADE',
            ],
            [
                'table' => 'solicitudes_disponibilidad',
                'name' => 'solicitudes_disponibilidad_materia_id_foreign',
                'columns' => ['materia_id'],
                'referenced_table' => 'materias',
                'referenced_columns' => ['materia_id'],
                'original_delete' => 'CASCADE',
            ],
            [
                'table' => 'solicitudes_disponibilidad',
                'name' => 'solicitudes_disponibilidad_tema_id_foreign',
                'columns' => ['tema_id'],
                'referenced_table' => 'temas',
                'referenced_columns' => ['tema_id'],
                'original_delete' => 'SET NULL',
            ],
            [
                'table' => 'temas',
                'name' => 'temas_tema_id_tema_padre_foreign',
                'columns' => ['tema_id_tema_padre'],
                'referenced_table' => 'temas',
                'referenced_columns' => ['tema_id'],
                'original_delete' => 'SET NULL',
            ],
            [
                'table' => 'turnos',
                'name' => 'turnos_alumno_id_foreign',
                'columns' => ['alumno_id'],
                'referenced_table' => 'users',
                'referenced_columns' => ['id'],
                'original_delete' => 'CASCADE',
            ],
            [
                'table' => 'turnos',
                'name' => 'turnos_materia_id_foreign',
                'columns' => ['materia_id'],
                'referenced_table' => 'materias',
                'referenced_columns' => ['materia_id'],
                'original_delete' => 'CASCADE',
            ],
            [
                'table' => 'turnos',
                'name' => 'turnos_profesor_id_foreign',
                'columns' => ['profesor_id'],
                'referenced_table' => 'users',
                'referenced_columns' => ['id'],
                'original_delete' => 'CASCADE',
            ],
            [
                'table' => 'turnos',
                'name' => 'turnos_reemplazado_por_turno_id_foreign',
                'columns' => ['reemplazado_por_turno_id'],
                'referenced_table' => 'turnos',
                'referenced_columns' => ['id'],
                'original_delete' => 'SET NULL',
            ],
            [
                'table' => 'turnos',
                'name' => 'turnos_reprogramado_por_turno_id_foreign',
                'columns' => ['reprogramado_por_turno_id'],
                'referenced_table' => 'turnos',
                'referenced_columns' => ['id'],
                'original_delete' => 'SET NULL',
            ],
            [
                'table' => 'turnos',
                'name' => 'turnos_tema_id_foreign',
                'columns' => ['tema_id'],
                'referenced_table' => 'temas',
                'referenced_columns' => ['tema_id'],
                'original_delete' => 'SET NULL',
            ],
            [
                'table' => 'turno_reemplazos',
                'name' => 'turno_reemplazos_alumno_id_foreign',
                'columns' => ['alumno_id'],
                'referenced_table' => 'users',
                'referenced_columns' => ['id'],
                'original_delete' => 'CASCADE',
            ],
            [
                'table' => 'turno_reemplazos',
                'name' => 'turno_reemplazos_profesor_id_foreign',
                'columns' => ['profesor_id'],
                'referenced_table' => 'users',
                'referenced_columns' => ['id'],
                'original_delete' => 'CASCADE',
            ],
            [
                'table' => 'turno_reemplazos',
                'name' => 'turno_reemplazos_turno_cancelado_id_foreign',
                'columns' => ['turno_cancelado_id'],
                'referenced_table' => 'turnos',
                'referenced_columns' => ['id'],
                'original_delete' => 'CASCADE',
            ],
            [
                'table' => 'users',
                'name' => 'users_carrera_activa_id_foreign',
                'columns' => ['carrera_activa_id'],
                'referenced_table' => 'carreras',
                'referenced_columns' => ['carrera_id'],
                'original_delete' => 'SET NULL',
            ]
        ];
    }

    private function replaceDeleteAction(array $foreignKey, ?string $targetDeleteAction): void
    {
        $existing = $this->getConstraint($foreignKey['name']);

        if ($existing !== null) {
            if ((string) $existing->TABLE_NAME !== $foreignKey['table']) {
                throw new RuntimeException("La constraint {$foreignKey['name']} existe pero pertenece a la tabla {$existing->TABLE_NAME}.");
            }

            if ($this->matchesTargetDeleteRule($existing->DELETE_RULE, $targetDeleteAction)) {
                return;
            }

            DB::statement(sprintf(
                'ALTER TABLE `%s` DROP FOREIGN KEY `%s`',
                $foreignKey['table'],
                $foreignKey['name']
            ));
        }

        if ($this->getConstraint($foreignKey['name']) === null) {
            DB::statement($this->buildAddConstraintSql($foreignKey, $targetDeleteAction));
        }
    }

    private function getConstraint(string $constraintName): ?object
    {
        return DB::table('information_schema.REFERENTIAL_CONSTRAINTS')
            ->select('CONSTRAINT_NAME', 'TABLE_NAME', 'REFERENCED_TABLE_NAME', 'DELETE_RULE', 'UPDATE_RULE')
            ->where('CONSTRAINT_SCHEMA', DB::raw('DATABASE()'))
            ->where('CONSTRAINT_NAME', $constraintName)
            ->first();
    }

    private function matchesTargetDeleteRule(string $currentRule, ?string $targetDeleteAction): bool
    {
        $currentRule = strtoupper($currentRule);

        if ($targetDeleteAction === null) {
            return in_array($currentRule, ['RESTRICT', 'NO ACTION'], true);
        }

        return $currentRule === strtoupper($targetDeleteAction);
    }

    private function buildAddConstraintSql(array $foreignKey, ?string $deleteAction): string
    {
        $columns = implode(', ', array_map(fn (string $column) => "`{$column}`", $foreignKey['columns']));
        $referencedColumns = implode(', ', array_map(fn (string $column) => "`{$column}`", $foreignKey['referenced_columns']));

        $sql = sprintf(
            'ALTER TABLE `%s` ADD CONSTRAINT `%s` FOREIGN KEY (%s) REFERENCES `%s` (%s)',
            $foreignKey['table'],
            $foreignKey['name'],
            $columns,
            $foreignKey['referenced_table'],
            $referencedColumns,
        );

        if ($deleteAction !== null) {
            $sql .= ' ON DELETE ' . strtoupper($deleteAction);
        }

        return $sql;
    }
};
