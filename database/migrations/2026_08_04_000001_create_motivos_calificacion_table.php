<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('motivos_calificacion', function (Blueprint $table) {
            $table->id();
            $table->string('tipo_evaluado', 30);
            $table->unsignedTinyInteger('estrellas');
            $table->string('descripcion');
            $table->boolean('activo')->default(true);
            $table->unsignedSmallInteger('orden')->default(0);
            $table->timestamps();

            $table->unique(['tipo_evaluado', 'estrellas', 'descripcion'], 'motivos_calificacion_tipo_estrellas_descripcion_unique');
            $table->index(['tipo_evaluado', 'estrellas', 'activo', 'orden'], 'motivos_calificacion_busqueda_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('motivos_calificacion');
    }
};
