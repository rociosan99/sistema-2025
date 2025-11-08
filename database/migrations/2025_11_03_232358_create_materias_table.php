<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('materias', function (Blueprint $table) {
            $table->bigIncrements('materia_id');
            $table->string('materia_nombre', 150);
            $table->text('materia_descripcion')->nullable();
            $table->unsignedSmallInteger('materia_anio'); // 2024, 2025, etc.
            $table->timestamps();

            $table->index('materia_anio');
            // Si querés evitar duplicados por nombre + año:
            // $table->unique(['materia_nombre', 'materia_anio']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('materias');
    }
};
