<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pagos', function (Blueprint $table) {
            $table->timestamp('recordatorio_pago_enviado_at')
                ->nullable()
                ->after('fecha_aprobado'); // si esa columna existe
        });
    }

    public function down(): void
    {
        Schema::table('pagos', function (Blueprint $table) {
            $table->dropColumn('recordatorio_pago_enviado_at');
        });
    }
};
