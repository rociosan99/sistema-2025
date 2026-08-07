<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('politicas_cancelacion', function (Blueprint $table) {
            $table->id();
            $table->string('codigo', 50)->unique();
            $table->unsignedSmallInteger('horas_cancelacion_sin_penalizacion');
            $table->decimal('porcentaje_credito_anticipado', 5, 2);
            $table->decimal('porcentaje_credito_tardio', 5, 2);
            $table->decimal('porcentaje_penalizacion_tardia', 5, 2);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('politicas_cancelacion');
    }
};
