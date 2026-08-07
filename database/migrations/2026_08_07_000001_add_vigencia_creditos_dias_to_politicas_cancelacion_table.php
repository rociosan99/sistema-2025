<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('politicas_cancelacion', function (Blueprint $table) {
            $table->unsignedSmallInteger('vigencia_creditos_dias')->nullable()->after('porcentaje_penalizacion_tardia');
        });
    }

    public function down(): void
    {
        Schema::table('politicas_cancelacion', function (Blueprint $table) {
            $table->dropColumn('vigencia_creditos_dias');
        });
    }
};
