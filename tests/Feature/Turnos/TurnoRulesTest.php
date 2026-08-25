<?php

namespace Tests\Feature\Turnos;

use App\Filament\Alumno\Pages\SolicitarTurno;
use App\Filament\Alumno\Pages\VerTurnoAlumno;
use App\Mail\ProfesorRespondioTurno;
use App\Models\Turno;
use App\Services\TurnoRespuestaProfesorService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Route;
use Livewire\Livewire;
use Tests\Support\CreatesTurnoScenarios;
use Tests\TestCase;

class TurnoRulesTest extends TestCase
{
    use CreatesTurnoScenarios;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->travelTo('2026-08-25 10:00:00');

        if (! Route::has('filament.admin.pages.mi-perfil')) {
            Route::get('/testing/mi-perfil', fn () => response()->noContent())
                ->name('filament.admin.pages.mi-perfil');
        }
    }

    public function test_alumno_no_puede_reservar_dos_turnos_solapados(): void
    {
        Mail::fake();

        $alumno = $this->crearAlumno();
        $profesorExistente = $this->crearProfesor();
        $profesorNuevo = $this->crearProfesor();
        $materia = $this->crearMateria();

        $this->crearTurno($alumno, $profesorExistente, $materia, [
            'hora_inicio' => '10:00:00',
            'hora_fin' => '11:00:00',
        ]);

        $slot = $this->slotPara([
            'profesor_id' => $profesorNuevo->id,
            'fecha' => '2026-08-27',
            'hora_inicio' => '10:30:00',
            'hora_fin' => '11:30:00',
            'precio_por_hora' => '100.00',
            'precio_total' => '100.00',
        ]);

        $this->actingAs($alumno);

        Livewire::test(SolicitarTurno::class)
            ->set('materiaId', $materia->materia_id)
            ->set('slots', [$slot])
            ->call('reservar', $slot['slot_key'])
            ->assertHasErrors(['slot' => 'Ya tenés otro turno en ese día y horario.']);

        $this->assertDatabaseCount('turnos', 1);
    }

    public function test_profesor_no_puede_tener_turnos_solapados(): void
    {
        Mail::fake();

        $alumnoExistente = $this->crearAlumno();
        $alumnoNuevo = $this->crearAlumno();
        $profesor = $this->crearProfesor();
        $materia = $this->crearMateria();

        $turnoExistente = $this->crearTurno($alumnoExistente, $profesor, $materia);
        $slot = $this->slotPara($turnoExistente);

        $this->actingAs($alumnoNuevo);

        Livewire::test(SolicitarTurno::class)
            ->set('materiaId', $materia->materia_id)
            ->set('slots', [$slot])
            ->call('reservar', $slot['slot_key'])
            ->assertHasErrors(['slot' => 'Ese horario ya no está disponible.']);

        $this->assertDatabaseCount('turnos', 1);
    }

    public function test_profesor_solo_puede_aceptar_un_turno_propio_pendiente_y_futuro(): void
    {
        Mail::fake();

        $alumno = $this->crearAlumno();
        $profesor = $this->crearProfesor();
        $materia = $this->crearMateria();
        $service = app(TurnoRespuestaProfesorService::class);

        $turnoFuturo = $this->crearTurno($alumno, $profesor, $materia);
        $aceptado = $service->aceptar($turnoFuturo, $profesor->id);

        $this->assertNotNull($aceptado);
        $this->assertSame(Turno::ESTADO_PENDIENTE_PAGO, $turnoFuturo->fresh()->estado);
        Mail::assertSent(ProfesorRespondioTurno::class);

        $turnoNoPendiente = $this->crearTurno($alumno, $profesor, $materia, [
            'hora_inicio' => '12:00:00',
            'hora_fin' => '13:00:00',
            'estado' => Turno::ESTADO_CONFIRMADO,
        ]);

        $this->assertNull($service->aceptar($turnoNoPendiente, $profesor->id));
        $this->assertSame(Turno::ESTADO_CONFIRMADO, $turnoNoPendiente->fresh()->estado);

        $turnoPasado = $this->crearTurno($alumno, $profesor, $materia, [
            'fecha' => '2026-08-24',
            'hora_inicio' => '12:00:00',
            'hora_fin' => '13:00:00',
        ]);

        $this->assertNull($service->aceptar($turnoPasado, $profesor->id));
        $this->assertSame(Turno::ESTADO_VENCIDO, $turnoPasado->fresh()->estado);
    }

    public function test_aceptacion_copia_enlace_predeterminado_al_turno(): void
    {
        Mail::fake();

        $alumno = $this->crearAlumno();
        $enlace = 'https://meet.google.com/enlace-congelado';
        $profesor = $this->crearProfesor(enlace: $enlace);
        $materia = $this->crearMateria();
        $turno = $this->crearTurno($alumno, $profesor, $materia);

        app(TurnoRespuestaProfesorService::class)->aceptar($turno, $profesor->id);

        $this->assertSame($enlace, $turno->fresh()->enlace_clase);
    }

    public function test_alumno_no_puede_acceder_al_turno_de_otro_alumno(): void
    {
        $duenio = $this->crearAlumno();
        $otroAlumno = $this->crearAlumno();
        $profesor = $this->crearProfesor();
        $materia = $this->crearMateria();
        $turno = $this->crearTurno($duenio, $profesor, $materia);

        $url = VerTurnoAlumno::getUrl(['record' => $turno->id], panel: 'alumno');

        $this->actingAs($otroAlumno)
            ->get($url)
            ->assertNotFound();
    }

    public function test_profesor_no_puede_responder_un_turno_ajeno(): void
    {
        Mail::fake();

        $alumno = $this->crearAlumno();
        $profesorDuenio = $this->crearProfesor();
        $otroProfesor = $this->crearProfesor();
        $materia = $this->crearMateria();
        $turno = $this->crearTurno($alumno, $profesorDuenio, $materia);

        $this->expectException(ModelNotFoundException::class);

        try {
            app(TurnoRespuestaProfesorService::class)->aceptar($turno, $otroProfesor->id);
        } finally {
            $this->assertSame(Turno::ESTADO_PENDIENTE, $turno->fresh()->estado);
            Mail::assertNothingSent();
        }
    }
}
