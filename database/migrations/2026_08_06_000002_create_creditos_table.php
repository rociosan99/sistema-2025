<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('creditos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('alumno_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('turno_id')->constrained('turnos')->restrictOnDelete();
            $table->unsignedBigInteger('pago_id')->nullable();
            $table->decimal('importe_pagado', 10, 2)->default(0);
            $table->decimal('importe_credito', 10, 2)->default(0);
            $table->decimal('importe_penalizacion', 10, 2)->default(0);
            $table->decimal('saldo_disponible', 10, 2)->default(0);
            $table->decimal('porcentaje_credito_aplicado', 5, 2);
            $table->decimal('porcentaje_penalizacion_aplicado', 5, 2);
            $table->unsignedSmallInteger('horas_limite_aplicadas');
            $table->string('estado', 30);
            $table->string('idempotency_key', 150)->unique();
            $table->timestamp('cancelado_at');
            $table->timestamps();

            $table->foreign('pago_id')
                ->references('pago_id')
                ->on('pagos')
                ->restrictOnDelete();

            $table->unique('turno_id');
            $table->unique('pago_id');
            $table->index(['alumno_id', 'estado']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('creditos');
    }
};
