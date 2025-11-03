<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
     public function up(): void
    {
        Schema::create('temas', function (Blueprint $table) {
            $table->bigIncrements('tema_id');
            $table->string('tema_nombre', 350);
            $table->text('tema_descripcion')->nullable();

            // FK auto-referenciada (tema padre)
            $table->unsignedBigInteger('tema_id_tema_padre')->nullable()->index();
            $table->foreign('tema_id_tema_padre')
                ->references('tema_id')->on('temas')
                ->onDelete('set null');   // si borrás el padre, los hijos quedan sin padre

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('temas');
    }
};
