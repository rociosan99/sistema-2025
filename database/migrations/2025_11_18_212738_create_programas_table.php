<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('programas', function (Blueprint $table) {
            $table->bigIncrements('programa_id');

            // 💠 Programa pertenece a un plan de estudio
            $table->unsignedBigInteger('programa_plan_id');

            // 💠 Programa pertenece a una materia
            $table->unsignedBigInteger('programa_materia_id');

            // 💠 Año del programa (ej: 2025)
            $table->unsignedSmallInteger('programa_anio');

            // Descripción opcional
            $table->text('programa_descripcion')->nullable();

            $table->timestamps();

            $table->foreign('programa_plan_id')
                ->references('plan_id')->on('planes_estudio')
                ->onDelete('cascade');

            $table->foreign('programa_materia_id')
                ->references('materia_id')->on('materias')
                ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('programas');
    }
};
