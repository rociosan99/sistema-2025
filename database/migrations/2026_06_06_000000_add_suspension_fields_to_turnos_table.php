<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('turnos', function (Blueprint $table) {
            $table->timestamp('suspendido_at')->nullable()->after('reprogramado_at');
            $table->unsignedBigInteger('suspendido_por_id')->nullable()->after('suspendido_at');
            $table->string('suspension_motivo', 1000)->nullable()->after('suspendido_por_id');

            $table->index('suspendido_por_id');

            $table->foreign('suspendido_por_id')
                ->references('id')->on('users')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('turnos', function (Blueprint $table) {
            $table->dropForeign(['suspendido_por_id']);
            $table->dropIndex(['suspendido_por_id']);
            $table->dropColumn(['suspendido_at', 'suspendido_por_id', 'suspension_motivo']);
        });
    }
};
