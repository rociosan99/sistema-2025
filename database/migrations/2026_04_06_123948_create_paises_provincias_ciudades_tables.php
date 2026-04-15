<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('paises', function (Blueprint $table) {
            $table->id('pais_id');
            $table->string('pais_nombre', 120)->unique();
            $table->timestamps();
        });

        Schema::create('provincias', function (Blueprint $table) {
            $table->id('provincia_id');
            $table->unsignedBigInteger('pais_id');
            $table->string('provincia_nombre', 120);
            $table->timestamps();

            $table->foreign('pais_id')
                ->references('pais_id')
                ->on('paises')
                ->restrictOnDelete();

            $table->unique(['pais_id', 'provincia_nombre']);
        });

        Schema::create('ciudades', function (Blueprint $table) {
            $table->id('ciudad_id');
            $table->unsignedBigInteger('provincia_id');
            $table->string('ciudad_nombre', 150);
            $table->timestamps();

            $table->foreign('provincia_id')
                ->references('provincia_id')
                ->on('provincias')
                ->restrictOnDelete();

            $table->unique(['provincia_id', 'ciudad_nombre']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ciudades');
        Schema::dropIfExists('provincias');
        Schema::dropIfExists('paises');
    }
};