<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('carrera_materias', function (Blueprint $table) {
            $table->bigIncrements('carreramateria_id');

            // FKs
            $table->unsignedBigInteger('carreramateria_carrera_id');
            $table->unsignedBigInteger('carreramateria_materia_id'); // <- renombrado para mantener consistencia

            // Evitar duplicados (misma carrera con la misma materia dos veces)
            $table->unique(['carreramateria_carrera_id', 'carreramateria_materia_id'], 'uniq_carrera_materia');

            // Claves foráneas
            $table->foreign('carreramateria_carrera_id')
                ->references('carrera_id')->on('carreras')
                ->onDelete('cascade');

            $table->foreign('carreramateria_materia_id')
                ->references('materia_id')->on('materias')
                ->onDelete('cascade');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('carrera_materias');
    }
};
