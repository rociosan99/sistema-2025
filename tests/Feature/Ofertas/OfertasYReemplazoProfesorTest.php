<?php

namespace Tests\Feature\Ofertas;

use App\Mail\AlumnoReemplazoProfesorConfirmado;
use App\Filament\Alumno\Pages\ResponderOfertaProfesor;
use App\Filament\Profesor\Pages\OfertasSolicitudes;
use App\Models\OfertaSolicitud;
use App\Models\Pago;
use App\Models\SolicitudDisponibilidad;
use App\Models\Turno;
use App\Services\EnlaceClaseProfesorService;
use App\Services\ReemplazoProfesorService;
use App\Services\SolicitudMatchingService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Route;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Tests\Support\CreatesTurnoScenarios;
use Tests\TestCase;

class OfertasYReemplazoProfesorTest extends TestCase
{
    use CreatesTurnoScenarios;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->travelTo('2026-08-25 10:00:00');
        Mail::fake();
        $this->registrarRutasFilamentDePrueba();
    }

    public function test_solo_profesor_destinatario_puede_aceptar_oferta(): void
    {
        [$solicitud, $oferta, , $otroProfesor] = $this->escenarioOferta();
        $this->actingAs($otroProfesor);
        $pagina = $this->paginaOfertas($oferta->id);

        $this->expectException(ModelNotFoundException::class);

        try {
            $pagina->aceptar(app(SolicitudMatchingService::class), app(EnlaceClaseProfesorService::class));
        } finally {
            $this->assertSame(SolicitudDisponibilidad::ESTADO_ACTIVA, $solicitud->fresh()->estado);
            $this->assertDatabaseCount('turnos', 0);
        }
    }

    public function test_primera_aceptacion_gana_cierra_solicitud_y_crea_unico_turno_con_datos_congelados(): void
    {
        [$solicitud, $ofertaGanadora, $profesor, $otroProfesor, $otraOferta] = $this->escenarioOferta();
        $this->actingAs($profesor);

        $this->paginaOfertas($ofertaGanadora->id)
            ->aceptar(app(SolicitudMatchingService::class), app(EnlaceClaseProfesorService::class));

        $turno = Turno::query()->sole();

        $this->assertSame(SolicitudDisponibilidad::ESTADO_TOMADA, $solicitud->fresh()->estado);
        $this->assertSame(OfertaSolicitud::ESTADO_ACEPTADA, $ofertaGanadora->fresh()->estado);
        $this->assertSame(OfertaSolicitud::ESTADO_EXPIRADA, $otraOferta->fresh()->estado);
        $this->assertSame(Turno::ESTADO_ACEPTADO, $turno->estado);
        $this->assertSame($profesor->id, $turno->profesor_id);
        $this->assertSame('120.00', $turno->precio_por_hora);
        $this->assertSame('120.00', $turno->precio_total);
        $this->assertSame('https://meet.google.com/oferta-profesor', $turno->enlace_clase);
        $this->assertDatabaseCount('turnos', 1);
    }

    public function test_segunda_aceptacion_o_doble_clic_no_crea_otro_turno(): void
    {
        [, $ofertaGanadora, $profesor, $otroProfesor, $otraOferta] = $this->escenarioOferta();

        $this->actingAs($profesor);
        $this->paginaOfertas($ofertaGanadora->id)
            ->aceptar(app(SolicitudMatchingService::class), app(EnlaceClaseProfesorService::class));

        $this->paginaOfertas($ofertaGanadora->id)
            ->aceptar(app(SolicitudMatchingService::class), app(EnlaceClaseProfesorService::class));

        $this->actingAs($otroProfesor);
        $this->paginaOfertas($otraOferta->id)
            ->aceptar(app(SolicitudMatchingService::class), app(EnlaceClaseProfesorService::class));

        $this->assertDatabaseCount('turnos', 1);
    }

    public function test_alumno_acepta_propuesta_y_turno_pasa_a_pendiente_pago(): void
    {
        [$turno, $alumno] = $this->turnoAceptadoParaAlumno();
        $this->actingAs($alumno);
        $pagina = $this->paginaRespuestaAlumno($turno);

        $pagina->aceptar();

        $this->assertSame(Turno::ESTADO_PENDIENTE_PAGO, $turno->fresh()->estado);
    }

    public function test_alumno_rechaza_propuesta_y_turno_pasa_a_rechazado(): void
    {
        [$turno, $alumno] = $this->turnoAceptadoParaAlumno();
        $this->actingAs($alumno);
        $pagina = $this->paginaRespuestaAlumno($turno);

        $pagina->rechazar();

        $this->assertSame(Turno::ESTADO_RECHAZADO, $turno->fresh()->estado);
        $this->assertTrue($pagina->propuestaRechazada);
    }

    public function test_alumno_no_puede_responder_propuesta_ajena(): void
    {
        [$turno] = $this->turnoAceptadoParaAlumno();
        $otroAlumno = $this->crearAlumno();
        $this->actingAs($otroAlumno);

        $this->expectException(NotFoundHttpException::class);
        $this->paginaRespuestaAlumno($turno);
    }

    public function test_reemplazo_solo_funciona_en_turno_suspendido_con_pago_aprobado(): void
    {
        [$turno, , $reemplazante] = $this->escenarioReemplazo();
        $service = app(ReemplazoProfesorService::class);

        $turno->update(['estado' => Turno::ESTADO_CONFIRMADO]);
        $this->expectValidationException(fn () => $service->proponer(
            $turno->fresh(),
            $turno->alumno_id,
            $this->slotReemplazo($reemplazante),
        ));

        $turno->update(['estado' => Turno::ESTADO_SUSPENDIDO_PROFESOR]);
        $turno->pago()->update(['estado' => Pago::ESTADO_RECHAZADO]);
        $this->expectValidationException(fn () => $service->proponer(
            $turno->fresh(),
            $turno->alumno_id,
            $this->slotReemplazo($reemplazante),
        ));

        $this->assertNull($turno->fresh()->reemplazo_profesor_propuesto_id);
    }

    public function test_solo_profesor_propuesto_puede_aceptar_reemplazo(): void
    {
        [$turno, , $reemplazante, $otroProfesor] = $this->escenarioReemplazo(conPropuesta: true);

        $this->expectException(NotFoundHttpException::class);
        app(ReemplazoProfesorService::class)->aceptar($turno, $otroProfesor->id);
    }

    public function test_propuesta_reemplazo_vencida_no_puede_aceptarse(): void
    {
        [$turno, , $reemplazante] = $this->escenarioReemplazo(conPropuesta: true);
        $turno->update(['reemplazo_expires_at' => now()->subMinute()]);

        $this->expectValidationException(
            fn () => app(ReemplazoProfesorService::class)->aceptar($turno->fresh(), $reemplazante->id),
        );

        $this->assertSame(Turno::ESTADO_SUSPENDIDO_PROFESOR, $turno->fresh()->estado);
    }

    public function test_reemplazante_necesita_enlace_predeterminado_valido(): void
    {
        [$turno, $original, $reemplazante] = $this->escenarioReemplazo(conPropuesta: true, enlaceReemplazante: null);

        $this->expectValidationException(
            fn () => app(ReemplazoProfesorService::class)->aceptar($turno, $reemplazante->id),
        );

        $actual = $turno->fresh();
        $this->assertSame($original->id, $actual->profesor_id);
        $this->assertSame(Turno::ESTADO_SUSPENDIDO_PROFESOR, $actual->estado);
        $this->assertSame($reemplazante->id, $actual->reemplazo_profesor_propuesto_id);
    }

    public function test_aceptar_reemplazo_conserva_turno_pago_y_precio_cambia_profesor_enlace_estado_y_limpia_propuesta(): void
    {
        [$turno, , $reemplazante, , $pago] = $this->escenarioReemplazo(conPropuesta: true);
        $turnoId = $turno->id;
        $pagoId = $pago->pago_id;

        $resultado = app(ReemplazoProfesorService::class)->aceptar($turno, $reemplazante->id);

        $this->assertSame($turnoId, $resultado->id);
        $this->assertDatabaseCount('turnos', 1);
        $this->assertSame($turnoId, $pago->fresh()->turno_id);
        $this->assertSame($pagoId, $resultado->pago->pago_id);
        $this->assertSame('100.00', $resultado->precio_por_hora);
        $this->assertSame('100.00', $resultado->precio_total);
        $this->assertSame($reemplazante->id, $resultado->profesor_id);
        $this->assertSame('https://meet.google.com/profesor-reemplazante', $resultado->enlace_clase);
        $this->assertSame(Turno::ESTADO_CONFIRMADO, $resultado->estado);

        foreach ([
            'reemplazo_profesor_propuesto_id',
            'reemplazo_fecha',
            'reemplazo_hora_inicio',
            'reemplazo_hora_fin',
            'reemplazo_solicitado_at',
            'reemplazo_expires_at',
        ] as $campo) {
            $this->assertNull($resultado->{$campo});
        }
    }

    public function test_rechazar_reemplazo_conserva_profesor_original_y_limpia_propuesta(): void
    {
        [$turno, $original, $reemplazante, , $pago] = $this->escenarioReemplazo(conPropuesta: true);

        $resultado = app(ReemplazoProfesorService::class)->rechazar($turno, $reemplazante->id);

        $this->assertSame($original->id, $resultado->profesor_id);
        $this->assertSame(Turno::ESTADO_SUSPENDIDO_PROFESOR, $resultado->estado);
        $this->assertSame($turno->id, $pago->fresh()->turno_id);
        $this->assertNull($resultado->reemplazo_profesor_propuesto_id);
        $this->assertNull($resultado->reemplazo_expires_at);
    }

    public function test_aceptar_reemplazo_envia_correo_al_alumno_despues_del_commit(): void
    {
        [$turno, , $reemplazante, , , $alumno] = $this->escenarioReemplazo(conPropuesta: true);

        app(ReemplazoProfesorService::class)->aceptar($turno, $reemplazante->id);

        Mail::assertSent(AlumnoReemplazoProfesorConfirmado::class, function ($mail) use ($alumno): bool {
            return $mail->hasTo($alumno->email);
        });
    }

    private function escenarioOferta(): array
    {
        $alumno = $this->crearAlumno();
        $profesor = $this->crearProfesor(enlace: 'https://meet.google.com/oferta-profesor');
        $otroProfesor = $this->crearProfesor();
        $materia = $this->crearMateria();
        $this->asignarMateriaProfesor($profesor, $materia, '120.00');
        $this->asignarMateriaProfesor($otroProfesor, $materia, '140.00');
        $this->crearDisponibilidad($profesor);
        $this->crearDisponibilidad($otroProfesor);
        $solicitud = $this->crearSolicitudDisponibilidad($alumno, $materia);
        $oferta = $this->crearOfertaSolicitud($solicitud, $profesor);
        $otraOferta = $this->crearOfertaSolicitud($solicitud, $otroProfesor);

        return [$solicitud, $oferta, $profesor, $otroProfesor, $otraOferta, $alumno, $materia];
    }

    private function paginaOfertas(int $ofertaId): OfertasSolicitudes
    {
        $pagina = app(OfertasSolicitudes::class);
        $pagina->ofertaSeleccionada = $ofertaId;

        return $pagina;
    }

    private function turnoAceptadoParaAlumno(): array
    {
        $alumno = $this->crearAlumno();
        $profesor = $this->crearProfesor();
        $materia = $this->crearMateria();
        $turno = $this->crearTurno($alumno, $profesor, $materia, [
            'estado' => Turno::ESTADO_ACEPTADO,
        ]);

        return [$turno, $alumno];
    }

    private function paginaRespuestaAlumno(Turno $turno): ResponderOfertaProfesor
    {
        $pagina = app(ResponderOfertaProfesor::class);
        $pagina->mount($turno->id);

        return $pagina;
    }

    private function escenarioReemplazo(bool $conPropuesta = false, ?string $enlaceReemplazante = 'https://meet.google.com/profesor-reemplazante'): array
    {
        $alumno = $this->crearAlumno();
        $original = $this->crearProfesor(enlace: 'https://meet.google.com/profesor-original');
        $reemplazante = $this->crearProfesor(enlace: $enlaceReemplazante);
        $otroProfesor = $this->crearProfesor();
        $materia = $this->crearMateria();
        $this->asignarMateriaProfesor($reemplazante, $materia, '150.00');
        $this->crearDisponibilidad($reemplazante);
        $turno = $this->crearTurno($alumno, $original, $materia, [
            'estado' => Turno::ESTADO_SUSPENDIDO_PROFESOR,
            'enlace_clase' => 'https://meet.google.com/profesor-original',
            'precio_por_hora' => '100.00',
            'precio_total' => '100.00',
            'suspendido_at' => now(),
            'suspendido_por_id' => $original->id,
            'suspension_motivo' => 'No puede dictar la clase.',
        ]);
        $pago = $this->crearPago($turno);

        if ($conPropuesta) {
            $turno = app(ReemplazoProfesorService::class)->proponer(
                $turno,
                $alumno->id,
                $this->slotReemplazo($reemplazante),
            );
        }

        return [$turno, $original, $reemplazante, $otroProfesor, $pago, $alumno, $materia];
    }

    private function slotReemplazo($profesor): array
    {
        return [
            'profesor_id' => $profesor->id,
            'fecha' => '2026-08-27',
            'hora_inicio' => '10:00:00',
            'hora_fin' => '11:00:00',
        ];
    }

    private function expectValidationException(callable $callback): void
    {
        try {
            $callback();
            $this->fail('Se esperaba una ValidationException.');
        } catch (ValidationException $exception) {
            $this->assertNotEmpty($exception->errors());
        }
    }

    private function registrarRutasFilamentDePrueba(): void
    {
        foreach ([
            'filament.admin.resources.turnos.index' => '/testing/alumno/turnos',
            'filament.admin.pages.solicitudes-disponibilidad' => '/testing/alumno/solicitudes',
            'filament.admin.pages.completar-pago-turno' => '/testing/alumno/completar-pago/{record}',
        ] as $nombre => $uri) {
            if (! Route::has($nombre)) {
                Route::get($uri, fn () => response()->noContent())->name($nombre);
            }
        }
    }
}
