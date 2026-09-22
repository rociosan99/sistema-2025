<?php

namespace Tests\Feature\Ofertas;

use App\Filament\Profesor\Pages\OfertasSolicitudes;
use App\Jobs\GenerarOfertasInteligentesDesdeSolicitudesJob;
use App\Models\OfertaSolicitud;
use App\Models\SolicitudDisponibilidad;
use App\Models\Turno;
use App\Services\EnlaceClaseProfesorService;
use App\Services\SolicitudMatchingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\CreatesTurnoScenarios;
use Tests\TestCase;

class GenerarOfertasInteligentesTest extends TestCase
{
    use CreatesTurnoScenarios;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->travelTo('2026-08-25 10:00:00');
    }

    public function test_solicitud_con_profesor_compatible_genera_una_oferta(): void
    {
        [$solicitud, $profesor] = $this->crearEscenarioCompatible();

        $this->ejecutarMatching($solicitud->id);

        $this->assertDatabaseHas('ofertas_solicitud', [
            'solicitud_id' => $solicitud->id,
            'profesor_id' => $profesor->id,
            'hora_inicio' => '10:00:00',
            'hora_fin' => '11:00:00',
            'estado' => OfertaSolicitud::ESTADO_PENDIENTE,
        ]);
        $this->assertDatabaseCount('ofertas_solicitud', 1);
    }

    public function test_solicitud_sin_profesor_compatible_no_genera_oferta_y_permanece_activa(): void
    {
        $alumno = $this->crearAlumno();
        $profesor = $this->crearProfesor();
        $materia = $this->crearMateria();
        $this->asignarMateriaProfesor($profesor, $materia);
        $solicitud = $this->crearSolicitudDisponibilidad($alumno, $materia);

        $this->ejecutarMatching($solicitud->id);

        $this->assertDatabaseCount('ofertas_solicitud', 0);
        $this->assertSame(SolicitudDisponibilidad::ESTADO_ACTIVA, $solicitud->fresh()->estado);
    }

    public function test_ejecutar_matching_dos_veces_no_duplica_la_oferta(): void
    {
        [$solicitud] = $this->crearEscenarioCompatible();

        $this->ejecutarMatching($solicitud->id);
        $this->ejecutarMatching($solicitud->id);

        $this->assertDatabaseCount('ofertas_solicitud', 1);
    }

    public function test_disponibilidad_agregada_despues_permite_generar_la_oferta(): void
    {
        $alumno = $this->crearAlumno();
        $profesor = $this->crearProfesor();
        $materia = $this->crearMateria();
        $this->asignarMateriaProfesor($profesor, $materia);
        $solicitud = $this->crearSolicitudDisponibilidad($alumno, $materia);

        $this->ejecutarMatching($solicitud->id);
        $this->assertDatabaseCount('ofertas_solicitud', 0);

        $this->crearDisponibilidad($profesor);
        $this->ejecutarMatching($solicitud->id);

        $this->assertDatabaseHas('ofertas_solicitud', [
            'solicitud_id' => $solicitud->id,
            'profesor_id' => $profesor->id,
            'estado' => OfertaSolicitud::ESTADO_PENDIENTE,
        ]);
        $this->assertSame(SolicitudDisponibilidad::ESTADO_ACTIVA, $solicitud->fresh()->estado);
    }

    public function test_matching_con_solicitud_id_procesa_unicamente_esa_solicitud(): void
    {
        [$primera, $profesor, $materia] = $this->crearEscenarioCompatible();
        $otroAlumno = $this->crearAlumno();
        $segunda = $this->crearSolicitudDisponibilidad($otroAlumno, $materia);

        $this->ejecutarMatching($primera->id);

        $this->assertDatabaseHas('ofertas_solicitud', [
            'solicitud_id' => $primera->id,
            'profesor_id' => $profesor->id,
        ]);
        $this->assertDatabaseMissing('ofertas_solicitud', [
            'solicitud_id' => $segunda->id,
        ]);
        $this->assertDatabaseCount('ofertas_solicitud', 1);
    }

    public function test_matching_sin_id_conserva_el_procesamiento_por_lote_del_scheduler(): void
    {
        [$primera, $profesor, $materia] = $this->crearEscenarioCompatible();
        $otroAlumno = $this->crearAlumno();
        $segunda = $this->crearSolicitudDisponibilidad($otroAlumno, $materia);

        $this->ejecutarMatching();

        $this->assertDatabaseHas('ofertas_solicitud', [
            'solicitud_id' => $primera->id,
            'profesor_id' => $profesor->id,
        ]);
        $this->assertDatabaseHas('ofertas_solicitud', [
            'solicitud_id' => $segunda->id,
            'profesor_id' => $profesor->id,
        ]);
        $this->assertDatabaseCount('ofertas_solicitud', 2);
    }

    public function test_profesor_ocupado_en_primer_bloque_solo_recibe_oferta_del_bloque_libre(): void
    {
        [$solicitud, $profesor, $materia] = $this->crearEscenarioDeDosBloques();
        $otroAlumno = $this->crearAlumno();
        $this->crearTurno($otroAlumno, $profesor, $materia, [
            'hora_inicio' => '09:00:00',
            'hora_fin' => '10:00:00',
            'estado' => Turno::ESTADO_CONFIRMADO,
        ]);

        $this->ejecutarMatching($solicitud->id);

        $this->assertDatabaseMissing('ofertas_solicitud', [
            'solicitud_id' => $solicitud->id,
            'profesor_id' => $profesor->id,
            'hora_inicio' => '09:00:00',
            'hora_fin' => '10:00:00',
        ]);
        $this->assertDatabaseHas('ofertas_solicitud', [
            'solicitud_id' => $solicitud->id,
            'profesor_id' => $profesor->id,
            'hora_inicio' => '10:00:00',
            'hora_fin' => '11:00:00',
            'estado' => OfertaSolicitud::ESTADO_PENDIENTE,
        ]);
        $this->assertDatabaseCount('ofertas_solicitud', 1);
    }

    public function test_actualizar_oculta_oferta_que_perdio_compatibilidad_y_conserva_el_bloque_libre(): void
    {
        [$solicitud, $profesor, $materia] = $this->crearEscenarioDeDosBloques();
        $this->ejecutarMatching($solicitud->id);
        $this->assertDatabaseCount('ofertas_solicitud', 2);

        $otroAlumno = $this->crearAlumno();
        $this->crearTurno($otroAlumno, $profesor, $materia, [
            'hora_inicio' => '09:00:00',
            'hora_fin' => '10:00:00',
            'estado' => Turno::ESTADO_CONFIRMADO,
        ]);

        $this->actingAs($profesor);
        $pagina = app(OfertasSolicitudes::class);
        $pagina->cargar();

        $this->assertSame(['10:00'], collect($pagina->ofertas)->pluck('hora_inicio')->all());
        $this->assertDatabaseCount('ofertas_solicitud', 2);
        $this->assertDatabaseHas('ofertas_solicitud', [
            'solicitud_id' => $solicitud->id,
            'profesor_id' => $profesor->id,
            'hora_inicio' => '09:00:00',
            'estado' => OfertaSolicitud::ESTADO_PENDIENTE,
        ]);
    }

    public function test_aceptar_sigue_rechazando_oferta_que_perdio_compatibilidad(): void
    {
        [$solicitud, $profesor, $materia] = $this->crearEscenarioDeDosBloques();
        $this->ejecutarMatching($solicitud->id);
        $ofertaPrimerBloque = OfertaSolicitud::query()
            ->where('solicitud_id', $solicitud->id)
            ->where('hora_inicio', '09:00:00')
            ->sole();

        $otroAlumno = $this->crearAlumno();
        $this->crearTurno($otroAlumno, $profesor, $materia, [
            'hora_inicio' => '09:00:00',
            'hora_fin' => '10:00:00',
            'estado' => Turno::ESTADO_CONFIRMADO,
        ]);

        $this->actingAs($profesor);
        $pagina = app(OfertasSolicitudes::class);
        $pagina->ofertaSeleccionada = $ofertaPrimerBloque->id;
        $pagina->aceptar(
            app(SolicitudMatchingService::class),
            app(EnlaceClaseProfesorService::class),
        );

        $this->assertDatabaseCount('turnos', 1);
        $this->assertSame(SolicitudDisponibilidad::ESTADO_ACTIVA, $solicitud->fresh()->estado);
        $this->assertSame(OfertaSolicitud::ESTADO_PENDIENTE, $ofertaPrimerBloque->fresh()->estado);
    }

    private function crearEscenarioCompatible(): array
    {
        $alumno = $this->crearAlumno();
        $profesor = $this->crearProfesor();
        $materia = $this->crearMateria();
        $this->asignarMateriaProfesor($profesor, $materia);
        $this->crearDisponibilidad($profesor);
        $solicitud = $this->crearSolicitudDisponibilidad($alumno, $materia);

        return [$solicitud, $profesor, $materia];
    }

    private function crearEscenarioDeDosBloques(): array
    {
        $alumno = $this->crearAlumno();
        $profesor = $this->crearProfesor();
        $materia = $this->crearMateria();
        $this->asignarMateriaProfesor($profesor, $materia);
        $this->crearDisponibilidad($profesor, [
            'hora_inicio' => '09:00:00',
            'hora_fin' => '11:00:00',
        ]);
        $solicitud = $this->crearSolicitudDisponibilidad($alumno, $materia, [
            'hora_inicio' => '09:00:00',
            'hora_fin' => '11:00:00',
        ]);

        return [$solicitud, $profesor, $materia];
    }

    private function ejecutarMatching(?int $solicitudId = null): void
    {
        (new GenerarOfertasInteligentesDesdeSolicitudesJob($solicitudId))
            ->handle(app(SolicitudMatchingService::class));
    }
}
