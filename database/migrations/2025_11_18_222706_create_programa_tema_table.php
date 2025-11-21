<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('programa_tema', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('programa_id');
            $table->unsignedBigInteger('tema_id');
            $table->timestamps();

            $table->foreign('programa_id')
                ->references('programa_id')->on('programas')
                ->onDelete('cascade');

            $table->foreign('tema_id')
                ->references('tema_id')->on('temas')
                ->onDelete('cascade');

            $table->unique(['programa_id', 'tema_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('programa_tema');
    }
};
