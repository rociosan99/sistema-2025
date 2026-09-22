<?php

namespace Tests\Feature\Turnos;

use App\Filament\Alumno\Pages\CompletarPagoTurno;
use App\Filament\Alumno\Pages\VerTurnoAlumno;
use App\Jobs\ProcesarReemplazoTurnoCanceladoJob;
use App\Jobs\NotificarReemplazoNoConseguidoJob;
use App\Mail\AlumnoInvitacionReemplazo;
use App\Mail\ProfesorReemplazoConfirmado;
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

        $nuevoTurno = Turno::query()
            ->where('alumno_id', $candidato->id)
            ->where('estado', Turno::ESTADO_PENDIENTE_PAGO)
            ->sole();
        $response->assertRedirect(VerTurnoAlumno::getUrl(['record' => $nuevoTurno->id], panel: 'alumno'));
        $response->assertSessionHas('success');
        $this->assertSame($nuevoTurno->id, $turnoCancelado->fresh()->reemplazado_por_turno_id);
        $this->assertSame(TurnoReemplazo::ESTADO_ACEPTADA, $invitacion->fresh()->estado);
    }

    public function test_aceptar_nuevamente_no_duplica_y_redirige_al_turno_existente(): void
    {
        [$turnoCancelado, , $materia] = $this->escenarioBase();
        $candidato = $this->crearAlumno();
        $this->crearSolicitud($candidato, $materia);
        $this->ejecutarMatching($turnoCancelado);
        $invitacion = TurnoReemplazo::query()->sole();

        $this->actingAs($candidato)->get($this->urlRespuesta($invitacion));
        $turnoNuevo = Turno::query()->where('alumno_id', $candidato->id)->where('estado', Turno::ESTADO_PENDIENTE_PAGO)->sole();
        $cantidadTurnos = Turno::query()->count();

        $response = $this->get($this->urlRespuesta($invitacion->fresh()));

        $response->assertRedirect(VerTurnoAlumno::getUrl(['record' => $turnoNuevo->id], panel: 'alumno'));
        $this->assertSame($cantidadTurnos, Turno::query()->count());
        $this->assertSame(TurnoReemplazo::ESTADO_ACEPTADA, $invitacion->fresh()->estado);
        $this->assertSame($turnoNuevo->id, $turnoCancelado->fresh()->reemplazado_por_turno_id);
        Mail::assertQueued(ProfesorReemplazoConfirmado::class, 1);
    }

    public function test_rechazar_despues_de_aceptar_no_modifica_nada_ni_despacha_job(): void
    {
        [$turnoCancelado, , $materia] = $this->escenarioBase();
        $candidato = $this->crearAlumno();
        $this->crearSolicitud($candidato, $materia);
        $this->ejecutarMatching($turnoCancelado);
        $invitacion = TurnoReemplazo::query()->sole();
        $this->actingAs($candidato)->get($this->urlRespuesta($invitacion));
        $turnoNuevo = Turno::query()->where('alumno_id', $candidato->id)->where('estado', Turno::ESTADO_PENDIENTE_PAGO)->sole();
        Queue::fake();

        $response = $this->get($this->urlRespuesta($invitacion->fresh(), 'rechazar'));

        $response->assertRedirect(VerTurnoAlumno::getUrl(['record' => $turnoNuevo->id], panel: 'alumno'));
        $this->assertSame(TurnoReemplazo::ESTADO_ACEPTADA, $invitacion->fresh()->estado);
        $this->assertSame($turnoNuevo->id, $turnoCancelado->fresh()->reemplazado_por_turno_id);
        $this->assertSame(Turno::ESTADO_PENDIENTE_PAGO, $turnoNuevo->fresh()->estado);
        Queue::assertNotPushed(NotificarReemplazoNoConseguidoJob::class);
    }

    public function test_invitacion_rechazada_no_admite_nuevos_efectos(): void
    {
        [$turnoCancelado, , $materia] = $this->escenarioBase();
        $candidato = $this->crearAlumno();
        $this->crearSolicitud($candidato, $materia);
        $this->ejecutarMatching($turnoCancelado);
        $invitacion = TurnoReemplazo::query()->sole();
        $invitacion->update(['estado' => TurnoReemplazo::ESTADO_RECHAZADA]);
        Queue::fake();

        $this->actingAs($candidato)->get($this->urlRespuesta($invitacion, 'aceptar'))->assertSessionHas('error');
        $this->get($this->urlRespuesta($invitacion, 'rechazar'))->assertSessionHas('error');

        $this->assertSame(TurnoReemplazo::ESTADO_RECHAZADA, $invitacion->fresh()->estado);
        $this->assertNull($turnoCancelado->fresh()->reemplazado_por_turno_id);
        $this->assertDatabaseMissing('turnos', ['alumno_id' => $candidato->id, 'estado' => Turno::ESTADO_PENDIENTE_PAGO]);
        Queue::assertNotPushed(NotificarReemplazoNoConseguidoJob::class);
    }

    public function test_invitacion_pendiente_vencida_expira_sin_crear_turno(): void
    {
        [$turnoCancelado, , $materia] = $this->escenarioBase();
        $candidato = $this->crearAlumno();
        $this->crearSolicitud($candidato, $materia);
        $this->ejecutarMatching($turnoCancelado);
        $invitacion = TurnoReemplazo::query()->sole();
        $invitacion->update(['expires_at' => now()->subMinute()]);

        $this->actingAs($candidato)->get($this->urlRespuesta($invitacion))->assertSessionHas('error', 'La invitación expiró.');

        $this->assertSame(TurnoReemplazo::ESTADO_EXPIRADA, $invitacion->fresh()->estado);
        $this->assertNull($turnoCancelado->fresh()->reemplazado_por_turno_id);
    }

    public function test_invitacion_aceptada_no_expira_y_redirige_al_turno_existente(): void
    {
        [$turnoCancelado, , $materia] = $this->escenarioBase();
        $candidato = $this->crearAlumno();
        $this->crearSolicitud($candidato, $materia);
        $this->ejecutarMatching($turnoCancelado);
        $invitacion = TurnoReemplazo::query()->sole();
        $this->actingAs($candidato)->get($this->urlRespuesta($invitacion));
        $turnoNuevo = Turno::query()->where('alumno_id', $candidato->id)->where('estado', Turno::ESTADO_PENDIENTE_PAGO)->sole();
        $invitacion->update(['expires_at' => now()->subMinute()]);

        $response = $this->get($this->urlRespuesta($invitacion->fresh()));

        $response->assertRedirect(VerTurnoAlumno::getUrl(['record' => $turnoNuevo->id], panel: 'alumno'));
        $this->assertSame(TurnoReemplazo::ESTADO_ACEPTADA, $invitacion->fresh()->estado);
    }

    public function test_invitacion_rechazada_no_expira_despues_de_expires_at(): void
    {
        [$turnoCancelado, , $materia] = $this->escenarioBase();
        $candidato = $this->crearAlumno();
        $this->crearSolicitud($candidato, $materia);
        $this->ejecutarMatching($turnoCancelado);
        $invitacion = TurnoReemplazo::query()->sole();
        $invitacion->update(['estado' => TurnoReemplazo::ESTADO_RECHAZADA, 'expires_at' => now()->subMinute()]);

        $this->actingAs($candidato)->get($this->urlRespuesta($invitacion))->assertSessionHas('error');

        $this->assertSame(TurnoReemplazo::ESTADO_RECHAZADA, $invitacion->fresh()->estado);
        $this->assertNull($turnoCancelado->fresh()->reemplazado_por_turno_id);
    }

    public function test_alumno_no_autenticado_es_enviado_al_login_con_destino_conservado(): void
    {
        [$turnoCancelado, , $materia] = $this->escenarioBase();
        $candidato = $this->crearAlumno();
        $this->crearSolicitud($candidato, $materia);
        $this->ejecutarMatching($turnoCancelado);
        $invitacion = TurnoReemplazo::query()->sole();
        $url = $this->urlRespuesta($invitacion);

        $response = $this->get($url);

        $response->assertRedirect(route('filament.alumno.auth.login'));
        $response->assertSessionHas('url.intended', $url);
        $this->assertSame(TurnoReemplazo::ESTADO_PENDIENTE, $invitacion->fresh()->estado);
    }

    public function test_usuario_incorrecto_no_procesa_y_conserva_destino(): void
    {
        [$turnoCancelado, , $materia] = $this->escenarioBase();
        $candidato = $this->crearAlumno();
        $otroAlumno = $this->crearAlumno();
        $this->crearSolicitud($candidato, $materia);
        $this->ejecutarMatching($turnoCancelado);
        $invitacion = TurnoReemplazo::query()->sole();
        $url = $this->urlRespuesta($invitacion);

        $response = $this->actingAs($otroAlumno)->get($url);

        $response->assertRedirect(route('filament.alumno.auth.login'));
        $response->assertSessionHas('url.intended', $url);
        $this->assertGuest();
        $this->assertSame(TurnoReemplazo::ESTADO_PENDIENTE, $invitacion->fresh()->estado);
        $this->assertNull($turnoCancelado->fresh()->reemplazado_por_turno_id);
    }

    public function test_ficha_muestra_pagar_solo_para_pendiente_pago_no_iniciado(): void
    {
        $alumno = $this->crearAlumno();
        $profesor = $this->crearProfesor();
        $materia = $this->crearMateria();
        $turno = $this->crearTurno($alumno, $profesor, $materia, [
            'estado' => Turno::ESTADO_PENDIENTE_PAGO,
            'fecha' => '2026-08-27',
            'hora_inicio' => '15:00:00',
            'hora_fin' => '16:00:00',
        ]);

        $response = $this->actingAs($alumno)->get(VerTurnoAlumno::getUrl(['record' => $turno->id], panel: 'alumno'));

        $response->assertOk();
        $response->assertSee('Pagar');
        $response->assertSee(CompletarPagoTurno::getUrl(['record' => $turno->id], panel: 'alumno'), false);

        $turno->update(['estado' => Turno::ESTADO_CONFIRMADO]);
        $this->get(VerTurnoAlumno::getUrl(['record' => $turno->id], panel: 'alumno'))->assertDontSee('Pagar');
    }

    public function test_correo_al_profesor_por_reemplazo_confirmado_puede_renderizarse(): void
    {
        [$turnoCancelado, $profesor, $materia] = $this->escenarioBase();
        $alumnoNuevo = $this->crearAlumno([
            'name' => 'Alumno',
            'apellido' => 'Reemplazante',
        ]);
        $turnoNuevo = $this->crearTurno($alumnoNuevo, $profesor, $materia, [
            'fecha' => '2026-08-27',
            'hora_inicio' => '15:00:00',
            'hora_fin' => '16:00:00',
            'estado' => Turno::ESTADO_PENDIENTE_PAGO,
        ]);

        $html = (new ProfesorReemplazoConfirmado($turnoCancelado, $turnoNuevo))->render();

        $this->assertStringContainsString('Clase reasignada', $html);
        $this->assertStringContainsString('Alumno Reemplazante', $html);
        $this->assertStringContainsString($materia->materia_nombre, $html);
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

    private function urlRespuesta(TurnoReemplazo $invitacion, string $accion = 'aceptar'): string
    {
        return URL::signedRoute('reemplazos.responder', [
            'turnoReemplazo' => $invitacion->id,
            'accion' => $accion,
        ]);
    }
}
