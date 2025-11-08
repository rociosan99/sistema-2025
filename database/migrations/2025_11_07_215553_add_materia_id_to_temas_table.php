<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('temas', function (Blueprint $table) {
            // 🔗 Nueva FK hacia materias
            $table->unsignedBigInteger('tema_materia_id')->nullable()->after('tema_id_tema_padre');
            $table->foreign('tema_materia_id')
                ->references('materia_id')->on('materias')
                ->onDelete('cascade'); // Si se elimina la materia, se eliminan los temas
        });
    }

    public function down(): void
    {
        Schema::table('temas', function (Blueprint $table) {
            $table->dropForeign(['tema_materia_id']);
            $table->dropColumn('tema_materia_id');
        });
    }
};
