<?php

namespace Tests\Feature\Pagos;

use App\Http\Controllers\MercadoPagoController;
use App\Models\Credito;
use App\Models\CreditoAplicacion;
use App\Models\Pago;
use App\Models\Turno;
use App\Services\AplicacionCreditoService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use ReflectionMethod;
use Tests\Support\CreatesTurnoScenarios;
use Tests\TestCase;

class PagosYCreditosTest extends TestCase
{
    use CreatesTurnoScenarios;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->travelTo('2026-08-25 10:00:00');
    }

    public function test_pago_total_con_credito_descuenta_saldo_una_sola_vez(): void
    {
        [$turno, $credito] = $this->escenarioCreditoTotal();

        app(AplicacionCreditoService::class)->pagarTotalmenteConCredito($turno, $turno->alumno_id);

        $this->assertSame('0.00', $credito->fresh()->saldo_disponible);
        $this->assertSame('100.00', CreditoAplicacion::query()->where('turno_id', $turno->id)->sole()->importe);
    }

    public function test_pago_total_crea_aplicacion_en_estado_aplicado(): void
    {
        [$turno, $credito] = $this->escenarioCreditoTotal();

        app(AplicacionCreditoService::class)->pagarTotalmenteConCredito($turno, $turno->alumno_id);

        $this->assertDatabaseHas('credito_aplicaciones', [
            'credito_id' => $credito->id,
            'turno_id' => $turno->id,
            'importe' => 100,
            'estado' => CreditoAplicacion::ESTADO_APLICADO,
        ]);
    }

    public function test_pago_total_crea_pago_aprobado_con_provider_credito(): void
    {
        [$turno] = $this->escenarioCreditoTotal();

        $pago = app(AplicacionCreditoService::class)
            ->pagarTotalmenteConCredito($turno, $turno->alumno_id);

        $this->assertSame(Pago::ESTADO_APROBADO, $pago->estado);
        $this->assertSame('credito', $pago->provider);
        $this->assertSame('100.00', $pago->monto);
        $this->assertSame('0.00', $pago->monto_mercadopago);
    }

    public function test_pago_total_con_credito_confirma_turno(): void
    {
        [$turno] = $this->escenarioCreditoTotal();

        app(AplicacionCreditoService::class)->pagarTotalmenteConCredito($turno, $turno->alumno_id);

        $this->assertSame(Turno::ESTADO_CONFIRMADO, $turno->fresh()->estado);
    }

    public function test_repetir_pago_total_no_vuelve_a_descontar_credito(): void
    {
        [$turno, $credito] = $this->escenarioCreditoTotal();
        $service = app(AplicacionCreditoService::class);

        $primerPago = $service->pagarTotalmenteConCredito($turno, $turno->alumno_id);
        $segundoPago = $service->pagarTotalmenteConCredito($turno->fresh(), $turno->alumno_id);

        $this->assertSame($primerPago->pago_id, $segundoPago->pago_id);
        $this->assertSame('0.00', $credito->fresh()->saldo_disponible);
        $this->assertDatabaseCount('credito_aplicaciones', 1);
        $this->assertDatabaseCount('pagos', 1);
    }

    public function test_pago_mixto_reserva_unicamente_credito_disponible(): void
    {
        [$turno, $credito] = $this->escenarioCreditoParcial();

        $composicion = app(AplicacionCreditoService::class)
            ->reservarParaPagoParcial($turno, $turno->alumno_id);

        $this->assertSame('40.00', $composicion['credito_reservado']);
        $this->assertSame('60.00', $composicion['monto_mercadopago']);
        $this->assertSame('0.00', $credito->fresh()->saldo_disponible);
        $this->assertSame(
            CreditoAplicacion::ESTADO_RESERVADO,
            CreditoAplicacion::query()->where('turno_id', $turno->id)->sole()->estado,
        );
    }

    public function test_pago_mixto_conserva_total_y_guarda_solo_diferencia_para_mercado_pago(): void
    {
        [$turno] = $this->escenarioCreditoParcial();

        app(AplicacionCreditoService::class)->reservarParaPagoParcial($turno, $turno->alumno_id);
        $pago = Pago::query()->where('turno_id', $turno->id)->sole();

        $this->assertSame('100.00', $pago->monto);
        $this->assertSame('60.00', $pago->monto_mercadopago);
        $this->assertSame(Pago::ESTADO_PENDIENTE, $pago->estado);
    }

    public function test_aprobacion_mixta_convierte_reserva_en_aplicada_y_confirma(): void
    {
        [$turno] = $this->escenarioCreditoParcial();
        app(AplicacionCreditoService::class)->reservarParaPagoParcial($turno, $turno->alumno_id);
        $pago = Pago::query()->where('turno_id', $turno->id)->sole();

        $resultado = $this->procesarNotificacionMp($turno, $this->pagoMp(
            $pago->external_reference,
            'approved',
            '60.00',
        ));

        $this->assertSame('approved', $resultado['status']);
        $this->assertSame(CreditoAplicacion::ESTADO_APLICADO, $turno->aplicacionesCredito()->sole()->estado);
        $this->assertSame(Pago::ESTADO_APROBADO, $pago->fresh()->estado);
        $this->assertSame(Turno::ESTADO_CONFIRMADO, $turno->fresh()->estado);
    }

    public function test_rechazo_mixto_libera_credito_una_sola_vez(): void
    {
        [$turno, $credito] = $this->escenarioCreditoParcial();
        app(AplicacionCreditoService::class)->reservarParaPagoParcial($turno, $turno->alumno_id);
        $pago = Pago::query()->where('turno_id', $turno->id)->sole();
        $evento = $this->pagoMp($pago->external_reference, 'rejected', '60.00');

        $this->procesarNotificacionMp($turno, $evento);
        $this->procesarNotificacionMp($turno->fresh(), $evento);

        $this->assertSame('40.00', $credito->fresh()->saldo_disponible);
        $this->assertSame(CreditoAplicacion::ESTADO_LIBERADO, $turno->aplicacionesCredito()->sole()->estado);
        $this->assertSame(Pago::ESTADO_RECHAZADO, $pago->fresh()->estado);
        $this->assertSame(Turno::ESTADO_PENDIENTE_PAGO, $turno->fresh()->estado);
    }

    public function test_webhook_aprobado_duplicado_no_duplica_aplicaciones_ni_descuentos(): void
    {
        [$turno, $credito] = $this->escenarioCreditoParcial();
        app(AplicacionCreditoService::class)->reservarParaPagoParcial($turno, $turno->alumno_id);
        $pago = Pago::query()->where('turno_id', $turno->id)->sole();
        $evento = $this->pagoMp($pago->external_reference, 'approved', '60.00');

        $this->procesarNotificacionMp($turno, $evento);
        $this->procesarNotificacionMp($turno->fresh(), $evento);

        $this->assertDatabaseCount('credito_aplicaciones', 1);
        $this->assertSame('0.00', $credito->fresh()->saldo_disponible);
        $this->assertSame(CreditoAplicacion::ESTADO_APLICADO, $turno->aplicacionesCredito()->sole()->estado);
        $this->assertDatabaseCount('pagos', 1);
    }

    public function test_aprobacion_de_intento_anterior_no_confirma_turno_actual(): void
    {
        [$turno] = $this->escenarioCreditoParcial();
        app(AplicacionCreditoService::class)->reservarParaPagoParcial($turno, $turno->alumno_id);
        $pagoVigente = Pago::query()->where('turno_id', $turno->id)->sole();
        $referenciaVieja = "turno:{$turno->id}:intento:00000000-0000-4000-8000-000000000001";

        $resultado = $this->procesarNotificacionMp(
            $turno,
            $this->pagoMp($referenciaVieja, 'approved', '60.00'),
        );

        $this->assertSame('invalid', $resultado['status']);
        $this->assertSame(Pago::ESTADO_PENDIENTE, $pagoVigente->fresh()->estado);
        $this->assertSame(Turno::ESTADO_PENDIENTE_PAGO, $turno->fresh()->estado);
        $this->assertSame(CreditoAplicacion::ESTADO_RESERVADO, $turno->aplicacionesCredito()->sole()->estado);
    }

    public function test_credito_vencido_no_puede_aplicarse(): void
    {
        [$turno, $credito] = $this->escenarioCreditoTotal([
            'vence_at' => now()->subSecond(),
        ]);

        $preview = app(AplicacionCreditoService::class)->previsualizar($turno, $turno->alumno_id);
        $this->assertSame('0.00', $preview['credito_disponible']);

        try {
            app(AplicacionCreditoService::class)->pagarTotalmenteConCredito($turno, $turno->alumno_id);
            $this->fail('Se aplicó un crédito vencido.');
        } catch (ValidationException $exception) {
            $this->assertNotEmpty($exception->errors()['credito'] ?? []);
        }

        $this->assertSame('100.00', $credito->fresh()->saldo_disponible);
        $this->assertDatabaseCount('credito_aplicaciones', 0);
    }

    public function test_credito_de_otro_alumno_no_puede_utilizarse(): void
    {
        $alumnoTurno = $this->crearAlumno();
        $otroAlumno = $this->crearAlumno();
        $profesor = $this->crearProfesor();
        $materia = $this->crearMateria();
        $turno = $this->crearTurno($alumnoTurno, $profesor, $materia, [
            'estado' => Turno::ESTADO_PENDIENTE_PAGO,
        ]);
        $origenAjeno = $this->crearTurno($otroAlumno, $profesor, $materia, [
            'fecha' => '2026-08-20',
            'estado' => Turno::ESTADO_CANCELADO,
        ]);
        $creditoAjeno = $this->crearCreditoDisponible($otroAlumno, $origenAjeno);

        $preview = app(AplicacionCreditoService::class)->previsualizar($turno, $alumnoTurno->id);
        $this->assertSame('0.00', $preview['credito_disponible']);

        try {
            app(AplicacionCreditoService::class)->pagarTotalmenteConCredito($turno, $alumnoTurno->id);
            $this->fail('Se utilizó crédito perteneciente a otro alumno.');
        } catch (ValidationException $exception) {
            $this->assertNotEmpty($exception->errors()['credito'] ?? []);
        }

        $this->assertSame('100.00', $creditoAjeno->fresh()->saldo_disponible);
        $this->assertDatabaseCount('credito_aplicaciones', 0);
    }

    private function escenarioCreditoTotal(array $creditoAttributes = []): array
    {
        return $this->escenarioConCredito('100.00', $creditoAttributes);
    }

    private function escenarioCreditoParcial(): array
    {
        return $this->escenarioConCredito('40.00');
    }

    private function escenarioConCredito(string $saldo, array $creditoAttributes = []): array
    {
        $alumno = $this->crearAlumno();
        $profesor = $this->crearProfesor();
        $materia = $this->crearMateria();
        $turno = $this->crearTurno($alumno, $profesor, $materia, [
            'estado' => Turno::ESTADO_PENDIENTE_PAGO,
            'precio_total' => '100.00',
        ]);
        $origen = $this->crearTurno($alumno, $profesor, $materia, [
            'fecha' => '2026-08-20',
            'estado' => Turno::ESTADO_CANCELADO,
        ]);
        $credito = $this->crearCreditoDisponible($alumno, $origen, array_merge([
            'importe_credito' => $saldo,
            'saldo_disponible' => $saldo,
        ], $creditoAttributes));

        return [$turno, $credito];
    }

    private function pagoMp(string $externalReference, string $estado, string $monto): object
    {
        return (object) [
            'id' => '900001',
            'status' => $estado,
            'status_detail' => $estado === 'approved' ? 'accredited' : 'cc_rejected_other_reason',
            'external_reference' => $externalReference,
            'transaction_amount' => (float) $monto,
            'payment_type_id' => 'credit_card',
            'payment_method_id' => 'visa',
        ];
    }

    private function procesarNotificacionMp(Turno $turno, object $payment): array
    {
        $metodo = new ReflectionMethod(MercadoPagoController::class, 'procesarPagoDesdeObjetoMP');

        return $metodo->invoke(new MercadoPagoController(), $payment, $turno, null);
    }
}
