<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('solicitudes_disponibilidad', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('alumno_id');
            $table->unsignedBigInteger('materia_id');
            $table->unsignedBigInteger('tema_id')->nullable();

            $table->date('fecha');
            $table->time('hora_inicio');
            $table->time('hora_fin');

            $table->string('estado')->default('activa'); // activa | tomada | cancelada | expirada
            $table->timestamp('expires_at')->nullable();

            $table->timestamps();

            $table->foreign('alumno_id')->references('id')->on('users')->cascadeOnDelete();
            $table->foreign('materia_id')->references('materia_id')->on('materias')->cascadeOnDelete();
            $table->foreign('tema_id')->references('tema_id')->on('temas')->nullOnDelete();

            $table->index(['estado']);
            $table->index(['materia_id', 'tema_id']);
            $table->index(['fecha', 'hora_inicio', 'hora_fin']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('solicitudes_disponibilidad');
    }
};
