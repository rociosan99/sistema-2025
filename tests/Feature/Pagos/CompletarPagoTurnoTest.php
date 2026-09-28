<?php

namespace Tests\Feature\Pagos;

use App\Filament\Alumno\Pages\CompletarPagoTurno;
use App\Filament\Alumno\Resources\Turnos\TurnoResource;
use App\Models\Credito;
use App\Models\CreditoAplicacion;
use App\Models\Pago;
use App\Models\Turno;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Tests\Support\CreatesTurnoScenarios;
use Tests\TestCase;

class CompletarPagoTurnoTest extends TestCase
{
    use CreatesTurnoScenarios;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->travelTo('2026-09-27 10:00:00');
        Filament::setCurrentPanel(Filament::getPanel('alumno'));
    }

    public function test_muestra_el_resumen_del_turno_sin_crear_pago_ni_cambiar_su_estado(): void
    {
        [$alumno, $turno, $credito] = $this->crearEscenario();
        $pagosAntes = Pago::query()->count();

        $response = $this->actingAs($alumno)->get(
            CompletarPagoTurno::getUrl(['record' => $turno->id], panel: 'alumno'),
        );

        $response
            ->assertOk()
            ->assertSee('completar-pago-page', false)
            ->assertSee('completar-pago-card', false)
            ->assertSee('completar-pago-turno-row', false)
            ->assertSee('completar-pago-class-grid', false)
            ->assertSee('completar-pago-summary', false)
            ->assertSee("Turno #{$turno->id}")
            ->assertSee('Pendiente de pago')
            ->assertSee('Lucía Gómez')
            ->assertSee('Bases de Datos')
            ->assertSee('28/09/2026')
            ->assertSee('14:00 - 15:00')
            ->assertSee('1 hora')
            ->assertSee('$100,00')
            ->assertSee('Crédito disponible')
            ->assertSee('$40,00')
            ->assertSee('Crédito a utilizar')
            ->assertSee('$0,00')
            ->assertSee('Restante a pagar')
            ->assertSee('Pagar $100,00 con Mercado Pago');

        $this->assertSame($pagosAntes, Pago::query()->count());
        $this->assertSame(Turno::ESTADO_PENDIENTE_PAGO, $turno->fresh()->estado);
        $this->assertSame('40.00', $credito->fresh()->saldo_disponible);
    }

    public function test_otro_alumno_no_puede_visualizar_el_resumen(): void
    {
        [, $turno] = $this->crearEscenario();
        $otroAlumno = $this->crearAlumno();

        $this->actingAs($otroAlumno)
            ->get(CompletarPagoTurno::getUrl(['record' => $turno->id], panel: 'alumno'))
            ->assertNotFound();
    }

    public function test_pago_total_muestra_modal_despues_de_confirmar_y_redirige_al_aceptar(): void
    {
        [$alumno, $turno, $credito] = $this->crearEscenario();
        $turno->update(['precio_total' => '30.00']);
        $credito->update(['importe_credito' => '158.50', 'saldo_disponible' => '158.50']);

        $pagina = Livewire::actingAs($alumno)->test(CompletarPagoTurno::class, ['record' => $turno->id])
            ->set('usarCredito', true)
            ->assertSet('resumen.diferencia', '0.00')
            ->call('confirmarPagoConCredito')
            ->assertSet('mostrarModalExito', true)
            ->assertSee('¡Pago realizado con éxito!')
            ->assertSee('Tu clase quedó confirmada.')
            ->assertSee('Aceptar')
            ->assertNoRedirect();

        $this->assertSame('128.50', $credito->fresh()->saldo_disponible);
        $this->assertSame(Turno::ESTADO_CONFIRMADO, $turno->fresh()->estado);
        $this->assertDatabaseHas('pagos', [
            'turno_id' => $turno->id, 'estado' => Pago::ESTADO_APROBADO,
            'provider' => 'credito', 'monto' => 30, 'monto_mercadopago' => 0,
        ]);
        $this->assertDatabaseHas('credito_aplicaciones', [
            'turno_id' => $turno->id, 'credito_id' => $credito->id,
            'importe' => 30, 'estado' => CreditoAplicacion::ESTADO_APLICADO,
        ]);

        // Un segundo submit conserva el mismo modal y no vuelve a aplicar el crédito.
        $pagina->call('confirmarPagoConCredito')->assertSet('mostrarModalExito', true);
        $this->assertSame('128.50', $credito->fresh()->saldo_disponible);
        $this->assertSame(1, Pago::where('turno_id', $turno->id)->count());
        $this->assertSame(1, CreditoAplicacion::where('turno_id', $turno->id)->count());

        $pagina->call('cerrarModalExito')
            ->assertSet('mostrarModalExito', false)
            ->assertRedirect(TurnoResource::getUrl('index', panel: 'alumno'));

        // Una nueva visita (recarga o volver atrás) no reconstruye el éxito.
        Livewire::test(CompletarPagoTurno::class, ['record' => $turno->id])
            ->assertSet('mostrarModalExito', false)
            ->assertRedirect(TurnoResource::getUrl('index', panel: 'alumno'));
    }

    public function test_credito_parcial_continua_a_mercado_pago_sin_modal_de_exito(): void
    {
        [$alumno, $turno, $credito] = $this->crearEscenario();
        $credito->update(['importe_credito' => '30.00', 'saldo_disponible' => '30.00']);

        Livewire::actingAs($alumno)->test(CompletarPagoTurno::class, ['record' => $turno->id])
            ->set('usarCredito', true)
            ->call('continuarConPagoMixto')
            ->assertSet('mostrarModalExito', false)
            ->assertRedirect(route('mp.pagar', ['turno' => $turno->id]));

        $this->assertSame('0.00', $credito->fresh()->saldo_disponible);
        $this->assertSame(Turno::ESTADO_PENDIENTE_PAGO, $turno->fresh()->estado);
        $this->assertDatabaseHas('pagos', [
            'turno_id' => $turno->id, 'estado' => Pago::ESTADO_PENDIENTE,
            'provider' => 'mercadopago', 'monto' => 100, 'monto_mercadopago' => 70,
        ]);
        $this->assertDatabaseHas('credito_aplicaciones', [
            'turno_id' => $turno->id, 'importe' => 30,
            'estado' => CreditoAplicacion::ESTADO_RESERVADO,
        ]);
    }

    public function test_sin_usar_credito_conserva_enlace_a_mercado_pago_y_no_aplica_creditos(): void
    {
        [$alumno, $turno, $credito] = $this->crearEscenario();

        Livewire::actingAs($alumno)->test(CompletarPagoTurno::class, ['record' => $turno->id])
            ->assertSeeHtml('href="'.route('mp.pagar', ['turno' => $turno->id]).'"')
            ->assertSee('Pagar $100,00 con Mercado Pago')
            ->call('confirmarPagoConCredito')
            ->assertSet('mostrarModalExito', false)
            ->assertDontSee('¡Pago realizado con éxito!')
            ->assertNoRedirect();

        $this->assertSame('40.00', $credito->fresh()->saldo_disponible);
        $this->assertNull($turno->fresh()->pago);
    }

    public function test_error_durante_aplicacion_revierte_cambios_y_no_muestra_exito(): void
    {
        [$alumno, $turno, $credito] = $this->crearEscenario();
        $turno->update(['precio_total' => '30.00']);

        // Falla después de descontar el saldo, dentro de la transacción real.
        CreditoAplicacion::creating(function (): void {
            throw ValidationException::withMessages(['credito' => 'Error de prueba al aplicar crédito.']);
        });

        try {
            Livewire::actingAs($alumno)->test(CompletarPagoTurno::class, ['record' => $turno->id])
                ->set('usarCredito', true)
                ->call('confirmarPagoConCredito')
                ->assertSet('mostrarModalExito', false)
                ->assertDontSee('¡Pago realizado con éxito!')
                ->assertNotified('No se pudo completar el pago')
                ->assertNoRedirect();

            $this->assertSame('40.00', $credito->fresh()->saldo_disponible);
            $this->assertSame(Turno::ESTADO_PENDIENTE_PAGO, $turno->fresh()->estado);
            $this->assertNull($turno->fresh()->pago);
            $this->assertSame(0, CreditoAplicacion::where('turno_id', $turno->id)->count());
        } finally {
            CreditoAplicacion::flushEventListeners();
        }
    }

    public function test_abrir_y_recargar_sin_operar_no_muestra_exito(): void
    {
        [$alumno, $turno] = $this->crearEscenario();

        for ($visita = 0; $visita < 2; $visita++) {
            Livewire::actingAs($alumno)->test(CompletarPagoTurno::class, ['record' => $turno->id])
                ->assertSet('mostrarModalExito', false)
                ->assertDontSee('¡Pago realizado con éxito!')
                ->call('cerrarModalExito')
                ->assertNoRedirect();
        }

        $this->assertNull($turno->fresh()->pago);
    }

    /** @return array{User, Turno, Credito} */
    private function crearEscenario(): array
    {
        $alumno = $this->crearAlumno();
        $profesor = $this->crearProfesor([
            'name' => 'Lucía',
            'apellido' => 'Gómez',
        ]);
        $materia = $this->crearMateria([
            'materia_nombre' => 'Bases de Datos',
        ]);
        $turno = $this->crearTurno($alumno, $profesor, $materia, [
            'fecha' => '2026-09-28',
            'hora_inicio' => '14:00:00',
            'hora_fin' => '15:00:00',
            'estado' => Turno::ESTADO_PENDIENTE_PAGO,
            'precio_total' => '100.00',
        ]);
        $turnoOrigen = $this->crearTurno($alumno, $profesor, $materia, [
            'fecha' => '2026-09-20',
            'estado' => Turno::ESTADO_CANCELADO,
        ]);
        $credito = $this->crearCreditoDisponible($alumno, $turnoOrigen, [
            'importe_credito' => '40.00',
            'saldo_disponible' => '40.00',
        ]);

        return [$alumno, $turno, $credito];
    }
}
