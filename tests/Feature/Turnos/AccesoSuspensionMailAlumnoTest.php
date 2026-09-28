<?php

namespace Tests\Feature\Turnos;

use App\Mail\AlumnoClaseSuspendidaPorProfesor;
use App\Models\Pago;
use App\Models\Turno;
use App\Services\AccesoMailAlumnoService;
use Filament\Auth\Pages\Login;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\URL;
use Livewire\Livewire;
use Tests\Support\CreatesTurnoScenarios;
use Tests\TestCase;

class AccesoSuspensionMailAlumnoTest extends TestCase
{
    use CreatesTurnoScenarios;
    use RefreshDatabase {
        refreshDatabase as private refreshSqliteDatabase;
    }

    private const ORIGEN = 'https://suspensiones.example.test';

    public function refreshDatabase(): void
    {
        $this->assertSame('sqlite', config('database.default'));
        $this->assertSame(':memory:', config('database.connections.sqlite.database'));
        $this->refreshSqliteDatabase();
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->travelTo('2026-09-27 10:00:00');
        config(['app.url' => self::ORIGEN]);
        URL::forceRootUrl(self::ORIGEN);
        URL::forceScheme('https');
        Filament::setCurrentPanel(Filament::getPanel('alumno'));
    }

    protected function tearDown(): void
    {
        $this->travelBack();
        parent::tearDown();
    }

    public function test_correo_contiene_enlace_firmado_al_turno_suspendido_exacto(): void
    {
        [$turno] = $this->escenario();
        $correo = new AlumnoClaseSuspendidaPorProfesor($turno);

        $this->assertStringStartsWith(self::ORIGEN.'/mail/access?', $correo->urlReprogramar);
        $this->assertTrue(URL::hasValidSignature(\Illuminate\Http\Request::create($correo->urlReprogramar)));
        $this->assertSame($this->destino($turno), $this->targetFirmado($correo->urlReprogramar));
        $this->assertStringContainsString('Resolver suspensi', $correo->render());
        $this->assertStringContainsString(e($correo->urlReprogramar), $correo->render());
    }

    public function test_alumno_propietario_autenticado_llega_directamente_y_abrir_no_escribe(): void
    {
        [$turno, $alumno, $pago] = $this->escenario();
        $turnoAntes = $turno->fresh()->getRawOriginal();
        $pagoAntes = $pago->fresh()->getRawOriginal();
        $auditoriaAntes = DB::table('activity_log')->count();

        $this->actingAs($alumno)
            ->get($this->enlace($turno))
            ->assertRedirect($this->destino($turno));

        $this->get($this->destino($turno))
            ->assertOk()
            ->assertSee('Clase suspendida');

        $this->assertSame($turnoAntes, $turno->fresh()->getRawOriginal());
        $this->assertSame($pagoAntes, $pago->fresh()->getRawOriginal());
        $this->assertSame(Turno::ESTADO_SUSPENDIDO_PROFESOR, $turno->fresh()->estado);
        $this->assertDatabaseCount('turnos', 1);
        $this->assertDatabaseCount('pagos', 1);
        $this->assertDatabaseCount('activity_log', $auditoriaAntes);
    }

    public function test_sin_sesion_conserva_destino_y_regresa_despues_del_login(): void
    {
        [$turno, $alumno] = $this->escenario();

        $this->get($this->enlace($turno))
            ->assertRedirect(self::ORIGEN.'/alumno/login')
            ->assertSessionHas('url.intended', $this->destino($turno));

        Livewire::test(Login::class)
            ->fillForm(['email' => $alumno->email, 'password' => 'password'])
            ->call('authenticate')
            ->assertHasNoFormErrors()
            ->assertRedirect($this->destino($turno));

        $this->get($this->destino($turno))->assertOk();
    }

    public function test_otro_usuario_no_accede_y_debe_reautenticarse_como_destinatario(): void
    {
        [$turno] = $this->escenario();
        $otroAlumno = $this->crearAlumno();

        $this->actingAs($otroAlumno)
            ->get($this->enlace($turno))
            ->assertRedirect(self::ORIGEN.'/alumno/login')
            ->assertSessionHas('url.intended', $this->destino($turno));

        $this->assertGuest();

        Livewire::test(Login::class)
            ->fillForm(['email' => $otroAlumno->email, 'password' => 'password'])
            ->call('authenticate')
            ->assertRedirect($this->destino($turno));

        $this->get($this->destino($turno))->assertNotFound();
        $this->assertSame(Turno::ESTADO_SUSPENDIDO_PROFESOR, $turno->fresh()->estado);
    }

    public function test_firma_o_id_manipulados_se_rechazan(): void
    {
        [$turno] = $this->escenario();
        $enlace = $this->enlace($turno);

        $this->get($enlace.'&alterado=1')->assertForbidden();
        $this->get(str_replace(
            'alumno='.$turno->alumno_id,
            'alumno=9999',
            $enlace,
        ))->assertForbidden();

        $this->assertSame(Turno::ESTADO_SUSPENDIDO_PROFESOR, $turno->fresh()->estado);
    }

    /** @return array{Turno, \App\Models\User, Pago} */
    private function escenario(): array
    {
        $alumno = $this->crearAlumno();
        $turno = $this->crearTurno($alumno, $this->crearProfesor(), $this->crearMateria(), [
            'fecha' => '2026-10-10',
            'hora_inicio' => '10:00:00',
            'hora_fin' => '11:00:00',
            'estado' => Turno::ESTADO_SUSPENDIDO_PROFESOR,
            'suspendido_at' => now(),
            'suspension_motivo' => 'Motivo de prueba',
        ]);
        $pago = $this->crearPago($turno, ['estado' => Pago::ESTADO_APROBADO]);

        return [$turno, $alumno, $pago];
    }

    private function enlace(Turno $turno): string
    {
        return (new AlumnoClaseSuspendidaPorProfesor($turno))->urlReprogramar;
    }

    private function destino(Turno $turno): string
    {
        return self::ORIGEN.'/alumno/resolver-suspension/'.$turno->getKey();
    }

    private function targetFirmado(string $enlace): string
    {
        parse_str((string) parse_url($enlace, PHP_URL_QUERY), $parametros);

        return self::ORIGEN.base64_decode((string) $parametros['target'], true);
    }
}
