<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Turno;
use App\Services\AccesoMailAlumnoService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;

class GoogleAuthController extends Controller
{
    private function provider(): \Laravel\Socialite\Two\AbstractProvider
    {
        /** @var \Laravel\Socialite\Two\AbstractProvider $provider */
        $provider = Socialite::driver('google');

        return $provider;
    }

    public function redirect()
    {
        $panel = request('panel', 'alumno');
        if (! in_array($panel, ['alumno', 'profesor'], true)) {
            $panel = 'alumno';
        }

        $acceso = app(AccesoMailAlumnoService::class);
        $destino = (string) parse_url((string) session('url.intended', ''), PHP_URL_PATH);

        if ($panel === 'alumno' && $acceso->esDestino($destino)) {
            abort_unless(
                rtrim((string) config('services.google.redirect'), '/') === $acceso->origen().'/auth/google/callback',
                503,
                'GOOGLE_REDIRECT_URI debe ser APP_URL seguido de /auth/google/callback.',
            );

            if (request()->getSchemeAndHttpHost() !== $acceso->origen()) {
                // Volver a entrar por el enlace firmado en el dominio correcto.
                // No transportar cookies, credenciales ni sesiones entre dominios.
                $turno = Turno::query()->findOrFail(basename($destino));

                return redirect()->away($acceso->firmar($destino, (int) $turno->alumno_id, now()->addMinutes(5)));
            }

            session()->put('url.intended', $acceso->origen().$destino);
        }

        return $this->provider()
            ->stateless()
            ->with(['prompt' => 'select_account'])
            ->redirect()
            ->withCookie(cookie('google_panel', $panel, 5));
    }

    public function callback()
    {
        $g = $this->provider()
            ->stateless()
            ->user();

        $email    = $g->getEmail();
        $googleId = $g->getId();
        $avatar   = $g->getAvatar();

        $fullName = trim((string) ($g->getName() ?? 'Usuario'));
        if ($fullName === '') $fullName = 'Usuario';

        $parts = preg_split('/\s+/', $fullName);
        $name = $parts[0] ?? 'Usuario';
        $apellido = count($parts) > 1 ? implode(' ', array_slice($parts, 1)) : 'Google';

        $user = User::where('google_id', $googleId)->first();

        if (! $user && $email) {
            $user = User::where('email', $email)->first();
        }

        if (! $user) {
            $user = User::create([
                'name' => $name,
                'apellido' => $apellido,
                'email' => $email,
                'role' => 'alumno',
                'password' => bcrypt(Str::random(32)),
                'email_verified_at' => now(),
            ]);
        }

        $user->google_id = $googleId;
        $user->google_avatar_url = $avatar;

        if (! $user->email_verified_at) {
            $user->email_verified_at = now();
        }

        $user->save();

        Auth::login($user, remember: true);

        return redirect()->intended('/');
    }
}
