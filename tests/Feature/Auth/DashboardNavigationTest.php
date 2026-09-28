<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Filament\Auth\Pages\Login;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class DashboardNavigationTest extends TestCase
{
    use RefreshDatabase;

    public static function paneles(): array
    {
        return [
            'alumno' => ['alumno', \App\Filament\Alumno\Pages\Dashboard::class, 'dashboard', [
                'Mi perfil', 'Mis créditos', 'Solicitar turno', 'Disponibilidad', 'Turnos', 'Mis Clases',
            ], 'turnos'],
            'profesor' => ['profesor', \App\Filament\Profesor\Pages\Dashboard::class, 'dashboard', [
                'Mi perfil', 'Oferta de alumnos', 'Turnos', 'Disponibilidad', 'Mis Clases',
            ], 'turnos'],
            'admin' => ['admin', \App\Filament\Pages\Dashboard::class, 'usuarios', [
                'Usuarios', 'Auditoría', 'Estadísticas', 'Reportes', 'Instituciones', 'Carreras',
                'Planes De Estudio', 'Materias', 'Programas', 'Temas', 'Pagos', 'Solicitudes de ubicacion',
            ], 'usuarios'],
        ];
    }

    #[DataProvider('paneles')]
    public function test_login_inicio_y_dashboard_se_conservan_sin_la_entrada_en_sidebar(
        string $panel, string $dashboard, string $inicio, array $otrosItems, string $otraPagina,
    ): void
    {
        $usuario = User::factory()->create(['role' => $panel, 'activo' => true]);
        Filament::setCurrentPanel(Filament::getPanel($panel));
        $this->get('/'.$panel.'/login')->assertOk();
        Livewire::test(Login::class)
            ->set('data.email', $usuario->email)
            ->set('data.password', 'password')
            ->call('authenticate')
            ->assertHasNoErrors()
            ->assertRedirect(url('/'.$panel));
        $this->assertAuthenticatedAs($usuario);
        $this->get('/'.$panel)->assertRedirect(url('/'.$panel.'/'.$inicio));
        $this->assertContains($dashboard, Filament::getPanel($panel)->getPages());
        $this->assertSame(url('/'.$panel.'/dashboard'), $dashboard::getUrl(panel: $panel));

        foreach (['dashboard', $otraPagina] as $pagina) {
            $response = $this->get('/'.$panel.'/'.$pagina)->assertOk();
            $this->assertFalse($dashboard::shouldRegisterNavigation());
            $items = collect(Filament::getNavigation())->flatMap(fn ($grupo) => $grupo->getItems())
                ->map(fn ($item) => $item->getLabel())->values()->all();
            // Lista y orden observados antes del cambio, excluyendo únicamente Dashboard.
            $this->assertSame($otrosItems, $items);
            $this->assertSame(1, preg_match('/<aside\b.*?<\/aside>/s', $response->getContent(), $sidebar));
            $this->assertStringNotContainsString('href="'.$dashboard::getUrl(panel: $panel).'"', $sidebar[0]);
        }
    }

    #[DataProvider('paneles')]
    public function test_dashboard_conserva_proteccion_de_acceso(string $panel): void
    {
        $this->get('/'.$panel.'/dashboard')->assertRedirect(url('/'.$panel.'/login'));
    }
}
