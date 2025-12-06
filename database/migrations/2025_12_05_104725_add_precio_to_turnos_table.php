<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('turnos', function (Blueprint $table) {
            // Precio por hora al momento de reservar
            $table->decimal('precio_por_hora', 10, 2)
                ->nullable()
                ->after('estado');

            // Precio total de la clase (según duración)
            $table->decimal('precio_total', 10, 2)
                ->nullable()
                ->after('precio_por_hora');
        });
    }

    public function down(): void
    {
        Schema::table('turnos', function (Blueprint $table) {
            $table->dropColumn('precio_por_hora');
            $table->dropColumn('precio_total');
        });
    }
};
