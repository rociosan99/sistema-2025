<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('profesor_materia', function (Blueprint $table) {
            // Solo la creamos si no existe
            if (! Schema::hasColumn('profesor_materia', 'precio_por_hora')) {
                $table->decimal('precio_por_hora', 10, 2)
                    ->nullable()
                    ->after('materia_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('profesor_materia', function (Blueprint $table) {
            if (Schema::hasColumn('profesor_materia', 'precio_por_hora')) {
                $table->dropColumn('precio_por_hora');
            }
        });
    }
};
