<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->unsignedBigInteger('carrera_activa_id')->nullable()->after('role');

            $table->foreign('carrera_activa_id')
                ->references('carrera_id')->on('carreras')
                ->nullOnDelete();

            $table->index('carrera_activa_id');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['carrera_activa_id']);
            $table->dropIndex(['carrera_activa_id']);
            $table->dropColumn('carrera_activa_id');
        });
    }
};