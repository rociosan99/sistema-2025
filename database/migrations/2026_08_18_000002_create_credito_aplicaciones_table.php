<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('credito_aplicaciones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('credito_id')
                ->constrained('creditos')
                ->restrictOnDelete();
            $table->foreignId('turno_id')
                ->constrained('turnos')
                ->restrictOnDelete();
            $table->decimal('importe', 10, 2);
            $table->string('estado', 20);
            $table->string('idempotency_key', 150)->unique();
            $table->timestamps();

            $table->unique(['credito_id', 'turno_id']);
            $table->index(['turno_id', 'estado']);
            $table->index(['credito_id', 'estado']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('credito_aplicaciones');
    }
};
