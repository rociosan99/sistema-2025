<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('turno_reemplazos', function (Blueprint $table) {
            $table->timestamp('notificado_at')->nullable()->after('expires_at');
            $table->index('notificado_at');
        });
    }

    public function down(): void
    {
        Schema::table('turno_reemplazos', function (Blueprint $table) {
            $table->dropIndex(['notificado_at']);
            $table->dropColumn('notificado_at');
        });
    }
};