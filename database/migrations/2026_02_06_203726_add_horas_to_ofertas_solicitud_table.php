<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('ofertas_solicitud', function (Blueprint $table) {
            // 1) Agregar horas
            if (! Schema::hasColumn('ofertas_solicitud', 'hora_inicio')) {
                $table->time('hora_inicio')->nullable()->after('profesor_id');
            }
            if (! Schema::hasColumn('ofertas_solicitud', 'hora_fin')) {
                $table->time('hora_fin')->nullable()->after('hora_inicio');
            }

            // 2) IMPORTANTÍSIMO: crear índices simples antes de tocar el UNIQUE viejo
            // Esto evita el error 1553 (FK necesita un índice).
            if (! Schema::hasColumn('ofertas_solicitud', 'solicitud_id')) {
                // no debería pasar porque ya existe
            } else {
                // El schema builder no tiene "hasIndex" nativo, así que lo agregamos directo.
                // Si ya existe, MySQL suele tirar error -> por eso usamos try/catch.
                try { $table->index('solicitud_id', 'idx_ofertas_solicitud_solicitud_id'); } catch (\Throwable $e) {}
                try { $table->index('profesor_id', 'idx_ofertas_solicitud_profesor_id'); } catch (\Throwable $e) {}
            }
        });

        // 3) Ahora sí: dropear UNIQUE viejo y crear el nuevo con tramo
        Schema::table('ofertas_solicitud', function (Blueprint $table) {
            // nombre del unique viejo (Laravel lo genera así por defecto)
            try {
                $table->dropUnique('ofertas_solicitud_solicitud_id_profesor_id_unique');
            } catch (\Throwable $e) {
                // si ya no existe, ok
            }

            // nuevo unique: permite varias ofertas por profe en la misma solicitud (distintos tramos)
            try {
                $table->unique(['solicitud_id', 'profesor_id', 'hora_inicio', 'hora_fin'], 'uniq_oferta_tramo');
            } catch (\Throwable $e) {
                // si ya existe, ok
            }
        });
    }

    public function down(): void
    {
        Schema::table('ofertas_solicitud', function (Blueprint $table) {
            try { $table->dropUnique('uniq_oferta_tramo'); } catch (\Throwable $e) {}

            // restaurar unique viejo
            try {
                $table->unique(['solicitud_id', 'profesor_id'], 'ofertas_solicitud_solicitud_id_profesor_id_unique');
            } catch (\Throwable $e) {}

            if (Schema::hasColumn('ofertas_solicitud', 'hora_inicio')) {
                $table->dropColumn('hora_inicio');
            }
            if (Schema::hasColumn('ofertas_solicitud', 'hora_fin')) {
                $table->dropColumn('hora_fin');
            }

            // opcional: borrar índices simples (si querés)
            try { $table->dropIndex('idx_ofertas_solicitud_solicitud_id'); } catch (\Throwable $e) {}
            try { $table->dropIndex('idx_ofertas_solicitud_profesor_id'); } catch (\Throwable $e) {}
        });
    }
};
