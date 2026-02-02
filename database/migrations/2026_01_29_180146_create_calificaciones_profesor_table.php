<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('calificaciones_profesor', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('turno_id')->unique(); // 1 calificación por turno
            $table->unsignedBigInteger('alumno_id');
            $table->unsignedBigInteger('profesor_id');

            $table->unsignedTinyInteger('estrellas'); // 1..5
            $table->text('comentario')->nullable();

            $table->timestamps();

            $table->foreign('turno_id')->references('id')->on('turnos')->cascadeOnDelete();
            $table->foreign('alumno_id')->references('id')->on('users')->cascadeOnDelete();
            $table->foreign('profesor_id')->references('id')->on('users')->cascadeOnDelete();

            $table->index(['profesor_id', 'estrellas']);
            $table->index(['alumno_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('calificaciones_profesor');
    }
};
