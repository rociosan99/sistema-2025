<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('turnos', function (Blueprint $table) {
            $table->foreignId('reemplazo_profesor_propuesto_id')
                ->nullable()
                ->after('suspension_motivo')
                ->constrained('users')
                ->nullOnDelete();
            $table->date('reemplazo_fecha')->nullable()->after('reemplazo_profesor_propuesto_id');
            $table->time('reemplazo_hora_inicio')->nullable()->after('reemplazo_fecha');
            $table->time('reemplazo_hora_fin')->nullable()->after('reemplazo_hora_inicio');
            $table->timestamp('reemplazo_solicitado_at')->nullable()->after('reemplazo_hora_fin');
            $table->timestamp('reemplazo_expires_at')->nullable()->after('reemplazo_solicitado_at');

            $table->index(
                ['reemplazo_profesor_propuesto_id', 'reemplazo_expires_at'],
                'turnos_reemplazo_profesor_expires_idx',
            );
        });
    }

    public function down(): void
    {
        Schema::table('turnos', function (Blueprint $table) {
            $table->dropIndex('turnos_reemplazo_profesor_expires_idx');
            $table->dropForeign(['reemplazo_profesor_propuesto_id']);
            $table->dropColumn([
                'reemplazo_profesor_propuesto_id',
                'reemplazo_fecha',
                'reemplazo_hora_inicio',
                'reemplazo_hora_fin',
                'reemplazo_solicitado_at',
                'reemplazo_expires_at',
            ]);
        });
    }
};
