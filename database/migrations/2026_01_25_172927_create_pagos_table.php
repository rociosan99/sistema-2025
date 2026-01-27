<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pagos', function (Blueprint $table) {
            $table->bigIncrements('pago_id');

            $table->unsignedBigInteger('turno_id');

            // Interno
            $table->decimal('monto', 10, 2)->nullable();
            $table->string('moneda', 10)->default('ARS');
            $table->string('estado', 50)->default('pendiente'); // pendiente/aprobado/rechazado/error
            $table->string('provider', 50)->default('mercadopago');

            // Mercado Pago (Checkout Pro)
            $table->string('mp_preference_id')->nullable();
            $table->text('mp_init_point')->nullable();

            // Datos del pago real (webhook)
            $table->string('mp_payment_id')->nullable();
            $table->string('mp_status')->nullable();
            $table->string('mp_status_detail')->nullable();

            // Método (opcional)
            $table->string('mp_payment_type')->nullable();
            $table->string('mp_payment_method')->nullable();

            // Para mapear
            $table->string('external_reference')->nullable(); // "turno:123"

            // Auditoría
            $table->json('detalle_externo')->nullable();
            $table->timestamp('fecha_aprobado')->nullable();

            $table->timestamps();

            // FK + índices
            $table->foreign('turno_id')
                ->references('id')->on('turnos')
                ->cascadeOnDelete();

            $table->unique(['provider', 'mp_preference_id']);
            $table->index(['provider', 'mp_payment_id']);
            $table->index(['turno_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pagos');
    }
};
