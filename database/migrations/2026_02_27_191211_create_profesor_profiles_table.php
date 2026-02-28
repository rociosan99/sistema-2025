<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('profesor_profiles', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('user_id')->unique();

            $table->string('ciudad')->nullable();

            // Presentación profesional
            $table->text('bio')->nullable();
            $table->unsignedTinyInteger('experiencia_anios')->nullable();
            $table->string('nivel', 20)->nullable(); // junior|semi|senior

            // Precio default
            $table->decimal('precio_por_hora_default', 10, 2)->nullable();

            // Título profesional (ANTES era headline)
            $table->string('titulo_profesional', 180)->nullable();

            $table->timestamps();

            $table->foreign('user_id')
                ->references('id')->on('users')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('profesor_profiles');
    }
};