<?php

namespace Tests\Feature\Ofertas;

use App\Jobs\EnviarRecordatorioPago24hJob;
use App\Mail\ProfesorRespondioTurno;
use App\Mail\RecordatorioPagoTurno;
use App\Models\Turno;
use App\Services\AccesoMailAlumnoService;
use Filament\Auth\Pages\Login;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\GoogleProvider;
use Laravel\Socialite\Two\User as GoogleUser;
use Livewire\Livewire;
use Mockery;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Support\CreatesTurnoScenarios;
use Tests\TestCase;

class AccesoPagoMailAlumnoTest extends TestCase
{
    use CreatesTurnoScenarios;
    use RefreshDatabase {
        refreshDatabase as private refreshSqliteDatabase;
    }

    private const ORIGEN = 'https://correos.example.test';

    public function refreshDatabase(): void
    {
        $this->assertSame('sqlite', config('database.default'));
        $this->assertSame(':memory:', config('database.connections.sqlite.database'));
        $this->refreshSqliteDatabase();
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo('2026-08-25 10:00:00');
        config(['app.url' => self::ORIGEN, 'services.google.redirect' => self::ORIGEN.'/auth/google/callback']);
        $this->usarOrigen(self::ORIGEN);
        Filament::setCurrentPanel(Filament::getPanel('alumno'));
    }

    public static function tiposEnlace(): array
    {
        return ['nuevo' => [false], 'compatible mp.pagar.mail' => [true]];
    }

    #[DataProvider('tiposEnlace')]
    public function test_alumno_correcto_conserva_sesion_y_abre_pago_sin_escrituras(bool $antiguo): void
    {
        [$turno, $alumno] = $this->escenario();
        $antes = $turno->fresh()->getRawOriginal();
        $auditoria = DB::table('activity_log')->count();
        $this->actingAs($alumno)->withSession(['conservar' => 'si'])
            ->get($this->enlace($turno, $antiguo))->assertRedirect($this->destino($turno))
            ->assertSessionHas('conservar', 'si');
        $this->assertAuthenticatedAs($alumno);
        $this->get($this->destino($turno))->assertOk()->assertSee('Completar pago');
        $this->assertSame($antes, $turno->fresh()->getRawOriginal());
        $this->assertDatabaseCount('pagos', 0);
        $this->assertDatabaseCount('creditos', 0);
        $this->assertDatabaseCount('activity_log', $auditoria);
    }

    #[DataProvider('tiposEnlace')]
    public function test_invitado_login_filament_conserva_destino(bool $antiguo): void
    {
        [$turno, $alumno] = $this->escenario();
        $this->get($this->enlace($turno, $antiguo))->assertRedirect(self::ORIGEN.'/alumno/login')
            ->assertSessionHas('url.intended', $this->destino($turno));
        Livewire::test(Login::class)->fillForm(['email' => $alumno->email, 'password' => 'password'])
            ->call('authenticate')->assertHasNoFormErrors()->assertRedirect($this->destino($turno));
        $this->get($this->destino($turno))->assertOk();
    }

    #[DataProvider('tiposEnlace')]
    public function test_otro_usuario_debe_autenticarse_como_destinatario(bool $antiguo): void
    {
        [$turno, $alumno] = $this->escenario();
        $this->actingAs($this->crearAlumno())->withSession(['anterior' => 'eliminar'])
            ->get($this->enlace($turno, $antiguo))->assertRedirect(self::ORIGEN.'/alumno/login')
            ->assertSessionMissing('anterior')->assertSessionHas('url.intended', $this->destino($turno));
        $this->assertGuest();
        Livewire::test(Login::class)->fillForm(['email' => $alumno->email, 'password' => 'password'])
            ->call('authenticate')->assertRedirect($this->destino($turno));
        $this->assertAuthenticatedAs($alumno);
        $this->get($this->destino($turno))->assertOk();
    }

    public function test_login_tradicional_breeze_conserva_pago(): void
    {
        [$turno, $alumno] = $this->escenario();
        $this->get($this->enlace($turno));
        $this->post(self::ORIGEN.'/login', ['email' => $alumno->email, 'password' => 'password'])
            ->assertRedirect($this->destino($turno));
        $this->get($this->destino($turno))->assertOk();
    }

