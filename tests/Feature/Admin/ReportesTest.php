<?php

namespace Tests\Feature\Admin;

use App\Filament\Pages\Reportes;
use App\Models\Turno;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Livewire\Livewire;
use Tests\Support\CreatesTurnoScenarios;
use Tests\TestCase;

class ReportesTest extends TestCase
{
    use CreatesTurnoScenarios;
    use RefreshDatabase;

    private User $administrador;

    protected function setUp(): void
    {
        parent::setUp();

        $this->administrador = User::factory()->create([
            'name' => 'Ana',
            'apellido' => 'Administradora',
            'email' => 'ana.admin@example.test',
            'role' => 'admin',
            'activo' => true,
        ]);

        $this->actingAs($this->administrador);
        Filament::setCurrentPanel(Filament::getPanel('admin'));
        $this->travelTo(Carbon::parse('2026-09-25 22:30:00', config('app.timezone')));
    }

    protected function tearDown(): void
    {
        $this->travelBack();
        parent::tearDown();
    }

    public function test_mantiene_los_datos_originales_y_aplica_los_filtros_reales(): void
    {
        $alumno = $this->crearAlumno(['name' => 'Micaela', 'apellido' => 'Sosa']);
        $profesor = $this->crearProfesor(['name' => 'Lucía', 'apellido' => 'Gómez']);
        $materia = $this->crearMateria(['materia_nombre' => 'Bases de Datos']);
        $incluido = $this->crearTurno($alumno, $profesor, $materia, [
            'fecha' => '2026-09-20',
            'hora_inicio' => '10:00:00',
            'hora_fin' => '11:00:00',
            'estado' => Turno::ESTADO_CONFIRMADO,
            'precio_total' => '201.00',
        ]);
        $excluido = $this->crearTurno($alumno, $profesor, $materia, [
            'fecha' => '2026-09-20',
            'estado' => Turno::ESTADO_RECHAZADO,
        ]);

        $componente = Livewire::test(Reportes::class)
            ->set('fechaInicio', '2026-09-01')
            ->set('fechaFin', '2026-09-25')
            ->set('estado', Turno::ESTADO_CONFIRMADO)
            ->call('aplicarFiltros')
            ->assertSee('Micaela')
            ->assertSee('Sosa')
            ->assertSee('Lucía')
            ->assertSee('Gómez')
            ->assertSee('Bases de Datos')
            ->assertSee('20/09/2026')
            ->assertSee('$201,00');

        $this->assertSame([$incluido->id], array_column($componente->get('turnos'), 'id'));
        $this->assertNotContains($excluido->id, array_column($componente->get('turnos'), 'id'));
    }

    public function test_muestra_identificacion_periodo_criterios_y_resumen_formales(): void
    {
        Livewire::test(Reportes::class)
            ->set('fechaInicio', '2026-09-01')
            ->set('fechaFin', '2026-09-25')
            ->set('estado', Turno::ESTADO_PENDIENTE)
            ->call('aplicarFiltros')
            ->assertSee((string) config('app.name'))
            ->assertSee('REPORTE DE TURNOS')
            ->assertSee('Reporte de turnos')
            ->assertSee('Ana Administradora')
            ->assertSee('Rol:')
            ->assertSee('Administrador')
            ->assertSee('25/09/2026')
            ->assertSee('22:30')
            ->assertSee('01/09/2026 al 25/09/2026')
            ->assertSee('Estado:')
            ->assertSee('Pendiente')
            ->assertSee('Total de registros:')
            ->assertSee('0');
    }

    public function test_el_emisor_proviene_del_usuario_autenticado_y_no_de_parametros(): void
    {
        $response = $this->get(Reportes::getUrl([
            'emitido_por' => 'Nombre Falsificado',
        ], panel: 'admin'));

        $response->assertOk()
            ->assertSee('Ana Administradora')
            ->assertDontSee('Nombre Falsificado');
    }

    public function test_preparar_impresion_actualiza_el_momento_y_dispara_el_evento(): void
    {
        $this->travelTo(Carbon::parse('2026-09-25 22:45:00', config('app.timezone')));

        Livewire::test(Reportes::class)
            ->call('prepararImpresion')
            ->assertSet('emitidoEn', now()->toIso8601String())
            ->assertDispatched('imprimir-reporte')
            ->assertSee('25/09/2026')
            ->assertSee('22:45');
    }
}
