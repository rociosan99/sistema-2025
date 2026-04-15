<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('profesor_profiles', function (Blueprint $table) {
            $table->unsignedBigInteger('ciudad_id')->nullable()->after('user_id');

            $table->foreign('ciudad_id')
                ->references('ciudad_id')
                ->on('ciudades')
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('profesor_profiles', function (Blueprint $table) {
            $table->dropForeign(['ciudad_id']);
            $table->dropColumn('ciudad_id');
        });
    }
};