    public function test_nuevo_correo_en_peticion_local_se_firma_desde_app_url(): void
    {
        [$turno] = $this->escenario();
        $this->get('http://127.0.0.1:8000/');
        $this->usarOrigen('http://127.0.0.1:8000');
        $enlace = $this->enlace($turno);
        $this->assertStringStartsWith(self::ORIGEN.'/mail/access?', $enlace);
        $this->assertTrue(URL::hasValidSignature(Request::create($enlace)));
        $this->assertStringStartsWith('http://127.0.0.1:8000/', route('auth.google.redirect'));
    }

    public function test_correo_antiguo_local_se_valida_antes_de_normalizar_y_google_recupera_pago(): void
    {
        [$turno, $alumno] = $this->escenario();
        $this->usarOrigen('http://127.0.0.1:8000');
        $original = $this->enlace($turno, true);
        $this->assertTrue(URL::hasValidSignature(Request::create($original)));
        // Cambiar solamente el host no debe permitir saltar la firma original.
        $alterado = str_replace('http://127.0.0.1:8000', self::ORIGEN, $original);
        $this->get($alterado)->assertForbidden();
        $canonico = $this->get($original)->assertRedirect()->headers->get('Location');
        $this->assertStringStartsWith(self::ORIGEN.'/mail/access?', $canonico);
        $this->assertTrue(URL::hasValidSignature(Request::create($canonico)));
        session()->flush();
        Auth::forgetGuards();
        $this->usarOrigen(self::ORIGEN);
        $this->get($canonico)->assertRedirect(self::ORIGEN.'/alumno/login')
            ->assertSessionHas('url.intended', $this->destino($turno));
        $this->simularGoogle($alumno);
        $this->get(self::ORIGEN.'/auth/google?panel=alumno')->assertRedirect('https://accounts.google.com/o/oauth2/auth');
        $this->get(self::ORIGEN.'/auth/google/callback?code=simulado')->assertRedirect($this->destino($turno));
        $this->get($this->destino($turno))->assertOk();
    }

    public function test_google_desde_local_normaliza_pago_antes_de_contactar_proveedor(): void
    {
        [$turno] = $this->escenario();
        Socialite::shouldReceive('driver')->never();
        $this->withSession(['url.intended' => 'http://127.0.0.1:8000/alumno/completar-pago/'.$turno->id]);
        $url = $this->get('http://127.0.0.1:8000/auth/google?panel=alumno')
            ->assertRedirect()->headers->get('Location');
        $this->assertStringStartsWith(self::ORIGEN.'/mail/access?', $url);
        session()->flush();
        $this->get($url)->assertSessionHas('url.intended', $this->destino($turno));
    }

    #[DataProvider('tiposEnlace')]
    public function test_firma_y_destinatario_manipulados_se_rechazan(bool $antiguo): void
    {
        [$turno] = $this->escenario();
        $enlace = $this->enlace($turno, $antiguo);
        $this->get($enlace.'&alterado=1')->assertForbidden();
        $parametro = $antiguo ? 'alumno_id' : 'alumno';
        $this->get(str_replace($parametro.'='.$turno->alumno_id, $parametro.'=9999', $enlace))->assertForbidden();
        $this->assertDatabaseCount('pagos', 0);
    }

    public function test_legacy_firmado_con_destinatario_que_no_corresponde_al_turno_se_rechaza(): void
    {
        [$turno] = $this->escenario();
        $url = URL::signedRoute('mp.pagar.mail', ['turno' => $turno->id, 'alumno_id' => $this->crearAlumno()->id]);
        $this->get($url)->assertForbidden();
    }

    public function test_page_denega_turno_ajeno_incluso_despues_del_login(): void
    {
        [$turno] = $this->escenario();
        $otro = $this->crearAlumno();
        $this->get($this->enlace($turno));
        Livewire::test(Login::class)->fillForm(['email' => $otro->email, 'password' => 'password'])
            ->call('authenticate')->assertRedirect($this->destino($turno));
        $this->get($this->destino($turno))->assertNotFound();
    }

