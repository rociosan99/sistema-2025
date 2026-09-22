<?php

namespace Tests\Feature\Ofertas;

use App\Jobs\GenerarOfertasInteligentesDesdeSolicitudesJob;
use App\Models\OfertaSolicitud;
use App\Models\SolicitudDisponibilidad;
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

    private function ejecutarMatching(?int $solicitudId = null): void
    {
        (new GenerarOfertasInteligentesDesdeSolicitudesJob($solicitudId))
            ->handle(app(SolicitudMatchingService::class));
    }
}
