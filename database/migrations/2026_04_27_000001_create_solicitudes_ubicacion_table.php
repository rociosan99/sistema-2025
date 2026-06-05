<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('solicitudes_ubicacion', function (Blueprint $table) {
            $table->id();

            $table->string('tipo', 20);
            $table->string('estado', 20)->default('pendiente');

            $table->unsignedBigInteger('pais_id')->nullable();
            $table->unsignedBigInteger('provincia_id')->nullable();

            $table->string('nombre_pais_solicitado', 120)->nullable();
            $table->string('nombre_provincia_solicitada', 120)->nullable();
            $table->string('nombre_ciudad_solicitada', 150)->nullable();

            $table->text('observacion_solicitante')->nullable();
            $table->text('observacion_admin')->nullable();

            $table->foreignId('solicitado_por_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('revisado_por_id')->nullable()->constrained('users')->nullOnDelete();

            $table->unsignedBigInteger('pais_creado_id')->nullable();
            $table->unsignedBigInteger('provincia_creada_id')->nullable();
            $table->unsignedBigInteger('ciudad_creada_id')->nullable();

            $table->timestamp('revisado_at')->nullable();
            $table->timestamps();

            $table->foreign('pais_id')->references('pais_id')->on('paises')->restrictOnDelete();
            $table->foreign('provincia_id')->references('provincia_id')->on('provincias')->restrictOnDelete();

            $table->foreign('pais_creado_id')->references('pais_id')->on('paises')->nullOnDelete();
            $table->foreign('provincia_creada_id')->references('provincia_id')->on('provincias')->nullOnDelete();
            $table->foreign('ciudad_creada_id')->references('ciudad_id')->on('ciudades')->nullOnDelete();

            $table->index(['estado', 'tipo']);
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('solicitudes_ubicacion');
    }
};
