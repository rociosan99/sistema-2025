<?php

namespace Tests\Feature\Pagos;

use App\Filament\Alumno\Pages\CompletarPagoTurno;
use App\Models\Credito;
use App\Models\Pago;
use App\Models\Turno;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
