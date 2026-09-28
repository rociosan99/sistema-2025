<?php

namespace Tests\Feature\Admin;

use App\Filament\Pages\Reportes;
use App\Models\Turno;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\DataProvider;
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

    public static function tamanosPdf(): array
    {
        return ['una pagina' => [1], 'varias paginas' => [120]];
    }

    #[DataProvider('tamanosPdf')]
    public function test_pdf_conserva_datos_y_numera_todas_las_paginas_sin_superponer_el_pie(int $cantidad): void
    {
        config(['app.name' => 'Laravel']);
        $alumno = $this->crearAlumno(['name' => 'Micaela', 'apellido' => 'Sosa']);
        $profesor = $this->crearProfesor(['name' => 'Lucía', 'apellido' => 'Gómez']);
        $materia = $this->crearMateria(['materia_nombre' => 'Bases de Datos']);
        for ($i = 0; $i < $cantidad; $i++) {
            $this->crearTurno($alumno, $profesor, $materia, [
                'fecha' => '2026-09-20', 'estado' => Turno::ESTADO_CONFIRMADO,
                'hora_inicio' => '10:00:00', 'hora_fin' => '11:00:00', 'precio_total' => '201.00',
            ]);
        }
        $this->crearTurno($alumno, $profesor, $materia, [
            'fecha' => '2026-09-20', 'estado' => Turno::ESTADO_RECHAZADO,
        ]);
        $this->crearTurno($alumno, $profesor, $materia, [
            'fecha' => '2026-08-20', 'estado' => Turno::ESTADO_CONFIRMADO,
        ]);

        // Motor real: observamos el documento renderizado, sin simular DomPDF.
        $pdf = app('dompdf.wrapper');
        app()->instance('dompdf.wrapper', $pdf);
        $datos = [];
        view()->composer('pdf.reportes.turnos', function ($vista) use (&$datos): void {
            $datos = $vista->getData();
        });
        $filas = [];
        $pies = [];
        $pdf->getDomPDF()->setCallbacks([[
            'event' => 'end_frame',
            'f' => function ($frame, $canvas) use (&$filas, &$pies): void {
                $nodo = $frame->get_node();
                if (! $nodo instanceof \DOMElement) {
                    return;
                }
                $pagina = $canvas->get_page_number();
                $caja = $frame->get_border_box();
                if ($nodo->nodeName === 'tr') {
                    $filas[$pagina][] = $caja['y'] + $caja['h'];
                }
                if ($nodo->getAttribute('class') === 'pie') {
                    $pies[$pagina] = $caja;
                }
            },
        ]]);

        $response = $this->get(route('reportes.turnos.pdf', [
            'fechaInicio' => '2026-09-01', 'fechaFin' => '2026-09-25',
            'estado' => Turno::ESTADO_CONFIRMADO, 'emitido_por' => 'Emisor falsificado',
        ]));
        $response->assertOk()->assertHeader('content-type', 'application/pdf');
        $this->assertStringStartsWith('%PDF-', $response->getContent());
        $this->assertSame('Laravel', config('app.name'));

        // DomPDF libera los nodos del body al renderizar; conservamos los datos de la vista.
        $texto = html_entity_decode(strip_tags(view('pdf.reportes.turnos', $datos)->render()), ENT_QUOTES, 'UTF-8');
        foreach (['TutorLine', 'REPORTE DE TURNOS', 'Ana Administradora', 'Administrador',
            '25/09/2026', '22:30', '01/09/2026 al 25/09/2026', 'Clase pagada',
            'Micaela Sosa', 'Lucía Gómez', 'Bases de Datos', '20/09/2026',
            '10:00', '11:00', '$201,00', 'Documento generado por TutorLine'] as $esperado) {
            $this->assertStringContainsString($esperado, $texto);
        }
        $this->assertStringNotContainsString('Laravel', $texto);
        $this->assertStringNotContainsString('Emisor falsificado', $texto);
        $this->assertCount($cantidad, $datos['turnos']);
        $this->assertSame('2026-09-01', $datos['fechaInicio']);
        $this->assertSame('2026-09-25', $datos['fechaFin']);
        $this->assertSame(Turno::ESTADO_CONFIRMADO, $datos['estado']);

        $canvas = $pdf->getDomPDF()->getCanvas();
        $total = $canvas->get_page_count();
        $cantidad === 1 ? $this->assertSame(1, $total) : $this->assertGreaterThan(2, $total);
        // DejaVu Sans se escribe como UTF-16BE en los streams PDF sin comprimir.
        $salida = $pdf->output(['compress' => false]);
        $this->assertGreaterThanOrEqual($total, substr_count($salida, mb_convert_encoding('TutorLine', 'UTF-16BE', 'UTF-8')));
        $this->assertStringNotContainsString(mb_convert_encoding('Laravel', 'UTF-16BE', 'UTF-8'), $salida);
        for ($pagina = 1; $pagina <= $total; $pagina++) {
            $etiqueta = mb_convert_encoding("Página {$pagina} de {$total}", 'UTF-16BE', 'UTF-8');
            $this->assertSame(1, substr_count($salida, $etiqueta));
            $this->assertArrayHasKey($pagina, $pies);
            $this->assertLessThan($pies[$pagina]['y'], max($filas[$pagina]));
            $this->assertLessThan($canvas->get_height() - 15, $pies[$pagina]['y'] + $pies[$pagina]['h']);
        }
    }
}
