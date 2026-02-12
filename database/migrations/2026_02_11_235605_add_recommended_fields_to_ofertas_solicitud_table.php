<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('ofertas_solicitud', function (Blueprint $table) {
            if (! Schema::hasColumn('ofertas_solicitud', 'recommended_turno_id')) {
                $table->unsignedBigInteger('recommended_turno_id')->nullable()->after('expires_at');
            }

            if (! Schema::hasColumn('ofertas_solicitud', 'recommended_reason')) {
                $table->string('recommended_reason')->nullable()->after('recommended_turno_id'); // ej: "slot_liberado_cancelacion"
            }

            // (opcional) índice para ordenar/filtrar rápido
            try { $table->index('recommended_turno_id', 'idx_ofertas_recommended_turno'); } catch (\Throwable $e) {}
        });
    }

    public function down(): void
    {
        Schema::table('ofertas_solicitud', function (Blueprint $table) {
            try { $table->dropIndex('idx_ofertas_recommended_turno'); } catch (\Throwable $e) {}

            if (Schema::hasColumn('ofertas_solicitud', 'recommended_reason')) {
                $table->dropColumn('recommended_reason');
            }
            if (Schema::hasColumn('ofertas_solicitud', 'recommended_turno_id')) {
                $table->dropColumn('recommended_turno_id');
            }
        });
    }
};
