<?php

namespace Tests\Feature\Turnos;

use App\Jobs\ProcesarReemplazoTurnoCanceladoJob;
use App\Mail\AlumnoInvitacionReemplazo;
use App\Models\SolicitudDisponibilidad;
use App\Models\Turno;
use App\Models\TurnoReemplazo;
use App\Services\SolicitudMatchingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\URL;
use Tests\Support\CreatesTurnoScenarios;
use Tests\TestCase;

class ReemplazoAlumnoSolapamientoTest extends TestCase
{
    use CreatesTurnoScenarios;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->travelTo('2026-08-25 10:00:00');
        Mail::fake();
        Queue::fake();
    }

    public function test_alumno_ocupado_en_horario_liberado_no_recibe_invitacion(): void
    {
        [$turnoCancelado, , $materia] = $this->escenarioBase();
        $candidato = $this->crearAlumno();
        $this->crearSolicitud($candidato, $materia);
        $this->crearTurnoSolapado($candidato, $materia, '15:00:00', '16:00:00');

        $this->ejecutarMatching($turnoCancelado);

        $this->assertDatabaseMissing('turno_reemplazos', [
            'turno_cancelado_id' => $turnoCancelado->id,
            'alumno_id' => $candidato->id,
        ]);
        Mail::assertNotSent(AlumnoInvitacionReemplazo::class);
    }

    public function test_turno_inmediatamente_anterior_no_bloquea_invitacion(): void
    {
        [$turnoCancelado, , $materia] = $this->escenarioBase();
        $candidato = $this->crearAlumno();
        $this->crearSolicitud($candidato, $materia);
        $this->crearTurnoSolapado($candidato, $materia, '14:00:00', '15:00:00');

        $this->ejecutarMatching($turnoCancelado);

        $this->assertDatabaseHas('turno_reemplazos', [
            'turno_cancelado_id' => $turnoCancelado->id,
            'alumno_id' => $candidato->id,
            'hora_inicio' => '15:00:00',
            'hora_fin' => '16:00:00',
            'estado' => TurnoReemplazo::ESTADO_PENDIENTE,
        ]);
    }

    public function test_candidato_ocupado_se_omite_y_otro_libre_recibe_invitacion(): void
    {
        [$turnoCancelado, , $materia] = $this->escenarioBase();
        $ocupado = $this->crearAlumno();
        $libre = $this->crearAlumno();
        $this->crearSolicitud($ocupado, $materia);
        $this->crearSolicitud($libre, $materia);
        $this->crearTurnoSolapado($ocupado, $materia, '15:00:00', '16:00:00');

        $this->ejecutarMatching($turnoCancelado);

        $this->assertDatabaseMissing('turno_reemplazos', [
            'turno_cancelado_id' => $turnoCancelado->id,
            'alumno_id' => $ocupado->id,
        ]);
        $this->assertDatabaseHas('turno_reemplazos', [
            'turno_cancelado_id' => $turnoCancelado->id,
            'alumno_id' => $libre->id,
            'estado' => TurnoReemplazo::ESTADO_PENDIENTE,
        ]);
    }

    public function test_alumno_que_se_ocupa_despues_no_puede_aceptar_invitacion(): void
    {
        [$turnoCancelado, , $materia] = $this->escenarioBase();
        $candidato = $this->crearAlumno();
        $this->crearSolicitud($candidato, $materia);
        $this->ejecutarMatching($turnoCancelado);
        $invitacion = TurnoReemplazo::query()->sole();
        $this->crearTurnoSolapado($candidato, $materia, '15:00:00', '16:00:00');

        $response = $this->actingAs($candidato)->get($this->urlRespuesta($invitacion));

        $response->assertSessionHas(
            'error',
            'Ya tenés otro turno en ese día y horario. Esta invitación ya no está disponible para vos.',
        );
        $this->assertSame(TurnoReemplazo::ESTADO_PENDIENTE, $invitacion->fresh()->estado);
        $this->assertNull($turnoCancelado->fresh()->reemplazado_por_turno_id);
        $this->assertDatabaseMissing('turnos', [
            'alumno_id' => $candidato->id,
            'estado' => Turno::ESTADO_PENDIENTE_PAGO,
            'fecha' => '2026-08-27',
            'hora_inicio' => '15:00:00',
            'hora_fin' => '16:00:00',
        ]);
    }

    public function test_alumno_que_continua_libre_puede_aceptar_invitacion(): void
    {
        [$turnoCancelado, , $materia] = $this->escenarioBase();
        $candidato = $this->crearAlumno();
        $this->crearSolicitud($candidato, $materia);
        $this->ejecutarMatching($turnoCancelado);
        $invitacion = TurnoReemplazo::query()->sole();

        $response = $this->actingAs($candidato)->get($this->urlRespuesta($invitacion));

        $response->assertSessionHas('success');
        $nuevoTurno = Turno::query()
            ->where('alumno_id', $candidato->id)
            ->where('estado', Turno::ESTADO_PENDIENTE_PAGO)
            ->sole();
        $this->assertSame($nuevoTurno->id, $turnoCancelado->fresh()->reemplazado_por_turno_id);
        $this->assertSame(TurnoReemplazo::ESTADO_ACEPTADA, $invitacion->fresh()->estado);
    }

    private function escenarioBase(): array
    {
        $alumnoOriginal = $this->crearAlumno();
        $profesor = $this->crearProfesor();
        $materia = $this->crearMateria();
        $turnoCancelado = $this->crearTurno($alumnoOriginal, $profesor, $materia, [
            'fecha' => '2026-08-27',
            'hora_inicio' => '15:00:00',
            'hora_fin' => '16:00:00',
            'estado' => Turno::ESTADO_CANCELADO,
            'cancelacion_tipo' => 'con_cargo',
            'cancelado_at' => now(),
        ]);

        return [$turnoCancelado, $profesor, $materia];
    }

    private function crearSolicitud($alumno, $materia): SolicitudDisponibilidad
    {
        return $this->crearSolicitudDisponibilidad($alumno, $materia, [
            'fecha' => '2026-08-27',
            'hora_inicio' => '15:00:00',
            'hora_fin' => '16:00:00',
            'expires_at' => now()->addHour(),
        ]);
    }

    private function crearTurnoSolapado($alumno, $materia, string $inicio, string $fin): Turno
    {
        return $this->crearTurno($alumno, $this->crearProfesor(), $materia, [
            'fecha' => '2026-08-27',
            'hora_inicio' => $inicio,
            'hora_fin' => $fin,
            'estado' => Turno::ESTADO_CONFIRMADO,
        ]);
    }

    private function ejecutarMatching(Turno $turnoCancelado): void
    {
        (new ProcesarReemplazoTurnoCanceladoJob(
            turnoCanceladoId: $turnoCancelado->id,
            excludedAlumnoId: $turnoCancelado->alumno_id,
        ))->handle(app(SolicitudMatchingService::class));
    }

    private function urlRespuesta(TurnoReemplazo $invitacion): string
    {
        return URL::signedRoute('reemplazos.responder', [
            'turnoReemplazo' => $invitacion->id,
            'accion' => 'aceptar',
        ]);
    }
}
