<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('turnos', function (Blueprint $table) {
            if (!Schema::hasColumn('turnos', 'cancelado_at')) {
                $table->timestamp('cancelado_at')->nullable()->after('estado');
            }
            if (!Schema::hasColumn('turnos', 'cancelacion_tipo')) {
                $table->string('cancelacion_tipo', 20)->nullable()->after('cancelado_at'); // sin_cargo|con_cargo
            }
            if (!Schema::hasColumn('turnos', 'reemplazado_por_turno_id')) {
                $table->unsignedBigInteger('reemplazado_por_turno_id')->nullable()->after('cancelacion_tipo');
                $table->foreign('reemplazado_por_turno_id')->references('id')->on('turnos')->nullOnDelete();
            }

            $table->index(['cancelado_at']);
            $table->index(['cancelacion_tipo']);
        });
    }

    public function down(): void
    {
        Schema::table('turnos', function (Blueprint $table) {
            try { $table->dropForeign(['reemplazado_por_turno_id']); } catch (\Throwable $e) {}
            if (Schema::hasColumn('turnos', 'reemplazado_por_turno_id')) $table->dropColumn('reemplazado_por_turno_id');
            if (Schema::hasColumn('turnos', 'cancelacion_tipo')) $table->dropColumn('cancelacion_tipo');
            if (Schema::hasColumn('turnos', 'cancelado_at')) $table->dropColumn('cancelado_at');
        });
    }
};