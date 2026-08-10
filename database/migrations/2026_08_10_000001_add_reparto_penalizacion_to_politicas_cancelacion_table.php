<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('politicas_cancelacion', function (Blueprint $table) {
            $table->decimal('porcentaje_profesor_penalizacion', 5, 2)
                ->nullable()
                ->after('vigencia_creditos_dias');
            $table->decimal('porcentaje_plataforma_penalizacion', 5, 2)
                ->nullable()
                ->after('porcentaje_profesor_penalizacion');
        });
    }

    public function down(): void
    {
        Schema::table('politicas_cancelacion', function (Blueprint $table) {
            $table->dropColumn([
                'porcentaje_profesor_penalizacion',
                'porcentaje_plataforma_penalizacion',
            ]);
        });
    }
};
