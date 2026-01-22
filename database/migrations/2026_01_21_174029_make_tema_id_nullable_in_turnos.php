<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('turnos', function (Blueprint $table) {
            // Soltamos FK actual (si existe)
            $table->dropForeign(['tema_id']);

            // Nullable
            $table->unsignedBigInteger('tema_id')->nullable()->change();

            // Nueva FK (si borran el tema, deja null)
            $table->foreign('tema_id')
                ->references('tema_id')
                ->on('temas')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('turnos', function (Blueprint $table) {
            $table->dropForeign(['tema_id']);

            $table->unsignedBigInteger('tema_id')->nullable(false)->change();

            $table->foreign('tema_id')
                ->references('tema_id')
                ->on('temas')
                ->cascadeOnDelete();
        });
    }
};