    public function test_enlace_legacy_temporal_preserva_vencimiento(): void
    {
        [$turno] = $this->escenario();
        $this->usarOrigen('http://127.0.0.1:8000');
        $vence = now()->addMinutes(10);
        $original = URL::temporarySignedRoute('mp.pagar.mail', $vence, ['turno' => $turno->id, 'alumno_id' => $turno->alumno_id]);
        $url = $this->get($original)->headers->get('Location');
        parse_str(parse_url($url, PHP_URL_QUERY), $params);
        $this->assertSame((string) $vence->timestamp, $params['expires']);
        $this->travel(11)->minutes();
        $this->get($original)->assertForbidden();
        $this->get($url)->assertForbidden();
    }

    public static function destinosNoAdmitidos(): array
    {
        return array_map(fn ($p) => [$p], [
            'https://externo.example.test/alumno/completar-pago/1',
            '//externo.example.test', '/alumno/completar-pago/1?next=/admin',
            '/alumno/completar-pago/../1', '/alumno/completar-pago/0',
            '/alumno/resolver-suspension/1', '/alumno/turnos/1', '/reemplazos/1/aceptar',
        ]);
    }

    #[DataProvider('destinosNoAdmitidos')]
    public function test_whitelist_no_admite_otros_destinos(string $path): void
    {
        $this->assertFalse(app(AccesoMailAlumnoService::class)->esDestino($path));
        $url = URL::signedRoute('mail.access', ['panel' => 'alumno', 'alumno' => 1, 'target' => base64_encode($path)]);
        $this->get($url)->assertStatus(str_starts_with($path, '/alumno/') || str_starts_with($path, '/reemplazos/') ? 404 : 403);
    }

    public function test_recordatorio_renderiza_y_pagar_ahora_conserva_destino(): void
    {
        [$turno] = $this->escenario();
        $turno->update(['fecha' => '2026-08-26']);
        Mail::fake();
        $this->usarOrigen('http://127.0.0.1:8000');
        // Ejecutado exclusivamente en SQLite en memoria: comprobar el productor real.
        (new EnviarRecordatorioPago24hJob())->handle();
        $correo = Mail::sent(RecordatorioPagoTurno::class)->sole();
        $this->assertStringStartsWith(self::ORIGEN.'/mail/access?', $correo->urlPago);
        $html = $correo->render();
        $this->assertStringContainsString('Pagar ahora', $html);
        $this->assertStringContainsString(e($correo->urlPago), $html);
        $this->usarOrigen(self::ORIGEN);
        $this->get($correo->urlPago)->assertRedirect(self::ORIGEN.'/alumno/login')
            ->assertSessionHas('url.intended', $this->destino($turno));
    }

    private function escenario(): array
    {
        $alumno = $this->crearAlumno();
        return [$this->crearTurno($alumno, $this->crearProfesor(), $this->crearMateria(), ['estado' => Turno::ESTADO_PENDIENTE_PAGO]), $alumno];
    }

    private function enlace(Turno $turno, bool $antiguo = false): string
    {
        return $antiguo
            ? URL::signedRoute('mp.pagar.mail', ['turno' => $turno->id, 'alumno_id' => $turno->alumno_id])
            : (new ProfesorRespondioTurno($turno))->urlPanelAlumno;
    }

    private function destino(Turno $turno): string
    {
        return self::ORIGEN.'/alumno/completar-pago/'.$turno->id;
    }

    private function usarOrigen(string $origen): void
    {
        URL::forceRootUrl($origen);
        URL::forceScheme(parse_url($origen, PHP_URL_SCHEME));
    }

    private function simularGoogle($alumno): void
    {
        $provider = Mockery::mock(GoogleProvider::class);
        Socialite::shouldReceive('driver')->with('google')->twice()->andReturn($provider);
        $provider->shouldReceive('stateless')->twice()->andReturnSelf();
        $provider->shouldReceive('with')->with(['prompt' => 'select_account'])->once()->andReturnSelf();
        $provider->shouldReceive('redirect')->once()->andReturn(redirect('https://accounts.google.com/o/oauth2/auth'));
        $provider->shouldReceive('user')->once()->andReturn((new GoogleUser())->map([
            'id' => 'google-pago', 'email' => $alumno->email, 'name' => $alumno->name,
        ]));
    }
}
