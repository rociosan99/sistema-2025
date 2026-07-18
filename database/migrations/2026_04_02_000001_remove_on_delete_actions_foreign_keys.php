<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Elimina todas las acciones ON DELETE CASCADE y ON DELETE SET NULL
     * de las foreign keys detectadas en el dump compartido.
     *
     * Nota:
     * - No modifica datos existentes.
     * - A partir de esta migración, un DELETE sobre la tabla padre fallará
     *   si existen registros hijos relacionados, salvo que la aplicación
     *   los elimine o desvincule manualmente antes.
     */
    public function up(): void
    {
        $statements = [
            <<<'SQL'
ALTER TABLE `alumno_carreras`
    DROP FOREIGN KEY `alumno_carreras_alumno_id_foreign`,
    DROP FOREIGN KEY `alumno_carreras_carrera_id_foreign`,
    ADD CONSTRAINT `alumno_carreras_alumno_id_foreign` FOREIGN KEY (`alumno_id`) REFERENCES `users` (`id`),
    ADD CONSTRAINT `alumno_carreras_carrera_id_foreign` FOREIGN KEY (`carrera_id`) REFERENCES `carreras` (`carrera_id`);
SQL,
            <<<'SQL'
ALTER TABLE `calificaciones_alumno`
    DROP FOREIGN KEY `calificaciones_alumno_alumno_id_foreign`,
    DROP FOREIGN KEY `calificaciones_alumno_profesor_id_foreign`,
    DROP FOREIGN KEY `calificaciones_alumno_turno_id_foreign`,
    ADD CONSTRAINT `calificaciones_alumno_alumno_id_foreign` FOREIGN KEY (`alumno_id`) REFERENCES `users` (`id`),
    ADD CONSTRAINT `calificaciones_alumno_profesor_id_foreign` FOREIGN KEY (`profesor_id`) REFERENCES `users` (`id`),
    ADD CONSTRAINT `calificaciones_alumno_turno_id_foreign` FOREIGN KEY (`turno_id`) REFERENCES `turnos` (`id`);
SQL,
            <<<'SQL'
ALTER TABLE `calificaciones_profesor`
    DROP FOREIGN KEY `calificaciones_profesor_alumno_id_foreign`,
    DROP FOREIGN KEY `calificaciones_profesor_profesor_id_foreign`,
    DROP FOREIGN KEY `calificaciones_profesor_turno_id_foreign`,
    ADD CONSTRAINT `calificaciones_profesor_alumno_id_foreign` FOREIGN KEY (`alumno_id`) REFERENCES `users` (`id`),
    ADD CONSTRAINT `calificaciones_profesor_profesor_id_foreign` FOREIGN KEY (`profesor_id`) REFERENCES `users` (`id`),
    ADD CONSTRAINT `calificaciones_profesor_turno_id_foreign` FOREIGN KEY (`turno_id`) REFERENCES `turnos` (`id`);
SQL,
            <<<'SQL'
ALTER TABLE `carreras`
    DROP FOREIGN KEY `carreras_carrera_institucion_id_foreign`,
    ADD CONSTRAINT `carreras_carrera_institucion_id_foreign` FOREIGN KEY (`carrera_institucion_id`) REFERENCES `instituciones` (`institucion_id`);
SQL,
            <<<'SQL'
ALTER TABLE `carrera_materias`
    DROP FOREIGN KEY `carrera_materias_carreramateria_carrera_id_foreign`,
    DROP FOREIGN KEY `carrera_materias_carreramateria_materia_id_foreign`,
    ADD CONSTRAINT `carrera_materias_carreramateria_carrera_id_foreign` FOREIGN KEY (`carreramateria_carrera_id`) REFERENCES `carreras` (`carrera_id`),
    ADD CONSTRAINT `carrera_materias_carreramateria_materia_id_foreign` FOREIGN KEY (`carreramateria_materia_id`) REFERENCES `materias` (`materia_id`);
SQL,
            <<<'SQL'
ALTER TABLE `disponibilidades`
    DROP FOREIGN KEY `disponibilidades_profesor_id_foreign`,
    ADD CONSTRAINT `disponibilidades_profesor_id_foreign` FOREIGN KEY (`profesor_id`) REFERENCES `users` (`id`);
SQL,
            <<<'SQL'
ALTER TABLE `materia_tema`
    DROP FOREIGN KEY `materia_tema_materia_id_foreign`,
    DROP FOREIGN KEY `materia_tema_tema_id_foreign`,
    ADD CONSTRAINT `materia_tema_materia_id_foreign` FOREIGN KEY (`materia_id`) REFERENCES `materias` (`materia_id`),
    ADD CONSTRAINT `materia_tema_tema_id_foreign` FOREIGN KEY (`tema_id`) REFERENCES `temas` (`tema_id`);
SQL,
            <<<'SQL'
ALTER TABLE `ofertas_solicitud`
    DROP FOREIGN KEY `ofertas_solicitud_profesor_id_foreign`,
    DROP FOREIGN KEY `ofertas_solicitud_solicitud_id_foreign`,
    ADD CONSTRAINT `ofertas_solicitud_profesor_id_foreign` FOREIGN KEY (`profesor_id`) REFERENCES `users` (`id`),
    ADD CONSTRAINT `ofertas_solicitud_solicitud_id_foreign` FOREIGN KEY (`solicitud_id`) REFERENCES `solicitudes_disponibilidad` (`id`);
SQL,
            <<<'SQL'
ALTER TABLE `pagos`
    DROP FOREIGN KEY `pagos_turno_id_foreign`,
    ADD CONSTRAINT `pagos_turno_id_foreign` FOREIGN KEY (`turno_id`) REFERENCES `turnos` (`id`);
SQL,
            <<<'SQL'
ALTER TABLE `planes_estudio`
    DROP FOREIGN KEY `planes_estudio_plan_carrera_id_foreign`,
    ADD CONSTRAINT `planes_estudio_plan_carrera_id_foreign` FOREIGN KEY (`plan_carrera_id`) REFERENCES `carreras` (`carrera_id`);
SQL,
            <<<'SQL'
ALTER TABLE `profesor_materia`
    DROP FOREIGN KEY `profesor_materia_materia_id_foreign`,
    DROP FOREIGN KEY `profesor_materia_profesor_id_foreign`,
    ADD CONSTRAINT `profesor_materia_materia_id_foreign` FOREIGN KEY (`materia_id`) REFERENCES `materias` (`materia_id`),
    ADD CONSTRAINT `profesor_materia_profesor_id_foreign` FOREIGN KEY (`profesor_id`) REFERENCES `users` (`id`);
SQL,
            <<<'SQL'
ALTER TABLE `profesor_profiles`
    DROP FOREIGN KEY `profesor_profiles_user_id_foreign`,
    ADD CONSTRAINT `profesor_profiles_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);
SQL,
            <<<'SQL'
ALTER TABLE `profesor_tema`
    DROP FOREIGN KEY `profesor_tema_profesor_id_foreign`,
    DROP FOREIGN KEY `profesor_tema_tema_id_foreign`,
    ADD CONSTRAINT `profesor_tema_profesor_id_foreign` FOREIGN KEY (`profesor_id`) REFERENCES `users` (`id`),
    ADD CONSTRAINT `profesor_tema_tema_id_foreign` FOREIGN KEY (`tema_id`) REFERENCES `temas` (`tema_id`);
SQL,
            <<<'SQL'
ALTER TABLE `programas`
    DROP FOREIGN KEY `programas_programa_materia_id_foreign`,
    DROP FOREIGN KEY `programas_programa_plan_id_foreign`,
    ADD CONSTRAINT `programas_programa_materia_id_foreign` FOREIGN KEY (`programa_materia_id`) REFERENCES `materias` (`materia_id`),
    ADD CONSTRAINT `programas_programa_plan_id_foreign` FOREIGN KEY (`programa_plan_id`) REFERENCES `planes_estudio` (`plan_id`);
SQL,
            <<<'SQL'
ALTER TABLE `programa_tema`
    DROP FOREIGN KEY `programa_tema_programa_id_foreign`,
    DROP FOREIGN KEY `programa_tema_tema_id_foreign`,
    ADD CONSTRAINT `programa_tema_programa_id_foreign` FOREIGN KEY (`programa_id`) REFERENCES `programas` (`programa_id`),
    ADD CONSTRAINT `programa_tema_tema_id_foreign` FOREIGN KEY (`tema_id`) REFERENCES `temas` (`tema_id`);
SQL,
            <<<'SQL'
ALTER TABLE `slot_holds`
    DROP FOREIGN KEY `slot_holds_profesor_id_foreign`,
    ADD CONSTRAINT `slot_holds_profesor_id_foreign` FOREIGN KEY (`profesor_id`) REFERENCES `users` (`id`);
SQL,
            <<<'SQL'
ALTER TABLE `solicitudes_disponibilidad`
    DROP FOREIGN KEY `solicitudes_disponibilidad_alumno_id_foreign`,
    DROP FOREIGN KEY `solicitudes_disponibilidad_materia_id_foreign`,
    DROP FOREIGN KEY `solicitudes_disponibilidad_tema_id_foreign`,
    ADD CONSTRAINT `solicitudes_disponibilidad_alumno_id_foreign` FOREIGN KEY (`alumno_id`) REFERENCES `users` (`id`),
    ADD CONSTRAINT `solicitudes_disponibilidad_materia_id_foreign` FOREIGN KEY (`materia_id`) REFERENCES `materias` (`materia_id`),
    ADD CONSTRAINT `solicitudes_disponibilidad_tema_id_foreign` FOREIGN KEY (`tema_id`) REFERENCES `temas` (`tema_id`);
SQL,
            <<<'SQL'
ALTER TABLE `temas`
    DROP FOREIGN KEY `temas_tema_id_tema_padre_foreign`,
    ADD CONSTRAINT `temas_tema_id_tema_padre_foreign` FOREIGN KEY (`tema_id_tema_padre`) REFERENCES `temas` (`tema_id`);
SQL,
            <<<'SQL'
ALTER TABLE `turnos`
    DROP FOREIGN KEY `turnos_alumno_id_foreign`,
    DROP FOREIGN KEY `turnos_materia_id_foreign`,
    DROP FOREIGN KEY `turnos_profesor_id_foreign`,
    DROP FOREIGN KEY `turnos_reemplazado_por_turno_id_foreign`,
    DROP FOREIGN KEY `turnos_reprogramado_por_turno_id_foreign`,
    DROP FOREIGN KEY `turnos_tema_id_foreign`,
    ADD CONSTRAINT `turnos_alumno_id_foreign` FOREIGN KEY (`alumno_id`) REFERENCES `users` (`id`),
    ADD CONSTRAINT `turnos_materia_id_foreign` FOREIGN KEY (`materia_id`) REFERENCES `materias` (`materia_id`),
    ADD CONSTRAINT `turnos_profesor_id_foreign` FOREIGN KEY (`profesor_id`) REFERENCES `users` (`id`),
    ADD CONSTRAINT `turnos_reemplazado_por_turno_id_foreign` FOREIGN KEY (`reemplazado_por_turno_id`) REFERENCES `turnos` (`id`),
    ADD CONSTRAINT `turnos_reprogramado_por_turno_id_foreign` FOREIGN KEY (`reprogramado_por_turno_id`) REFERENCES `turnos` (`id`),
    ADD CONSTRAINT `turnos_tema_id_foreign` FOREIGN KEY (`tema_id`) REFERENCES `temas` (`tema_id`);
SQL,
            <<<'SQL'
ALTER TABLE `turno_reemplazos`
    DROP FOREIGN KEY `turno_reemplazos_alumno_id_foreign`,
    DROP FOREIGN KEY `turno_reemplazos_profesor_id_foreign`,
    DROP FOREIGN KEY `turno_reemplazos_turno_cancelado_id_foreign`,
    ADD CONSTRAINT `turno_reemplazos_alumno_id_foreign` FOREIGN KEY (`alumno_id`) REFERENCES `users` (`id`),
    ADD CONSTRAINT `turno_reemplazos_profesor_id_foreign` FOREIGN KEY (`profesor_id`) REFERENCES `users` (`id`),
    ADD CONSTRAINT `turno_reemplazos_turno_cancelado_id_foreign` FOREIGN KEY (`turno_cancelado_id`) REFERENCES `turnos` (`id`);
SQL,
            <<<'SQL'
ALTER TABLE `users`
    DROP FOREIGN KEY `users_carrera_activa_id_foreign`,
    ADD CONSTRAINT `users_carrera_activa_id_foreign` FOREIGN KEY (`carrera_activa_id`) REFERENCES `carreras` (`carrera_id`);
SQL
        ];

        $this->runStatements($statements);
    }

    /**
     * Restaura las acciones ON DELETE originales del dump:
     * - CASCADE
     * - SET NULL
     */
    public function down(): void
    {
        $statements = [
            <<<'SQL'
ALTER TABLE `alumno_carreras`
    DROP FOREIGN KEY `alumno_carreras_alumno_id_foreign`,
    DROP FOREIGN KEY `alumno_carreras_carrera_id_foreign`,
    ADD CONSTRAINT `alumno_carreras_alumno_id_foreign` FOREIGN KEY (`alumno_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
    ADD CONSTRAINT `alumno_carreras_carrera_id_foreign` FOREIGN KEY (`carrera_id`) REFERENCES `carreras` (`carrera_id`) ON DELETE CASCADE;
SQL,
            <<<'SQL'
ALTER TABLE `calificaciones_alumno`
    DROP FOREIGN KEY `calificaciones_alumno_alumno_id_foreign`,
    DROP FOREIGN KEY `calificaciones_alumno_profesor_id_foreign`,
    DROP FOREIGN KEY `calificaciones_alumno_turno_id_foreign`,
    ADD CONSTRAINT `calificaciones_alumno_alumno_id_foreign` FOREIGN KEY (`alumno_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
    ADD CONSTRAINT `calificaciones_alumno_profesor_id_foreign` FOREIGN KEY (`profesor_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
    ADD CONSTRAINT `calificaciones_alumno_turno_id_foreign` FOREIGN KEY (`turno_id`) REFERENCES `turnos` (`id`) ON DELETE CASCADE;
SQL,
            <<<'SQL'
ALTER TABLE `calificaciones_profesor`
    DROP FOREIGN KEY `calificaciones_profesor_alumno_id_foreign`,
    DROP FOREIGN KEY `calificaciones_profesor_profesor_id_foreign`,
    DROP FOREIGN KEY `calificaciones_profesor_turno_id_foreign`,
    ADD CONSTRAINT `calificaciones_profesor_alumno_id_foreign` FOREIGN KEY (`alumno_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
    ADD CONSTRAINT `calificaciones_profesor_profesor_id_foreign` FOREIGN KEY (`profesor_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
    ADD CONSTRAINT `calificaciones_profesor_turno_id_foreign` FOREIGN KEY (`turno_id`) REFERENCES `turnos` (`id`) ON DELETE CASCADE;
SQL,
            <<<'SQL'
ALTER TABLE `carreras`
    DROP FOREIGN KEY `carreras_carrera_institucion_id_foreign`,
    ADD CONSTRAINT `carreras_carrera_institucion_id_foreign` FOREIGN KEY (`carrera_institucion_id`) REFERENCES `instituciones` (`institucion_id`) ON DELETE CASCADE;
SQL,
            <<<'SQL'
ALTER TABLE `carrera_materias`
    DROP FOREIGN KEY `carrera_materias_carreramateria_carrera_id_foreign`,
    DROP FOREIGN KEY `carrera_materias_carreramateria_materia_id_foreign`,
    ADD CONSTRAINT `carrera_materias_carreramateria_carrera_id_foreign` FOREIGN KEY (`carreramateria_carrera_id`) REFERENCES `carreras` (`carrera_id`) ON DELETE CASCADE,
    ADD CONSTRAINT `carrera_materias_carreramateria_materia_id_foreign` FOREIGN KEY (`carreramateria_materia_id`) REFERENCES `materias` (`materia_id`) ON DELETE CASCADE;
SQL,
            <<<'SQL'
ALTER TABLE `disponibilidades`
    DROP FOREIGN KEY `disponibilidades_profesor_id_foreign`,
    ADD CONSTRAINT `disponibilidades_profesor_id_foreign` FOREIGN KEY (`profesor_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
SQL,
            <<<'SQL'
ALTER TABLE `materia_tema`
    DROP FOREIGN KEY `materia_tema_materia_id_foreign`,
    DROP FOREIGN KEY `materia_tema_tema_id_foreign`,
    ADD CONSTRAINT `materia_tema_materia_id_foreign` FOREIGN KEY (`materia_id`) REFERENCES `materias` (`materia_id`) ON DELETE CASCADE,
    ADD CONSTRAINT `materia_tema_tema_id_foreign` FOREIGN KEY (`tema_id`) REFERENCES `temas` (`tema_id`) ON DELETE CASCADE;
SQL,
            <<<'SQL'
ALTER TABLE `ofertas_solicitud`
    DROP FOREIGN KEY `ofertas_solicitud_profesor_id_foreign`,
    DROP FOREIGN KEY `ofertas_solicitud_solicitud_id_foreign`,
    ADD CONSTRAINT `ofertas_solicitud_profesor_id_foreign` FOREIGN KEY (`profesor_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
    ADD CONSTRAINT `ofertas_solicitud_solicitud_id_foreign` FOREIGN KEY (`solicitud_id`) REFERENCES `solicitudes_disponibilidad` (`id`) ON DELETE CASCADE;
SQL,
            <<<'SQL'
ALTER TABLE `pagos`
    DROP FOREIGN KEY `pagos_turno_id_foreign`,
    ADD CONSTRAINT `pagos_turno_id_foreign` FOREIGN KEY (`turno_id`) REFERENCES `turnos` (`id`) ON DELETE CASCADE;
SQL,
            <<<'SQL'
ALTER TABLE `planes_estudio`
    DROP FOREIGN KEY `planes_estudio_plan_carrera_id_foreign`,
    ADD CONSTRAINT `planes_estudio_plan_carrera_id_foreign` FOREIGN KEY (`plan_carrera_id`) REFERENCES `carreras` (`carrera_id`) ON DELETE CASCADE;
SQL,
            <<<'SQL'
ALTER TABLE `profesor_materia`
    DROP FOREIGN KEY `profesor_materia_materia_id_foreign`,
    DROP FOREIGN KEY `profesor_materia_profesor_id_foreign`,
    ADD CONSTRAINT `profesor_materia_materia_id_foreign` FOREIGN KEY (`materia_id`) REFERENCES `materias` (`materia_id`) ON DELETE CASCADE,
    ADD CONSTRAINT `profesor_materia_profesor_id_foreign` FOREIGN KEY (`profesor_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
SQL,
            <<<'SQL'
ALTER TABLE `profesor_profiles`
    DROP FOREIGN KEY `profesor_profiles_user_id_foreign`,
    ADD CONSTRAINT `profesor_profiles_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
SQL,
            <<<'SQL'
ALTER TABLE `profesor_tema`
    DROP FOREIGN KEY `profesor_tema_profesor_id_foreign`,
    DROP FOREIGN KEY `profesor_tema_tema_id_foreign`,
    ADD CONSTRAINT `profesor_tema_profesor_id_foreign` FOREIGN KEY (`profesor_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
    ADD CONSTRAINT `profesor_tema_tema_id_foreign` FOREIGN KEY (`tema_id`) REFERENCES `temas` (`tema_id`) ON DELETE CASCADE;
SQL,
            <<<'SQL'
ALTER TABLE `programas`
    DROP FOREIGN KEY `programas_programa_materia_id_foreign`,
    DROP FOREIGN KEY `programas_programa_plan_id_foreign`,
    ADD CONSTRAINT `programas_programa_materia_id_foreign` FOREIGN KEY (`programa_materia_id`) REFERENCES `materias` (`materia_id`) ON DELETE CASCADE,
    ADD CONSTRAINT `programas_programa_plan_id_foreign` FOREIGN KEY (`programa_plan_id`) REFERENCES `planes_estudio` (`plan_id`) ON DELETE CASCADE;
SQL,
            <<<'SQL'
ALTER TABLE `programa_tema`
    DROP FOREIGN KEY `programa_tema_programa_id_foreign`,
    DROP FOREIGN KEY `programa_tema_tema_id_foreign`,
    ADD CONSTRAINT `programa_tema_programa_id_foreign` FOREIGN KEY (`programa_id`) REFERENCES `programas` (`programa_id`) ON DELETE CASCADE,
    ADD CONSTRAINT `programa_tema_tema_id_foreign` FOREIGN KEY (`tema_id`) REFERENCES `temas` (`tema_id`) ON DELETE CASCADE;
SQL,
            <<<'SQL'
ALTER TABLE `slot_holds`
    DROP FOREIGN KEY `slot_holds_profesor_id_foreign`,
    ADD CONSTRAINT `slot_holds_profesor_id_foreign` FOREIGN KEY (`profesor_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
SQL,
            <<<'SQL'
ALTER TABLE `solicitudes_disponibilidad`
    DROP FOREIGN KEY `solicitudes_disponibilidad_alumno_id_foreign`,
    DROP FOREIGN KEY `solicitudes_disponibilidad_materia_id_foreign`,
    DROP FOREIGN KEY `solicitudes_disponibilidad_tema_id_foreign`,
    ADD CONSTRAINT `solicitudes_disponibilidad_alumno_id_foreign` FOREIGN KEY (`alumno_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
    ADD CONSTRAINT `solicitudes_disponibilidad_materia_id_foreign` FOREIGN KEY (`materia_id`) REFERENCES `materias` (`materia_id`) ON DELETE CASCADE,
    ADD CONSTRAINT `solicitudes_disponibilidad_tema_id_foreign` FOREIGN KEY (`tema_id`) REFERENCES `temas` (`tema_id`) ON DELETE SET NULL;
SQL,
            <<<'SQL'
ALTER TABLE `temas`
    DROP FOREIGN KEY `temas_tema_id_tema_padre_foreign`,
    ADD CONSTRAINT `temas_tema_id_tema_padre_foreign` FOREIGN KEY (`tema_id_tema_padre`) REFERENCES `temas` (`tema_id`) ON DELETE SET NULL;
SQL,
            <<<'SQL'
ALTER TABLE `turnos`
    DROP FOREIGN KEY `turnos_alumno_id_foreign`,
    DROP FOREIGN KEY `turnos_materia_id_foreign`,
    DROP FOREIGN KEY `turnos_profesor_id_foreign`,
    DROP FOREIGN KEY `turnos_reemplazado_por_turno_id_foreign`,
    DROP FOREIGN KEY `turnos_reprogramado_por_turno_id_foreign`,
    DROP FOREIGN KEY `turnos_tema_id_foreign`,
    ADD CONSTRAINT `turnos_alumno_id_foreign` FOREIGN KEY (`alumno_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
    ADD CONSTRAINT `turnos_materia_id_foreign` FOREIGN KEY (`materia_id`) REFERENCES `materias` (`materia_id`) ON DELETE CASCADE,
    ADD CONSTRAINT `turnos_profesor_id_foreign` FOREIGN KEY (`profesor_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
    ADD CONSTRAINT `turnos_reemplazado_por_turno_id_foreign` FOREIGN KEY (`reemplazado_por_turno_id`) REFERENCES `turnos` (`id`) ON DELETE SET NULL,
    ADD CONSTRAINT `turnos_reprogramado_por_turno_id_foreign` FOREIGN KEY (`reprogramado_por_turno_id`) REFERENCES `turnos` (`id`) ON DELETE SET NULL,
    ADD CONSTRAINT `turnos_tema_id_foreign` FOREIGN KEY (`tema_id`) REFERENCES `temas` (`tema_id`) ON DELETE SET NULL;
SQL,
            <<<'SQL'
ALTER TABLE `turno_reemplazos`
    DROP FOREIGN KEY `turno_reemplazos_alumno_id_foreign`,
    DROP FOREIGN KEY `turno_reemplazos_profesor_id_foreign`,
    DROP FOREIGN KEY `turno_reemplazos_turno_cancelado_id_foreign`,
    ADD CONSTRAINT `turno_reemplazos_alumno_id_foreign` FOREIGN KEY (`alumno_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
    ADD CONSTRAINT `turno_reemplazos_profesor_id_foreign` FOREIGN KEY (`profesor_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
    ADD CONSTRAINT `turno_reemplazos_turno_cancelado_id_foreign` FOREIGN KEY (`turno_cancelado_id`) REFERENCES `turnos` (`id`) ON DELETE CASCADE;
SQL,
            <<<'SQL'
ALTER TABLE `users`
    DROP FOREIGN KEY `users_carrera_activa_id_foreign`,
    ADD CONSTRAINT `users_carrera_activa_id_foreign` FOREIGN KEY (`carrera_activa_id`) REFERENCES `carreras` (`carrera_id`) ON DELETE SET NULL;
SQL
        ];

        $this->runStatements($statements);
    }

    private function runStatements(array $statements): void
    {
        if (DB::getDriverName() !== 'mysql') {
            throw new RuntimeException('Esta migración fue generada para MySQL/MariaDB.');
        }

        foreach ($statements as $statement) {
            DB::statement($statement);
        }
    }
};
