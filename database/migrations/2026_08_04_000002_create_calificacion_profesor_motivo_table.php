<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('calificacion_profesor_motivo', function (Blueprint $table) {
            $table->foreignId('calificacion_profesor_id')->constrained('calificaciones_profesor')->cascadeOnDelete();
            $table->foreignId('motivo_calificacion_id')->constrained('motivos_calificacion')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['calificacion_profesor_id', 'motivo_calificacion_id'], 'calificacion_profesor_motivo_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('calificacion_profesor_motivo');
    }
};
