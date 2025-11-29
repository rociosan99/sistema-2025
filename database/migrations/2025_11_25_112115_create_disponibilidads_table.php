<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('disponibilidades', function (Blueprint $table) {
            $table->id();

            // Profesor que define la disponibilidad
            $table->unsignedBigInteger('profesor_id');

            // 1 = Lunes, ... 7 = Domingo
            $table->unsignedTinyInteger('dia_semana');

            // Rango horario
            $table->time('hora_inicio');
            $table->time('hora_fin');

            $table->timestamps();

            // Clave foránea
            $table->foreign('profesor_id')
                ->references('id')
                ->on('users')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('disponibilidades');
    }
};
