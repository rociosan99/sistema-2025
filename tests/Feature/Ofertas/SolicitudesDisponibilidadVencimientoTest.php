<?php

namespace Tests\Feature\Ofertas;

use App\Filament\Alumno\Pages\SolicitudesDisponibilidad;
use App\Jobs\ExpirarOfertasSolicitudJob;
use App\Jobs\GenerarOfertasInteligentesDesdeSolicitudesJob;
use App\Models\OfertaSolicitud;
use App\Models\SolicitudDisponibilidad;
use App\Services\SolicitudDisponibilidadVencimientoService;
use App\Services\SolicitudMatchingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\CreatesTurnoScenarios;
use Tests\TestCase;

class SolicitudesDisponibilidadVencimientoTest extends TestCase
{
    use CreatesTurnoScenarios;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->travelTo('2026-08-27 19:00:00');
    }

    public function test_activa_de_ayer_expira_y_no_es_visible(): void
    {
        [$alumno, $materia] = $this->alumnoYMateria();
        $solicitud = $this->crearSolicitudDisponibilidad($alumno, $materia, [
            'fecha' => '2026-08-26',
            'hora_inicio' => '17:00:00',
            'hora_fin' => '21:00:00',
        ]);

        $pagina = $this->cargarPagina($alumno);

        $this->assertSame(SolicitudDisponibilidad::ESTADO_EXPIRADA, $solicitud->fresh()->estado);
        $this->assertNotContains($solicitud->id, collect($pagina->misSolicitudes)->pluck('id')->all());
    }

    public function test_activa_de_hoy_sin_slots_futuros_expira(): void
    {
        [$alumno, $materia] = $this->alumnoYMateria();
        $solicitud = $this->crearSolicitudDisponibilidad($alumno, $materia, [
            'fecha' => '2026-08-27',
            'hora_inicio' => '17:00:00',
            'hora_fin' => '19:00:00',
        ]);

        app(SolicitudDisponibilidadVencimientoService::class)->sincronizarActivas(alumnoId: $alumno->id);

        $this->assertSame(SolicitudDisponibilidad::ESTADO_EXPIRADA, $solicitud->fresh()->estado);
    }

    public function test_rango_parcial_conserva_solo_el_slot_con_inicio_futuro_y_sigue_visible(): void
    {
        [$alumno, $materia] = $this->alumnoYMateria();
        $solicitud = $this->crearSolicitudDisponibilidad($alumno, $materia, [
            'fecha' => '2026-08-27',
            'hora_inicio' => '17:00:00',
            'hora_fin' => '21:00:00',
        ]);
        $service = app(SolicitudDisponibilidadVencimientoService::class);

        $pagina = $this->cargarPagina($alumno);

        $this->assertSame([['20:00:00', '21:00:00']], $service->slotsFuturosOfertables($solicitud->fresh()));
        $this->assertSame(SolicitudDisponibilidad::ESTADO_ACTIVA, $solicitud->fresh()->estado);
        $this->assertContains($solicitud->id, collect($pagina->misSolicitudes)->pluck('id')->all());
    }

    public function test_cuando_ya_no_queda_inicio_futuro_la_misma_solicitud_expira(): void
    {
        [$alumno, $materia] = $this->alumnoYMateria();
        $solicitud = $this->crearSolicitudDisponibilidad($alumno, $materia, [
            'fecha' => '2026-08-27',
            'hora_inicio' => '17:00:00',
            'hora_fin' => '21:00:00',
        ]);
        $this->travelTo('2026-08-27 20:00:00');

        app(SolicitudDisponibilidadVencimientoService::class)->sincronizarActivas();

        $this->assertSame(SolicitudDisponibilidad::ESTADO_EXPIRADA, $solicitud->fresh()->estado);
    }

    public function test_solicitud_futura_continua_activa_y_visible(): void
    {
        [$alumno, $materia] = $this->alumnoYMateria();
        $solicitud = $this->crearSolicitudDisponibilidad($alumno, $materia, [
            'fecha' => '2026-08-28',
            'hora_inicio' => '09:00:00',
            'hora_fin' => '10:00:00',
        ]);

        $pagina = $this->cargarPagina($alumno);

        $this->assertSame(SolicitudDisponibilidad::ESTADO_ACTIVA, $solicitud->fresh()->estado);
        $this->assertContains($solicitud->id, collect($pagina->misSolicitudes)->pluck('id')->all());
    }

    public function test_tomada_y_cancelada_no_cambian_y_no_son_visibles(): void
    {
        [$alumno, $materia] = $this->alumnoYMateria();
        $tomada = $this->crearSolicitudDisponibilidad($alumno, $materia, [
            'estado' => SolicitudDisponibilidad::ESTADO_TOMADA,
            'fecha' => '2026-08-28',
        ]);
        $cancelada = $this->crearSolicitudDisponibilidad($alumno, $materia, [
            'estado' => SolicitudDisponibilidad::ESTADO_CANCELADA,
            'fecha' => '2026-08-28',
        ]);

        $pagina = $this->cargarPagina($alumno);
        $visibles = collect($pagina->misSolicitudes)->pluck('id')->all();

        $this->assertSame(SolicitudDisponibilidad::ESTADO_TOMADA, $tomada->fresh()->estado);
        $this->assertSame(SolicitudDisponibilidad::ESTADO_CANCELADA, $cancelada->fresh()->estado);
        $this->assertNotContains($tomada->id, $visibles);
        $this->assertNotContains($cancelada->id, $visibles);
    }

    public function test_expires_at_pasado_expira_solicitud_futura(): void
    {
        [$alumno, $materia] = $this->alumnoYMateria();
        $solicitud = $this->crearSolicitudDisponibilidad($alumno, $materia, [
            'fecha' => '2026-08-28',
            'expires_at' => now()->subMinute(),
        ]);

        (new ExpirarOfertasSolicitudJob())->handle(app(SolicitudDisponibilidadVencimientoService::class));

        $this->assertSame(SolicitudDisponibilidad::ESTADO_EXPIRADA, $solicitud->fresh()->estado);
    }

    public function test_expirar_solicitud_solo_expira_sus_ofertas_pendientes(): void
    {
        [$alumno, $materia] = $this->alumnoYMateria();
        $solicitud = $this->crearSolicitudDisponibilidad($alumno, $materia, [
            'fecha' => '2026-08-26',
        ]);
        $pendiente = $this->crearOfertaSolicitud($solicitud, $this->crearProfesor());
        $aceptada = $this->crearOfertaSolicitud($solicitud, $this->crearProfesor(), [
            'estado' => OfertaSolicitud::ESTADO_ACEPTADA,
        ]);
        $rechazada = $this->crearOfertaSolicitud($solicitud, $this->crearProfesor(), [
            'estado' => OfertaSolicitud::ESTADO_RECHAZADA,
        ]);

        $service = app(SolicitudDisponibilidadVencimientoService::class);
        $service->sincronizarActivas();
        $service->sincronizarActivas();

        $this->assertSame(SolicitudDisponibilidad::ESTADO_EXPIRADA, $solicitud->fresh()->estado);
        $this->assertSame(OfertaSolicitud::ESTADO_EXPIRADA, $pendiente->fresh()->estado);
        $this->assertSame(OfertaSolicitud::ESTADO_ACEPTADA, $aceptada->fresh()->estado);
        $this->assertSame(OfertaSolicitud::ESTADO_RECHAZADA, $rechazada->fresh()->estado);
    }

    public function test_matching_genera_oferta_solo_para_el_slot_estrictamente_futuro(): void
    {
        [$alumno, $materia] = $this->alumnoYMateria();
        $profesor = $this->crearProfesor();
        $this->asignarMateriaProfesor($profesor, $materia);
        $this->crearDisponibilidad($profesor, [
            'dia_semana' => 4,
            'hora_inicio' => '17:00:00',
            'hora_fin' => '21:00:00',
        ]);
        $solicitud = $this->crearSolicitudDisponibilidad($alumno, $materia, [
            'fecha' => '2026-08-27',
            'hora_inicio' => '17:00:00',
            'hora_fin' => '21:00:00',
        ]);

        (new GenerarOfertasInteligentesDesdeSolicitudesJob($solicitud->id))
            ->handle(app(SolicitudMatchingService::class));

        $this->assertDatabaseCount('ofertas_solicitud', 1);
        $this->assertDatabaseHas('ofertas_solicitud', [
            'solicitud_id' => $solicitud->id,
            'hora_inicio' => '20:00:00',
            'hora_fin' => '21:00:00',
            'estado' => OfertaSolicitud::ESTADO_PENDIENTE,
        ]);
        $this->assertDatabaseMissing('ofertas_solicitud', [
            'solicitud_id' => $solicitud->id,
            'hora_inicio' => '19:00:00',
        ]);
    }

    private function alumnoYMateria(): array
    {
        return [$this->crearAlumno(), $this->crearMateria()];
    }

    private function cargarPagina($alumno): SolicitudesDisponibilidad
    {
        $this->actingAs($alumno);
        $pagina = app(SolicitudesDisponibilidad::class);
        $pagina->cargarMisSolicitudes();

        return $pagina;
    }
}
