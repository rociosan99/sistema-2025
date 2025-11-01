<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('temas', function (Blueprint $table) {
        $table->id();

        $table->string('nombre');
        $table->text('descripcion')->nullable();

        // Columna que referencia a la misma tabla
        $table->unsignedBigInteger('tema_padre_id')->nullable();

        $table->timestamps();

        // Clave foránea autorreferencial
        $table->foreign('tema_padre_id')
            ->references('id')
            ->on('temas');
    });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('temas');
    }
};
