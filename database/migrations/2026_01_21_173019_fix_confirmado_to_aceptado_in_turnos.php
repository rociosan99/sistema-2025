<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('turnos')
            ->where('estado', 'confirmado')
            ->update(['estado' => 'aceptado']);
    }

    public function down(): void
    {
        DB::table('turnos')
            ->where('estado', 'aceptado')
            ->update(['estado' => 'confirmado']);
    }
};
