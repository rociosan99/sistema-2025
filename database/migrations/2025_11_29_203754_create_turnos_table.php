<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('turnos', function (Blueprint $table) {
            $table->id();

            // Quién reserva
            $table->unsignedBigInteger('alumno_id');

            // Con quién toma la clase
            $table->unsignedBigInteger('profesor_id');

            // Qué materia y tema
            $table->unsignedBigInteger('materia_id');
            $table->unsignedBigInteger('tema_id');

            // Cuándo
            $table->date('fecha');
            $table->time('hora_inicio');
            $table->time('hora_fin');

            // Estado del turno (para futuro: agenda automática)
            $table->string('estado')->default('pendiente');

            $table->timestamps();

            // 🔗 Claves foráneas
            $table->foreign('alumno_id')->references('id')->on('users')->cascadeOnDelete();
            $table->foreign('profesor_id')->references('id')->on('users')->cascadeOnDelete();
            $table->foreign('materia_id')->references('materia_id')->on('materias')->cascadeOnDelete();
            $table->foreign('tema_id')->references('tema_id')->on('temas')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('turnos');
    }
};
