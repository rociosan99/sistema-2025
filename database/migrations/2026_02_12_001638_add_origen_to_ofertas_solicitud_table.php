<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('ofertas_solicitud', function (Blueprint $table) {
            $table->string('origen')->default('batch')->after('estado'); // batch | slot
        });
    }

    public function down(): void
    {
        Schema::table('ofertas_solicitud', function (Blueprint $table) {
            $table->dropColumn('origen');
        });
    }
};
