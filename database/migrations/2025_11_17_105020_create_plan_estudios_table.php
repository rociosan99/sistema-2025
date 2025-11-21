<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('planes_estudio', function (Blueprint $table) {
            $table->bigIncrements('plan_id');

            // Relación con carrera
            $table->unsignedBigInteger('plan_carrera_id');

            // Año del plan (ej: 2013)
            $table->unsignedSmallInteger('plan_anio');

            // Descripción opcional
            $table->text('plan_descripcion')->nullable();

            $table->timestamps();

            // FK
            $table->foreign('plan_carrera_id')
                ->references('carrera_id')->on('carreras')
                ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('planes_estudio');
    }
};
