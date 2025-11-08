<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
     public function up(): void
    {
        Schema::create('carreras', function (Blueprint $table) {
            $table->bigIncrements('carrera_id');
            $table->unsignedBigInteger('carrera_institucion_id')->index();
            $table->string('carrera_nombre', 150);
            $table->text('carrera_descripcion')->nullable();
            $table->timestamps();

            // Clave foránea hacia instituciones
            $table->foreign('carrera_institucion_id')
                ->references('institucion_id')->on('instituciones')
                ->onDelete('cascade'); // si se borra la institución, se borran sus carreras
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('carreras');
    }
};
