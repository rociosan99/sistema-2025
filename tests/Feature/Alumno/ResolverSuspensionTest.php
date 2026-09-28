<?php

namespace Tests\Feature\Alumno;

use App\Filament\Alumno\Pages\ResolverSuspension;
use App\Models\Tema;
use App\Models\Turno;
use App\Services\SlotService;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Support\CreatesTurnoScenarios;
use Tests\TestCase;

class ResolverSuspensionTest extends TestCase
{
    use CreatesTurnoScenarios;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo('2026-09-27 10:00:00');
        Filament::setCurrentPanel(Filament::getPanel('alumno'));
        Mail::fake();
    }

    public static function estados(): array
    {
        return array_map(fn ($estado) => [$estado], ['disponible', 'vencida', 'vigente', 'sin_candidato', 'sin_calificaciones', 'resuelta']);
    }

    #[DataProvider('estados')]
    public function test_presentacion_conserva_datos_estados_y_acciones_sin_escrituras(string $estado): void
    {
        $alumno = $this->crearAlumno();
        $profesor = $this->crearProfesor(['name' => 'José', 'apellido' => 'Merlo']);
        $reemplazo = $this->crearProfesor(['name' => 'Lucía', 'apellido' => 'Gómez']);
        $materia = $this->crearMateria(['materia_nombre' => 'Algoritmos y Estructuras de Datos I']);
        $tema = Tema::create(['tema_nombre' => 'Diseño de algoritmos']);
        $turno = $this->crearTurno($alumno, $profesor, $materia, [
            'fecha' => '2026-09-28', 'hora_inicio' => '20:00:00', 'hora_fin' => '21:00:00',
            'tema_id' => $tema->getKey(), 'estado' => Turno::ESTADO_SUSPENDIDO_PROFESOR,
            'suspension_motivo' => 'viaje', 'precio_total' => '999.00',
        ]);
        $pago = $this->crearPago($turno, ['monto' => '30.00']);
        if (in_array($estado, ['vencida', 'vigente'])) {
            $turno->update([
                'reemplazo_profesor_propuesto_id' => $reemplazo->id,
                'reemplazo_fecha' => '2026-09-28', 'reemplazo_hora_inicio' => '20:00:00',
                'reemplazo_hora_fin' => '21:00:00',
                'reemplazo_expires_at' => $estado === 'vigente' ? now()->addHour() : now()->subMinute(),
            ]);
        }
        if ($estado === 'resuelta') {
            $turno->update(['estado' => Turno::ESTADO_CONFIRMADO]);
        }
        $slot = [
            'profesor_id' => $reemplazo->id, 'profesor_nombre' => 'Lucía Gómez',
            'fecha' => '2026-09-28', 'hora_inicio' => '20:00:00', 'hora_fin' => '21:00:00',
            'rating_avg' => 4.0, 'rating_count' => $estado === 'sin_calificaciones' ? 0 : 4,
        ];
        $this->mock(SlotService::class, function ($mock) use ($estado, $slot): void {
            if (in_array($estado, ['vigente', 'resuelta'])) {
                $mock->shouldNotReceive('obtenerSlotsPorMateria');
            } else {
                $mock->shouldReceive('obtenerSlotsPorMateria')->once()
                    ->andReturn(collect($estado === 'sin_candidato' ? [] : [$slot]));
            }
        });
        $turnoAntes = $turno->fresh()->getRawOriginal();
        $pagoAntes = $pago->fresh()->getRawOriginal();
        $auditorias = DB::table('activity_log')->count();

        $response = $this->actingAs($alumno)->get(ResolverSuspension::getUrl(['record' => $turno->id], panel: 'alumno'));
        $response->assertOk()->assertSee('Clase suspendida')->assertSee('José Merlo')
            ->assertSee('Algoritmos y Estructuras de Datos I')->assertSee('Diseño de algoritmos')
            ->assertSee('28/09/2026')->assertSee('20:00 - 21:00')->assertSee('viaje')
            ->assertSee('Tu pago continúa vigente')->assertSee('No necesitás volver a pagar esta clase.')
            ->assertSee('$30,00')->assertDontSee('$999,00')
            ->assertSee('id="rs-clase"', false)->assertSee('id="rs-pago"', false);

        if ($estado === 'resuelta') {
            $response->assertSee('Esta suspensión ya fue resuelta o el turno cambió de estado.')
                ->assertDontSee('id="rs-opcion-1"', false)->assertDontSee('id="rs-opcion-2"', false);
        } else {
            $response->assertSee('id="rs-opcion-1"', false)->assertSee('id="rs-opcion-2"', false)
                ->assertSee('¿Cómo querés resolver la suspensión?');
            if ($estado === 'vigente') {
                $response->assertSee('Propuesta pendiente de respuesta')->assertSee('Lucía Gómez')
                    ->assertSee('27/09/2026 11:00')->assertSee('wire:click="cancelarPropuesta"', false)
                    ->assertSee('Reprogramación bloqueada mientras exista una propuesta')
                    ->assertDontSee('wire:click="solicitarReemplazo"', false);
            } else {
                $response->assertSee('Reprogramar con José Merlo')
                    ->assertSee('href="'.url('/alumno/reprogramar-turno?turno='.$turno->id).'"', false);
                if ($estado === 'sin_candidato') {
                    $response->assertSee('No encontramos un profesor disponible para el horario original.')
                        ->assertDontSee('wire:click="solicitarReemplazo"', false);
                } else {
                    $response->assertSee('Profesor reemplazante disponible')->assertSee('Lucía Gómez')
                        ->assertSee('wire:click="solicitarReemplazo"', false);
                    if ($estado === 'sin_calificaciones') {
                        $response->assertSee('Sin calificaciones');
                    } else {
                        $response->assertSee('4,0 ★')->assertSee('4 calificaciones');
                    }
                }
                if ($estado === 'vencida') {
                    $response->assertSee('La propuesta anterior venció. Podés solicitar otro reemplazo.');
                }
            }
        }
        $this->assertTrue(mb_check_encoding($response->getContent(), 'UTF-8'));
        $this->assertSame($turnoAntes, $turno->fresh()->getRawOriginal());
        $this->assertSame($pagoAntes, $pago->fresh()->getRawOriginal());
        $this->assertSame($auditorias, DB::table('activity_log')->count());
        Mail::assertNothingSent();
    }
}
