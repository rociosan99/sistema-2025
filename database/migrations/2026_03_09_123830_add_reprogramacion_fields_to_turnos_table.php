<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('turnos', function (Blueprint $table) {
            // Turno original -> apunta al turno nuevo reprogramado
            $table->unsignedBigInteger('reprogramado_por_turno_id')->nullable()->after('reemplazado_por_turno_id');
            $table->timestamp('reprogramado_at')->nullable()->after('reprogramado_por_turno_id');

            $table->index('reprogramado_por_turno_id');

            $table->foreign('reprogramado_por_turno_id')
                ->references('id')->on('turnos')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('turnos', function (Blueprint $table) {
            $table->dropForeign(['reprogramado_por_turno_id']);
            $table->dropIndex(['reprogramado_por_turno_id']);
            $table->dropColumn(['reprogramado_por_turno_id', 'reprogramado_at']);
        });
    }
};