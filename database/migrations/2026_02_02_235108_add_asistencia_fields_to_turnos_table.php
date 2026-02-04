<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('turnos', function (Blueprint $table) {
            $table->timestamp('asistencia_confirmada_at')->nullable()->after('estado');
            $table->timestamp('asistencia_cancelada_at')->nullable()->after('asistencia_confirmada_at');
        });
    }

    public function down(): void
    {
        Schema::table('turnos', function (Blueprint $table) {
            $table->dropColumn(['asistencia_confirmada_at', 'asistencia_cancelada_at']);
        });
    }
};
