<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('creditos', function (Blueprint $table) {
            $table->decimal('porcentaje_profesor_penalizacion_aplicado', 5, 2)
                ->nullable()
                ->after('porcentaje_penalizacion_aplicado');
            $table->decimal('porcentaje_plataforma_penalizacion_aplicado', 5, 2)
                ->nullable()
                ->after('porcentaje_profesor_penalizacion_aplicado');
            $table->decimal('importe_penalizacion_profesor', 10, 2)
                ->nullable()
                ->after('importe_penalizacion');
            $table->decimal('importe_penalizacion_plataforma', 10, 2)
                ->nullable()
                ->after('importe_penalizacion_profesor');
        });
    }

    public function down(): void
    {
        Schema::table('creditos', function (Blueprint $table) {
            $table->dropColumn([
                'porcentaje_profesor_penalizacion_aplicado',
                'porcentaje_plataforma_penalizacion_aplicado',
                'importe_penalizacion_profesor',
                'importe_penalizacion_plataforma',
            ]);
        });
    }
};
