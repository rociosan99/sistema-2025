<?php

namespace Tests\Feature\Ofertas;

use App\Mail\ProfesorRespondioTurno;
use App\Models\Turno;
use Filament\Auth\Pages\Login;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\URL;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\GoogleProvider;
use Laravel\Socialite\Two\User as GoogleUser;
use Livewire\Livewire;
use Mockery;
use Tests\Support\CreatesTurnoScenarios;
use Tests\TestCase;

class AccesoPropuestaAlumnoTest extends TestCase
{
    use CreatesTurnoScenarios;
    use RefreshDatabase {
        refreshDatabase as private refreshSqliteDatabase;
    }

    private const ORIGEN = 'https://propuestas.example.test';

    public function refreshDatabase(): void
    {
        // Comprobar antes de migrar: esta suite nunca puede usar sistema_2025.
        $this->assertSame('sqlite', config('database.default'));
        $this->assertSame(':memory:', config('database.connections.sqlite.database'));
        $this->refreshSqliteDatabase();
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo('2026-08-25 10:00:00');
        config([
            'app.url' => self::ORIGEN,
            'services.google.redirect' => self::ORIGEN.'/auth/google/callback',
        ]);
        URL::forceRootUrl(self::ORIGEN);
        URL::forceScheme('https');
        Filament::setCurrentPanel(Filament::getPanel('alumno'));
    }

    public function test_destinatario_autenticado_continua_sin_cerrar_sesion(): void
    {
        [$turno, $alumno] = $this->escenario();
        $this->actingAs($alumno)->withSession(['conservar' => 'sesion']);
        $this->get($this->enlace($turno))
            ->assertRedirect($this->destino($turno))
            ->assertSessionHas('conservar', 'sesion');
        $this->assertAuthenticatedAs($alumno);
        $this->get($this->destino($turno))->assertOk()->assertSee('Esperando tu respuesta');
    }

    public function test_sin_sesion_login_filament_por_contrasena_conserva_propuesta(): void
    {
        [$turno, $alumno] = $this->escenario();
        $this->get($this->enlace($turno))
            ->assertRedirect(self::ORIGEN.'/alumno/login')
            ->assertSessionHas('url.intended', $this->destino($turno));
        $this->get(self::ORIGEN.'/alumno/login')->assertOk();

        Livewire::test(Login::class)
            ->fillForm(['email' => $alumno->email, 'password' => 'password'])
            ->call('authenticate')
            ->assertHasNoFormErrors()
            ->assertRedirect($this->destino($turno));

        $this->assertAuthenticatedAs($alumno);
        $this->get($this->destino($turno))->assertOk();
    }

    public function test_usuario_incorrecto_sale_y_login_correcto_recupera_destino(): void
    {
        [$turno, $alumno] = $this->escenario();
        $this->actingAs($this->crearAlumno());
        $this->get($this->enlace($turno))
            ->assertRedirect(self::ORIGEN.'/alumno/login')
            ->assertSessionHas('url.intended', $this->destino($turno));
        $this->assertGuest();

        Livewire::test(Login::class)
            ->fillForm(['email' => $alumno->email, 'password' => 'password'])
            ->call('authenticate')->assertRedirect($this->destino($turno));
        $this->get($this->destino($turno))->assertOk();
    }

    public function test_login_de_otro_alumno_no_da_acceso_a_la_propuesta(): void
    {
        [$turno] = $this->escenario();
        $otro = $this->crearAlumno();
        $this->get($this->enlace($turno));
        Livewire::test(Login::class)
            ->fillForm(['email' => $otro->email, 'password' => 'password'])
            ->call('authenticate')->assertRedirect($this->destino($turno));
        $this->get($this->destino($turno))->assertNotFound();
    }

    public function test_correo_generado_desde_local_usa_origen_canonico_y_firma_valida(): void
    {
        [$turno] = $this->escenario();
        URL::forceRootUrl('http://127.0.0.1:8000');
        URL::forceScheme('http');
        $url = $this->enlace($turno);
        $this->assertStringStartsWith(self::ORIGEN.'/mail/access?', $url);
        $this->assertTrue(URL::hasValidSignature(\Illuminate\Http\Request::create($url)));
        // La generación del correo no cambia el origen de otras rutas.
        $this->assertStringStartsWith('http://127.0.0.1:8000/', route('auth.google.redirect'));
    }

    public function test_enlace_local_anterior_pasa_a_canonico_antes_del_login(): void
    {
        [$turno] = $this->escenario();
        URL::forceRootUrl('http://127.0.0.1:8000');
        URL::forceScheme('http');
        $local = URL::temporarySignedRoute('mail.access', now()->addHour(), [
            'panel' => 'alumno', 'alumno' => $turno->alumno_id,
            'target' => base64_encode('/alumno/responder-oferta-profesor/'.$turno->id),
        ]);
        $response = $this->get($local);
        $canonico = $response->headers->get('Location');
        $this->assertStringStartsWith(self::ORIGEN.'/mail/access?', $canonico);
        parse_str(parse_url($local, PHP_URL_QUERY), $original);
        parse_str(parse_url($canonico, PHP_URL_QUERY), $nuevo);
        $this->assertSame($original['expires'], $nuevo['expires']);

        $this->nuevaSesionCanonica();
        $this->get($canonico)->assertRedirect(self::ORIGEN.'/alumno/login')
            ->assertSessionHas('url.intended', $this->destino($turno));
    }

