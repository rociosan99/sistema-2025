<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('ofertas_solicitud', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('solicitud_id');
            $table->unsignedBigInteger('profesor_id');

            $table->string('estado')->default('pendiente'); // pendiente | aceptada | rechazada | expirada
            $table->timestamp('expires_at');

            $table->timestamps();

            $table->foreign('solicitud_id')->references('id')->on('solicitudes_disponibilidad')->cascadeOnDelete();
            $table->foreign('profesor_id')->references('id')->on('users')->cascadeOnDelete();

            $table->unique(['solicitud_id', 'profesor_id']); // no repetir oferta
            $table->index(['estado']);
            $table->index(['expires_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ofertas_solicitud');
    }
};
