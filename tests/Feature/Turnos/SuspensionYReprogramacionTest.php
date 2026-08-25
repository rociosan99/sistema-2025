<?php

namespace Tests\Feature\Turnos;

use App\Filament\Alumno\Pages\ReprogramarTurno;
use App\Filament\Profesor\Resources\Turnos\Pages\ListTurnos;
use App\Models\Credito;
use App\Models\Pago;
use App\Models\Turno;
use App\Services\CreditoService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Route;
use Livewire\Livewire;
use Tests\Support\CreatesTurnoScenarios;
use Tests\TestCase;

class SuspensionYReprogramacionTest extends TestCase
{
    use CreatesTurnoScenarios;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->travelTo('2026-08-25 10:00:00');
        Mail::fake();
        Queue::fake();

        if (! Route::has('filament.admin.pages.mi-perfil')) {
            Route::get('/testing/mi-perfil', fn () => response()->noContent())
                ->name('filament.admin.pages.mi-perfil');
        }

        if (! Route::has('filament.admin.resources.turnos.view')) {
            Route::get('/testing/turnos/{record}', fn () => response()->noContent())
                ->name('filament.admin.resources.turnos.view');
        }

        if (! Route::has('filament.admin.resources.turnos.index')) {
            Route::get('/testing/turnos', fn () => response()->noContent())
                ->name('filament.admin.resources.turnos.index');
        }
    }

    public function test_suspension_anticipada_genera_credito_segun_politica(): void
    {
        [$turno, $pago, $politica] = $this->escenarioCancelacion([
            'fecha' => '2026-08-27',
            'hora_inicio' => '11:00:00',
            'hora_fin' => '12:00:00',
        ]);

        $this->suspenderComoAlumno($turno, $politica);

        $credito = Credito::query()->where('turno_id', $turno->id)->sole();

        $this->assertSame(Turno::ESTADO_CANCELADO, $turno->fresh()->estado);
        $this->assertSame('sin_cargo', $turno->fresh()->cancelacion_tipo);
        $this->assertSame(Credito::ESTADO_DISPONIBLE, $credito->estado);
        $this->assertSame($pago->pago_id, $credito->pago_id);
        $this->assertSame('100.00', $credito->importe_credito);
        $this->assertSame('0.00', $credito->importe_penalizacion);
        $this->assertSame('100.00', $credito->saldo_disponible);
    }

    public function test_suspension_tardia_aplica_credito_y_penalizacion_de_la_politica(): void
    {
        [$turno, , $politica] = $this->escenarioCancelacion([
            'fecha' => '2026-08-25',
            'hora_inicio' => '20:00:00',
            'hora_fin' => '21:00:00',
        ]);

        $this->suspenderComoAlumno($turno, $politica);

        $credito = Credito::query()->where('turno_id', $turno->id)->sole();

        $this->assertSame('con_cargo', $turno->fresh()->cancelacion_tipo);
        $this->assertSame('75.00', $credito->importe_credito);
        $this->assertSame('25.00', $credito->importe_penalizacion);
        $this->assertSame('20.00', $credito->importe_penalizacion_profesor);
        $this->assertSame('5.00', $credito->importe_penalizacion_plataforma);
        $hold = \App\Models\SlotHold::query()->where('profesor_id', $turno->profesor_id)->sole();
        $this->assertSame('2026-08-25', $hold->fecha->format('Y-m-d'));
        $this->assertSame('activo', $hold->estado);
    }

    public function test_suspender_dos_veces_no_genera_dos_creditos(): void
    {
        [$turno, , $politica] = $this->escenarioCancelacion();

        $this->suspenderComoAlumno($turno, $politica);
        $this->suspenderComoAlumno($turno->fresh(), $politica);

        $this->assertDatabaseCount('creditos', 1);
        $this->assertSame(Turno::ESTADO_CANCELADO, $turno->fresh()->estado);
    }

    public function test_suspension_sin_pago_aprobado_no_genera_credito_utilizable(): void
    {
        [$turno, , $politica] = $this->escenarioCancelacion(crearPago: false);

        $this->suspenderComoAlumno($turno, $politica);

        $credito = Credito::query()->where('turno_id', $turno->id)->sole();

        $this->assertSame(Credito::ESTADO_NO_APLICA, $credito->estado);
        $this->assertSame('0.00', $credito->importe_credito);
        $this->assertSame('0.00', $credito->saldo_disponible);
    }

    public function test_profesor_solo_puede_suspender_confirmado_pagado_y_no_finalizado(): void
    {
        $alumno = $this->crearAlumno();
        $profesor = $this->crearProfesor();
        $materia = $this->crearMateria();
        $turnoValido = $this->crearTurno($alumno, $profesor, $materia, [
            'estado' => Turno::ESTADO_CONFIRMADO,
        ]);
        $this->crearPago($turnoValido);

        $this->actingAs($profesor);

        Livewire::test(ListTurnos::class)
            ->assertTableActionVisible('suspender', $turnoValido)
            ->callTableAction('suspender', $turnoValido, [
                'suspension_motivo' => 'No podré dictar la clase.',
            ]);

        $this->assertDatabaseHas('turnos', [
            'id' => $turnoValido->id,
            'estado' => Turno::ESTADO_SUSPENDIDO_PROFESOR,
            'suspendido_por_id' => $profesor->id,
            'suspension_motivo' => 'No podré dictar la clase.',
        ]);

        foreach ([
            Turno::ESTADO_PENDIENTE,
            Turno::ESTADO_ACEPTADO,
            Turno::ESTADO_PENDIENTE_PAGO,
            Turno::ESTADO_CANCELADO,
            Turno::ESTADO_RECHAZADO,
            Turno::ESTADO_VENCIDO,
            Turno::ESTADO_SUSPENDIDO_PROFESOR,
        ] as $estado) {
            $turnoNoValido = $this->crearTurno($alumno, $profesor, $materia, [
                'fecha' => '2026-08-28',
                'hora_inicio' => '12:00:00',
                'hora_fin' => '13:00:00',
                'estado' => $estado,
            ]);
            $this->crearPago($turnoNoValido);

            Livewire::test(ListTurnos::class)
                ->assertTableActionHidden('suspender', $turnoNoValido);
        }

        $confirmadoSinPago = $this->crearTurno($alumno, $profesor, $materia, [
            'hora_inicio' => '14:00:00',
            'hora_fin' => '15:00:00',
            'estado' => Turno::ESTADO_CONFIRMADO,
        ]);

        Livewire::test(ListTurnos::class)
            ->assertTableActionHidden('suspender', $confirmadoSinPago);
    }

    public function test_profesor_no_puede_suspender_turno_confirmado_ya_finalizado(): void
    {
        $alumno = $this->crearAlumno();
        $profesor = $this->crearProfesor();
        $materia = $this->crearMateria();
        $turno = $this->crearTurno($alumno, $profesor, $materia, [
            'fecha' => '2026-08-25',
            'hora_inicio' => '08:00:00',
            'hora_fin' => '09:00:00',
            'estado' => Turno::ESTADO_CONFIRMADO,
        ]);
        $this->crearPago($turno);

        $this->actingAs($profesor);

        Livewire::test(ListTurnos::class)
            ->assertTableActionHidden('suspender', $turno);

        $this->assertSame(Turno::ESTADO_CONFIRMADO, $turno->fresh()->estado);
        $this->assertNull($turno->fresh()->suspendido_at);
    }

    public function test_turno_confirmado_pagado_con_anticipacion_puede_reprogramarse(): void
    {
        [$turno, $pago] = $this->escenarioReprogramacion();

        $this->reprogramar($turno, $this->slotNuevo($turno));

        $nuevo = Turno::query()->findOrFail($turno->fresh()->reprogramado_por_turno_id);

        $this->assertSame(Turno::ESTADO_CANCELADO, $turno->fresh()->estado);
        $this->assertSame(Turno::ESTADO_CONFIRMADO, $nuevo->estado);
        $this->assertSame($nuevo->id, $pago->fresh()->turno_id);
    }

    public function test_reprogramacion_traslada_el_mismo_pago_sin_crear_otro(): void
    {
        [$turno, $pago] = $this->escenarioReprogramacion();

        $this->reprogramar($turno, $this->slotNuevo($turno));

        $nuevoId = $turno->fresh()->reprogramado_por_turno_id;

        $this->assertDatabaseCount('pagos', 1);
        $this->assertSame($pago->pago_id, Pago::query()->where('turno_id', $nuevoId)->sole()->pago_id);
        $this->assertDatabaseMissing('pagos', ['turno_id' => $turno->id]);
    }

    public function test_reprogramacion_con_mismo_profesor_conserva_enlace_congelado(): void
    {
        [$turno] = $this->escenarioReprogramacion();
        $enlaceCongelado = $turno->enlace_clase;

        $turno->profesor->profesorProfile()->update([
            'enlace_clase_default' => 'https://meet.google.com/enlace-nuevo-perfil',
        ]);

        $this->reprogramar($turno, $this->slotNuevo($turno));

        $nuevo = Turno::query()->findOrFail($turno->fresh()->reprogramado_por_turno_id);
        $this->assertSame($enlaceCongelado, $nuevo->enlace_clase);
    }

    public function test_reprogramacion_con_otro_profesor_usa_su_enlace_predeterminado(): void
    {
        [$turno] = $this->escenarioReprogramacion();
        $nuevoEnlace = 'https://meet.google.com/profesor-nuevo';
        $profesorNuevo = $this->crearProfesor(enlace: $nuevoEnlace);

        $this->reprogramar($turno, $this->slotNuevo($turno, $profesorNuevo->id));

        $nuevo = Turno::query()->findOrFail($turno->fresh()->reprogramado_por_turno_id);
        $this->assertSame($profesorNuevo->id, $nuevo->profesor_id);
        $this->assertSame($nuevoEnlace, $nuevo->enlace_clase);
    }

    public function test_turno_ya_reprogramado_no_puede_usarse_nuevamente_como_origen(): void
    {
        [$turno] = $this->escenarioReprogramacion();
        $this->reprogramar($turno, $this->slotNuevo($turno));

        Livewire::withQueryParams(['turno' => $turno->id])
            ->actingAs($turno->alumno)
            ->test(ReprogramarTurno::class)
            ->assertSet('turnoOriginal', null)
            ->assertSet('errorMensaje', 'Este turno ya fue reprogramado.');

        $this->assertDatabaseCount('turnos', 2);
    }

    public function test_pendiente_y_pendiente_pago_no_pueden_ser_origen_de_reprogramacion(): void
    {
        $alumno = $this->crearAlumno();
        $profesor = $this->crearProfesor();
        $materia = $this->crearMateria();

        foreach ([Turno::ESTADO_PENDIENTE, Turno::ESTADO_PENDIENTE_PAGO] as $estado) {
            $turno = $this->crearTurno($alumno, $profesor, $materia, [
                'estado' => $estado,
                'hora_inicio' => $estado === Turno::ESTADO_PENDIENTE ? '10:00:00' : '12:00:00',
                'hora_fin' => $estado === Turno::ESTADO_PENDIENTE ? '11:00:00' : '13:00:00',
            ]);

            Livewire::withQueryParams(['turno' => $turno->id])
                ->actingAs($alumno)
                ->test(ReprogramarTurno::class)
                ->assertSet('turnoOriginal', null)
                ->assertSet('errorMensaje', 'Este turno no se puede reprogramar.');
        }

        $this->assertDatabaseCount('turnos', 2);
    }

    public function test_rama_suspendido_profesor_reprograma_mismo_turno_y_conserva_pago_y_enlace(): void
    {
        [$turno, $pago] = $this->escenarioReprogramacion([
            'estado' => Turno::ESTADO_SUSPENDIDO_PROFESOR,
            'suspendido_at' => now(),
            'suspendido_por_id' => null,
            'suspension_motivo' => 'Motivo de prueba',
        ]);
        $enlace = $turno->enlace_clase;
        $slot = $this->slotNuevo($turno);

        $this->reprogramar($turno, $slot);

        $actualizado = $turno->fresh();
        $this->assertDatabaseCount('turnos', 1);
        $this->assertSame(Turno::ESTADO_CONFIRMADO, $actualizado->estado);
        $this->assertSame($slot['fecha'], $actualizado->fecha->format('Y-m-d'));
        $this->assertSame($enlace, $actualizado->enlace_clase);
        $this->assertSame($actualizado->id, $pago->fresh()->turno_id);
    }

    private function escenarioCancelacion(array $turnoAttributes = [], bool $crearPago = true): array
    {
        $alumno = $this->crearAlumno();
        $profesor = $this->crearProfesor();
        $materia = $this->crearMateria();
        $turno = $this->crearTurno($alumno, $profesor, $materia, array_merge([
            'estado' => Turno::ESTADO_CONFIRMADO,
        ], $turnoAttributes));
        $pago = $crearPago ? $this->crearPago($turno) : null;
        $politica = $this->crearPoliticaCancelacion();

        return [$turno, $pago, $politica];
    }

    private function suspenderComoAlumno(Turno $turno, $politica): void
    {
        $huella = app(CreditoService::class)->huellaPolitica($politica);

        $this->actingAs($turno->alumno)
            ->post(route('turnos.cancelar-panel', $turno), [
                'acepta_terminos' => '1',
                'politica_version' => $huella,
            ]);
    }

    private function escenarioReprogramacion(array $turnoAttributes = []): array
    {
        $alumno = $this->crearAlumno();
        $profesor = $this->crearProfesor();
        $materia = $this->crearMateria();
        $turno = $this->crearTurno($alumno, $profesor, $materia, array_merge([
            'estado' => Turno::ESTADO_CONFIRMADO,
            'enlace_clase' => 'https://meet.google.com/enlace-congelado-original',
        ], $turnoAttributes));
        $pago = $this->crearPago($turno);

        return [$turno, $pago];
    }

    private function slotNuevo(Turno $turno, ?int $profesorId = null): array
    {
        return [
            'profesor_id' => $profesorId ?? $turno->profesor_id,
            'fecha' => '2026-08-29',
            'hora_inicio' => '15:00:00',
            'hora_fin' => '16:00:00',
            'desde' => '15:00',
            'hasta' => '16:00',
            'precio_por_hora' => '100.00',
            'precio_total' => '100.00',
        ];
    }

    private function reprogramar(Turno $turno, array $slot): void
    {
        Livewire::withQueryParams(['turno' => $turno->id])
            ->actingAs($turno->alumno)
            ->test(ReprogramarTurno::class)
            ->set('slots', [$slot])
            ->call('reprogramar', 0)
            ->assertHasNoErrors();
    }
}
