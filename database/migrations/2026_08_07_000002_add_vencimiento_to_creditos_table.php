<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('creditos', function (Blueprint $table) {
            $table->unsignedSmallInteger('vigencia_dias_aplicada')->nullable()->after('horas_limite_aplicadas');
            $table->timestamp('vence_at')->nullable()->after('cancelado_at');
            $table->index(['estado', 'vence_at']);
        });
    }

    public function down(): void
    {
        Schema::table('creditos', function (Blueprint $table) {
            $table->dropIndex(['estado', 'vence_at']);
            $table->dropColumn(['vigencia_dias_aplicada', 'vence_at']);
        });
    }
};
