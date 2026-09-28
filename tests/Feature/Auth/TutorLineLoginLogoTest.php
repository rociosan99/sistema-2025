<?php

namespace Tests\Feature\Auth;

use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class TutorLineLoginLogoTest extends TestCase
{
    public function test_logo_publico_existe(): void
    {
        $this->assertFileExists(public_path('images/tutorline-logo.png'));
    }

    public function test_login_principal_muestra_logo_tutorline(): void
    {
        $this->get('/login')
            ->assertOk()
            ->assertSee('images/tutorline-logo.png')
            ->assertSee('alt="TutorLine"', false)
            ->assertSee('Ingresar como administrador')
            ->assertSee('Ingresar como profesor')
            ->assertSee('Ingresar como alumno');
    }

    #[DataProvider('panelesFilament')]
    public function test_logins_filament_muestran_logo_y_conservan_sus_controles(string $panel, bool $tieneGoogle): void
    {
        $response = $this->get("/{$panel}/login")
            ->assertOk()
            ->assertSee('images/tutorline-logo.png')
            ->assertSee('alt="TutorLine"', false)
            ->assertSee('wire:model="data.email"', false)
            ->assertSee('wire:model="data.password"', false)
            ->assertSee('.fi-simple-header .fi-logo', false);

        if ($tieneGoogle) {
            $response->assertSee('Continuar con Google');
        }
    }

    public static function panelesFilament(): array
    {
        return [
            'Administrador' => ['admin', false],
            'Profesor' => ['profesor', true],
            'Alumno' => ['alumno', true],
        ];
    }
}
