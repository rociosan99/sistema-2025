<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('profesor_profiles', function (Blueprint $table) {
            $table->dropColumn('ciudad');
        });
    }

    public function down(): void
    {
        Schema::table('profesor_profiles', function (Blueprint $table) {
            $table->string('ciudad')->nullable();
        });
    }
};