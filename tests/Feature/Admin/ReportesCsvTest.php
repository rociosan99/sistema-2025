<?php

namespace Tests\Feature\Admin;

use App\Filament\Pages\Reportes;
use App\Models\Tema;
use App\Models\Turno;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Support\CreatesTurnoScenarios;
use Tests\TestCase;

class ReportesCsvTest extends TestCase
{
    use CreatesTurnoScenarios;
    use RefreshDatabase;

    private User $administrador;

    protected function setUp(): void
    {
        parent::setUp();
        $this->assertSame('sqlite', config('database.default'));
        $this->assertSame(':memory:', config('database.connections.sqlite.database'));
        // Compatibilidad del SQL MySQL existente: no cambiamos la consulta productiva.
        DB::connection()->getPdo()->sqliteCreateFunction('CONCAT', static function (...$valores) {
            return in_array(null, $valores, true) ? null : implode('', $valores);
        });
        $this->administrador = User::factory()->create([
            'name' => 'Ana', 'apellido' => 'Muñoz', 'role' => 'admin', 'activo' => true,
        ]);
        $this->actingAs($this->administrador);
        $this->travelTo(Carbon::parse('2026-09-28 10:35:00', config('app.timezone')));
    }

    protected function tearDown(): void
    {
        $this->travelBack();
        parent::tearDown();
    }

    public function test_csv_incluye_contexto_seguro_y_conserva_utf8_escaping_e_importes(): void
    {
        config(['app.name' => 'Laravel']);
        $this->administrador->update(['name' => 'Ana; "María"']);
        $alumno = $this->crearAlumno(['name' => 'José; "Pepe"', 'apellido' => 'Núñez']);
        $profesor = $this->crearProfesor(['name' => 'Lucía', 'apellido' => 'Gómez']);
        $materia = $this->crearMateria(['materia_nombre' => "Diseño; \"SQL\"\nAvanzado"]);
        $tema = Tema::create(['tema_nombre' => 'Rutas \\"especiales"; ñ']);
        $turno = $this->crearTurno($alumno, $profesor, $materia, [
            'tema_id' => $tema->getKey(), 'fecha' => '2026-09-20',
            'estado' => Turno::ESTADO_CONFIRMADO, 'precio_total' => '1234.50',
        ]);

        [$meta, $columnas, $detalle, $contenido] = $this->leerCsv([
            'fechaInicio' => '2026-09-01', 'fechaFin' => '2026-09-28',
            'estado' => Turno::ESTADO_CONFIRMADO,
            'emitido_por' => 'Falsificado', 'name' => 'Falsificado', 'role' => 'Superadmin',
        ]);

        $this->assertStringStartsWith("\xEF\xBB\xBFTutorLine\n", $contenido);
        $this->assertTrue(mb_check_encoding($contenido, 'UTF-8'));
        $this->assertStringNotContainsString('Laravel', $contenido);
        $this->assertStringNotContainsString('Falsificado', $contenido);
        $this->assertStringNotContainsString('Superadmin', $contenido);
        $this->assertStringNotContainsString('Página', $contenido);
        $this->assertSame('Laravel', config('app.name'));
        $this->assertArrayHasKey('Reporte de turnos', $meta);
        $this->assertArrayHasKey('Criterios del reporte', $meta);
        $this->assertSame('Ana; "María" Muñoz', $meta['Emitido por:']);
        $this->assertSame('Administrador', $meta['Rol:']);
        $this->assertSame('28/09/2026', $meta['Fecha de emisión:']);
        $this->assertSame('10:35', $meta['Hora de emisión:']);
        $this->assertSame('01/09/2026 al 28/09/2026', $meta['Período analizado:']);
        $this->assertSame('01/09/2026', $meta['Desde:']);
        $this->assertSame('28/09/2026', $meta['Hasta:']);
        $this->assertSame('Clase pagada', $meta['Estado:']);
        $this->assertSame('1', $meta['Total de registros:']);
        $this->assertSame([
            'ID', 'Alumno', 'Profesor', 'Materia', 'Tema', 'Fecha',
            'Hora inicio', 'Hora fin', 'Estado', 'Precio total',
        ], $columnas);
        $this->assertSame([[
            (string) $turno->id, 'José; "Pepe" Núñez', 'Lucía Gómez',
            $materia->materia_nombre, $tema->tema_nombre, '20/09/2026',
            '10:00', '11:00', 'Clase pagada', '1234.5',
        ]], $detalle);
    }

    public static function cantidades(): array
    {
        return ['filtros y orden' => [3], 'limite 300' => [305]];
    }

