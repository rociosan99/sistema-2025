<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('materia_tema', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('materia_id');
            $table->unsignedBigInteger('tema_id');
            $table->timestamps();

            // Claves foráneas
            $table->foreign('materia_id')
                ->references('materia_id')->on('materias')
                ->onDelete('cascade');

            $table->foreign('tema_id')
                ->references('tema_id')->on('temas')
                ->onDelete('cascade');

            $table->unique(['materia_id', 'tema_id']); // evita duplicados
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('materia_tema');
    }
};
