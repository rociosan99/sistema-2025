<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('paises', function (Blueprint $table) {
            $table->string('codigo_externo', 20)->nullable()->after('pais_nombre');
            $table->index('codigo_externo');
        });

        Schema::table('provincias', function (Blueprint $table) {
            $table->string('codigo_externo', 30)->nullable()->after('provincia_nombre');
            $table->index(['pais_id', 'codigo_externo']);
        });

        Schema::table('ciudades', function (Blueprint $table) {
            $table->string('codigo_externo', 40)->nullable()->after('ciudad_nombre');
            $table->index(['provincia_id', 'codigo_externo']);
        });
    }

    public function down(): void
    {
        Schema::table('ciudades', function (Blueprint $table) {
            $table->dropIndex(['provincia_id', 'codigo_externo']);
            $table->dropColumn('codigo_externo');
        });

        Schema::table('provincias', function (Blueprint $table) {
            $table->dropIndex(['pais_id', 'codigo_externo']);
            $table->dropColumn('codigo_externo');
        });

        Schema::table('paises', function (Blueprint $table) {
            $table->dropIndex(['codigo_externo']);
            $table->dropColumn('codigo_externo');
        });
    }
};