    public function test_google_desde_local_reingresa_en_canonico_antes_de_oauth(): void
    {
        [$turno] = $this->escenario();
        Socialite::shouldReceive('driver')->never();
        $this->withSession(['url.intended' => 'http://127.0.0.1:8000/alumno/responder-oferta-profesor/'.$turno->id]);
        $response = $this->get('http://127.0.0.1:8000/auth/google?panel=alumno');
        $canonico = $response->headers->get('Location');
        $this->assertStringStartsWith(self::ORIGEN.'/mail/access?', $canonico);

        // Otro dominio empieza sin la sesión local: el enlace reconstruye intended.
        $this->nuevaSesionCanonica();
        $this->get($canonico)->assertSessionHas('url.intended', $this->destino($turno));
    }

    public function test_google_en_canonico_conserva_destino_hasta_callback(): void
    {
        [$turno, $alumno] = $this->escenario();
        $this->get($this->enlace($turno));
        $provider = Mockery::mock(GoogleProvider::class);
        Socialite::shouldReceive('driver')->with('google')->twice()->andReturn($provider);
        $provider->shouldReceive('stateless')->twice()->andReturnSelf();
        $provider->shouldReceive('with')->with(['prompt' => 'select_account'])->once()->andReturnSelf();
        $provider->shouldReceive('redirect')->once()->andReturn(redirect('https://accounts.google.com/o/oauth2/auth'));
        $provider->shouldReceive('user')->once()->andReturn((new GoogleUser())->map([
            'id' => 'google-alumno', 'email' => $alumno->email, 'name' => $alumno->name,
        ]));

        $this->get(self::ORIGEN.'/auth/google?panel=alumno')
            ->assertRedirect('https://accounts.google.com/o/oauth2/auth')
            ->assertSessionHas('url.intended', $this->destino($turno));
        $this->get(self::ORIGEN.'/auth/google/callback?code=simulado')
            ->assertRedirect($this->destino($turno));
        $this->assertAuthenticatedAs($alumno);
        $this->get($this->destino($turno))->assertOk();
    }

    public function test_callback_de_otro_origen_no_inicia_google(): void
    {
        [$turno] = $this->escenario();
        config(['services.google.redirect' => 'https://otro.example.test/auth/google/callback']);
        Socialite::shouldReceive('driver')->never();
        $this->withSession(['url.intended' => $this->destino($turno)])
            ->get(self::ORIGEN.'/auth/google?panel=alumno')->assertStatus(503);
    }

    public function test_correo_original_con_turno_ahora_pendiente_pago_va_a_completar_pago(): void
    {
        [$turno, $alumno] = $this->escenario();
        $enlace = $this->enlace($turno);
        $turno->update(['estado' => Turno::ESTADO_PENDIENTE_PAGO]);
        $this->actingAs($alumno)->get($enlace)->assertRedirect($this->destino($turno));
        $this->get($this->destino($turno))->assertRedirect(self::ORIGEN.'/alumno/completar-pago/'.$turno->id);
    }

    public function test_abrir_propuesta_no_modifica_turno_ni_crea_pagos_o_auditoria(): void
    {
        [$turno, $alumno] = $this->escenario();
        $antes = $turno->fresh()->getRawOriginal();
        $auditoria = DB::table('activity_log')->count();
        $this->actingAs($alumno)->get($this->enlace($turno));
        $this->get($this->destino($turno))->assertOk();
        $this->assertSame($antes, $turno->fresh()->getRawOriginal());
        $this->assertDatabaseCount('turnos', 1);
        $this->assertDatabaseCount('pagos', 0);
        $this->assertDatabaseCount('activity_log', $auditoria);
    }

    public function test_rechazada_sigue_mostrando_rechazo_y_firma_alterada_se_deniega(): void
    {
        [$turno, $alumno] = $this->escenario();
        $enlace = $this->enlace($turno);
        $turno->update(['estado' => Turno::ESTADO_RECHAZADO]);
        $this->actingAs($alumno)->get($enlace)->assertRedirect($this->destino($turno));
        $this->get($this->destino($turno))->assertOk()->assertSee('Propuesta rechazada');
        $this->get($enlace.'&alterado=1')->assertForbidden();
    }

    public function test_enlace_vencido_no_redirige_ni_cierra_sesion(): void
    {
        [$turno, $alumno] = $this->escenario();
        $enlace = $this->enlace($turno);
        $this->travelTo('2026-08-28 10:00:00');
        $this->actingAs($alumno)->get($enlace)->assertForbidden();
        $this->assertAuthenticatedAs($alumno);
    }

    public function test_valida_alumno_indicado_aunque_el_usuario_sea_dueno_del_turno(): void
    {
        [$turno, $alumno] = $this->escenario();
        $enlace = URL::temporarySignedRoute('mail.access', now()->addHour(), [
            'panel' => 'alumno', 'alumno' => $this->crearAlumno()->id,
            'target' => base64_encode('/alumno/responder-oferta-profesor/'.$turno->id),
        ]);
        $this->actingAs($alumno)->get($enlace)->assertRedirect(self::ORIGEN.'/alumno/login');
        $this->assertGuest();
    }

    private function escenario(): array
    {
        $alumno = $this->crearAlumno();
        return [$this->crearTurno($alumno, $this->crearProfesor(), $this->crearMateria(), [
            'estado' => Turno::ESTADO_ACEPTADO,
        ]), $alumno];
    }

    private function enlace(Turno $turno): string
    {
        return (new ProfesorRespondioTurno($turno))->urlPanelAlumno;
    }

    private function destino(Turno $turno): string
    {
        return self::ORIGEN.'/alumno/responder-oferta-profesor/'.$turno->id;
    }

    private function nuevaSesionCanonica(): void
    {
        session()->flush();
        Auth::forgetGuards();
        URL::forceRootUrl(self::ORIGEN);
        URL::forceScheme('https');
    }
}
