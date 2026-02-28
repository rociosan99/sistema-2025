<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('slot_holds', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('profesor_id');
            $table->date('fecha');
            $table->time('hora_inicio');
            $table->time('hora_fin');

            // motivo: reemplazo | otro
            $table->string('motivo')->default('reemplazo');

            // estado: activo | consumido | expirado
            $table->string('estado')->default('activo');

            $table->timestamp('expires_at')->nullable();

            $table->json('meta')->nullable();

            $table->timestamps();

            $table->foreign('profesor_id')
                ->references('id')->on('users')
                ->cascadeOnDelete();

            $table->index(['profesor_id', 'fecha']);
            $table->index(['estado']);
            $table->index(['expires_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('slot_holds');
    }
};