    #[DataProvider('cantidades')]
    public function test_csv_y_pdf_exportan_las_mismas_columnas_registros_y_orden(int $cantidad): void
    {
        $alumno = $this->crearAlumno();
        $profesor = $this->crearProfesor();
        $materia = $this->crearMateria();
        $incluidos = [];
        for ($i = 0; $i < $cantidad; $i++) {
            $inicio = Carbon::parse('2026-09-01 10:00')->addDays($i % 2 === 0 ? 0 : 27)->addMinutes($i);
            $turno = $this->crearTurno($alumno, $profesor, $materia, [
                'fecha' => $inicio->toDateString(), 'hora_inicio' => $inicio->format('H:i:s'),
                'hora_fin' => $inicio->copy()->addHour()->format('H:i:s'),
                'estado' => Turno::ESTADO_CONFIRMADO, 'precio_total' => '201.50',
            ]);
            // SQLite almacena el cast como datetime; reproducimos la columna DATE de MySQL.
            DB::table('turnos')->where('id', $turno->id)->update(['fecha' => $inicio->toDateString()]);
            $incluidos[] = $turno;
        }
        foreach ([['2026-08-31', Turno::ESTADO_CONFIRMADO], ['2026-09-29', Turno::ESTADO_CONFIRMADO],
            ['2026-09-20', Turno::ESTADO_RECHAZADO]] as [$fecha, $estado]) {
            $this->crearTurno($alumno, $profesor, $materia, ['fecha' => $fecha, 'estado' => $estado]);
        }
        $filtros = ['fechaInicio' => '2026-09-01', 'fechaFin' => '2026-09-28', 'estado' => Turno::ESTADO_CONFIRMADO];
        [$meta, $columnas, $detalle] = $this->leerCsv($filtros);

        $datosPdf = [];
        view()->composer('pdf.reportes.turnos', function ($vista) use (&$datosPdf): void {
            $datosPdf = $vista->getData();
        });
        $this->get(route('reportes.turnos.pdf', $filtros))->assertOk()->assertHeader('content-type', 'application/pdf');
        $this->assertCount(min(300, $cantidad), $detalle);
        $this->assertSame((string) count($detalle), $meta['Total de registros:']);
        $this->assertSame(array_column($datosPdf['turnos'], 'id'), array_map('intval', array_column($detalle, 0)));
        $esperados = collect($incluidos)->sortByDesc(fn ($turno) => $turno->fecha->format('Y-m-d').' '.$turno->hora_inicio)
            ->take(300)->pluck('id')->values()->all();
        $this->assertSame($esperados, array_map('intval', array_column($detalle, 0)));
        $this->assertSame($datosPdf['emitidoPor'], $meta['Emitido por:']);
        $this->assertSame($datosPdf['fechaEmision'], $meta['Fecha de emisión:']);
        $this->assertSame($datosPdf['horaEmision'], $meta['Hora de emisión:']);
        $this->assertSame($datosPdf['estadoLabel'], $meta['Estado:']);

        $htmlPdf = view('pdf.reportes.turnos', $datosPdf)->render();
        preg_match_all('/<th\b[^>]*>(.*?)<\/th>/s', $htmlPdf, $titulos);
        $this->assertSame($titulos[1], $columnas);
        $this->assertSame(array_map(static fn ($row) => [
            (string) $row['id'], $row['alumno'], $row['profesor'], $row['materia'], $row['tema'],
            Carbon::parse($row['fecha'])->format('d/m/Y'), $row['hora_inicio'], $row['hora_fin'],
            Reportes::estadoLabel($row['estado']), (string) $row['precio_total'],
        ], $datosPdf['turnos']), $detalle);
    }

    public function test_sin_estado_incluye_todos_y_reporte_vacio_conserva_contexto(): void
    {
        [$meta, , $detalle] = $this->leerCsv(['fechaInicio' => '2026-09-01', 'fechaFin' => '2026-09-28']);
        $this->assertSame('Todos', $meta['Estado:']);
        $this->assertSame('0', $meta['Total de registros:']);
        $this->assertSame([], $detalle);

        $alumno = $this->crearAlumno();
        $profesor = $this->crearProfesor();
        $materia = $this->crearMateria();
        foreach ([Turno::ESTADO_CONFIRMADO, Turno::ESTADO_RECHAZADO] as $estado) {
            $this->crearTurno($alumno, $profesor, $materia, ['fecha' => '2026-09-20', 'estado' => $estado]);
        }
        [$meta, , $detalle] = $this->leerCsv(['fechaInicio' => '2026-09-01', 'fechaFin' => '2026-09-28']);
        $this->assertSame('Todos', $meta['Estado:']);
        $this->assertSame('2', $meta['Total de registros:']);
        $this->assertEqualsCanonicalizing(['Clase pagada', 'Rechazado'], array_column($detalle, 8));
    }

    public function test_emisor_sin_nombre_usa_email_y_fechas_predeterminadas_coinciden_con_pdf(): void
    {
        $this->administrador->update(['name' => '', 'apellido' => '']);
        [$meta] = $this->leerCsv([]);
        $this->assertSame($this->administrador->email, $meta['Emitido por:']);
        $this->assertSame(now()->subDays(30)->format('d/m/Y'), $meta['Desde:']);
        $this->assertSame(now()->format('d/m/Y'), $meta['Hasta:']);
    }

    private function leerCsv(array $filtros): array
    {
        $response = $this->get(route('reportes.turnos.excel', $filtros));
        $response->assertOk()->assertHeader('content-type', 'text/csv; charset=UTF-8');
        $contenido = $response->streamedContent();
        $archivo = fopen('php://temp', 'w+');
        fwrite($archivo, substr($contenido, 3));
        rewind($archivo);
        $filas = [];
        while (($fila = fgetcsv($archivo, 0, ';', '"', '')) !== false) {
            $filas[] = $fila;
        }
        fclose($archivo);
        $indice = array_search('ID', array_column($filas, 0), true);
        $this->assertNotFalse($indice);
        $this->assertSame([null], $filas[$indice - 1]);
        $meta = [];
        foreach (array_slice($filas, 0, $indice) as $fila) {
            if ($fila[0] !== null) {
                $meta[$fila[0]] = $fila[1] ?? null;
            }
        }

        return [$meta, $filas[$indice], array_slice($filas, $indice + 1), $contenido];
    }
}
