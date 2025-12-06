<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('profesor_materia', function (Blueprint $table) {
            // Precio por clase para esa materia con ese profesor
            $table->decimal('precio_por_clase', 10, 2)
                ->nullable()
                ->after('materia_id');
        });
    }

    public function down(): void
    {
        Schema::table('profesor_materia', function (Blueprint $table) {
            $table->dropColumn('precio_por_clase');
        });
    }
};
