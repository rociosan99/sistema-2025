<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('profesor_profiles', function (Blueprint $table): void {
            $table->text('enlace_clase_default')
                ->nullable()
                ->after('titulo_profesional');
        });
    }

    public function down(): void
    {
        Schema::table('profesor_profiles', function (Blueprint $table): void {
            $table->dropColumn('enlace_clase_default');
        });
    }
};
