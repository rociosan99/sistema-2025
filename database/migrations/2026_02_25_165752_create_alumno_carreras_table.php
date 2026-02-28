<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('alumno_carreras', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('alumno_id');
            $table->unsignedBigInteger('carrera_id');
            $table->timestamps();

            $table->foreign('alumno_id')->references('id')->on('users')->cascadeOnDelete();
            $table->foreign('carrera_id')->references('carrera_id')->on('carreras')->cascadeOnDelete();

            $table->unique(['alumno_id', 'carrera_id']);
            $table->index(['alumno_id', 'carrera_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('alumno_carreras');
    }
};