<?php

namespace Tests\Feature\Admin;

use App\Filament\Pages\Auditoria;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\DataProvider;
use Spatie\Activitylog\Models\Activity;
use Tests\TestCase;

class AuditoriaTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // El usuario de prueba no debe agregar una actividad ajena a los escenarios.
        $this->actingAs(User::withoutEvents(fn () => User::factory()->create(['role' => 'admin', 'activo' => true])));
        Filament::setCurrentPanel(Filament::getPanel('admin'));
        $this->travelTo('2026-09-28 15:00:00');
    }

    protected function tearDown(): void
    {
        $this->travelBack();
        parent::tearDown();
    }

    public function test_tabla_y_detalle_muestran_fecha_sin_segundos_y_no_modifican_el_registro(): void
    {
        $actividad = $this->actividad('2026-09-28 14:35:20');
        $antes = $actividad->getRawOriginal();
        $cantidad = Activity::count();

        Livewire::test(Auditoria::class)
            ->assertSet('registros.0.fecha', '28/09/2026 14:35')
            ->assertSee('28/09/2026 14:35')
            ->assertDontSee('2026-09-28 14:35:20')
            ->assertDontSee('28/09/2026 14:35:20')
            ->call('verDetalle', $actividad->id)
            ->assertSet('detalle.fecha', '28/09/2026 14:35')
            ->assertDontSee('14:35:20');

        $this->assertSame($antes, $actividad->fresh()->getRawOriginal());
        $this->assertSame($cantidad, Activity::count());
    }

    public static function rangos(): array
    {
        return [
            'Desde inclusive' => ['2026-09-28', '2026-09-30', [3, 2, 1]],
            'Hasta inclusive' => ['2026-09-01', '2026-09-28', [2, 1, 0]],
            'Desde y Hasta' => ['2026-09-28', '2026-09-28', [2, 1]],
        ];
    }

    #[DataProvider('rangos')]
    public function test_filtros_siguen_operando_sobre_datetime(string $desde, string $hasta, array $indices): void
    {
        $actividades = collect([
            '2026-09-27 23:59:59', '2026-09-28 00:00:00',
            '2026-09-28 23:59:59', '2026-09-29 00:00:00',
        ])->map(fn ($fecha) => $this->actividad($fecha));
        $antes = $actividades->map(fn ($actividad) => $actividad->getRawOriginal())->all();

        $pagina = Livewire::test(Auditoria::class)
            ->set('fechaInicio', $desde)
            ->set('fechaFin', $hasta)
            ->call('aplicarFiltros');

        $this->assertSame(array_map(fn ($indice) => $actividades[$indice]->id, $indices), array_column($pagina->get('registros'), 'id'));
        $this->assertSame($antes, $actividades->map(fn ($actividad) => $actividad->fresh()->getRawOriginal())->all());
    }

    public function test_orden_cronologico_conserva_meses_y_segundos_reales(): void
    {
        $agosto = $this->actividad('2026-08-31 23:59:59');
        $septiembre = $this->actividad('2026-09-01 00:00:00');
        // Mismo texto visible, pero diferentes segundos; el más reciente se crea primero.
        $reciente = $this->actividad('2026-09-28 14:35:50');
        $anterior = $this->actividad('2026-09-28 14:35:20');

        $pagina = Livewire::test(Auditoria::class)
            ->set('fechaInicio', '2026-08-01')
            ->set('fechaFin', '2026-09-30')
            ->call('aplicarFiltros');

        $this->assertSame([$reciente->id, $anterior->id, $septiembre->id, $agosto->id], array_column($pagina->get('registros'), 'id'));
        $pagina->assertSet('registros.0.fecha', '28/09/2026 14:35')
            ->assertSet('registros.1.fecha', '28/09/2026 14:35');
    }

    private function actividad(string $fecha): Activity
    {
        return Activity::create([
            'log_name' => 'test', 'description' => 'updated', 'event' => 'updated',
            'subject_type' => User::class, 'subject_id' => auth()->id(),
            'causer_type' => User::class, 'causer_id' => auth()->id(),
            'properties' => ['old' => ['name' => 'Antes'], 'attributes' => ['name' => 'Después']],
            'created_at' => $fecha, 'updated_at' => $fecha,
        ])->fresh();
    }
}
