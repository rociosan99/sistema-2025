<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('turno_reemplazos', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('turno_cancelado_id');
            $table->unsignedBigInteger('alumno_id');
            $table->unsignedBigInteger('profesor_id');

            $table->unsignedBigInteger('materia_id');
            $table->unsignedBigInteger('tema_id')->nullable();

            $table->date('fecha');
            $table->time('hora_inicio');
            $table->time('hora_fin');

            $table->string('estado')->default('pendiente'); // pendiente|aceptado|rechazado|expirado
            $table->timestamp('expires_at')->nullable();

            $table->timestamps();

            $table->foreign('turno_cancelado_id')->references('id')->on('turnos')->cascadeOnDelete();
            $table->foreign('alumno_id')->references('id')->on('users')->cascadeOnDelete();
            $table->foreign('profesor_id')->references('id')->on('users')->cascadeOnDelete();

            $table->index(['estado']);
            $table->index(['expires_at']);

            // evita invitar dos veces al mismo alumno por el mismo turno
            $table->unique(['turno_cancelado_id', 'alumno_id'], 'uniq_reemplazo_turno_alumno');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('turno_reemplazos');
    }
};