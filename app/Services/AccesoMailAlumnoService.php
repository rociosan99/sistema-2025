<?php

namespace App\Services;

use App\Filament\Alumno\Pages\ResponderOfertaProfesor;
use App\Models\Turno;
use DateTimeInterface;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\URL;

class AccesoMailAlumnoService
{
    private const DESTINOS = [
        'propuesta' => '/alumno/responder-oferta-profesor/',
        'pago' => '/alumno/completar-pago/',
    ];

    public function origen(): string
    {
        $origen = rtrim((string) config('app.url'), '/');
        $partes = parse_url($origen);

        if (! is_array($partes)
            || ! in_array($partes['scheme'] ?? null, ['http', 'https'], true)
            || empty($partes['host'])
            || isset($partes['user']) || isset($partes['pass'])
            || isset($partes['query']) || isset($partes['fragment'])
            || ! empty($partes['path'])) {
            throw new \RuntimeException('APP_URL debe contener el origen canónico de la aplicación, sin path.');
        }

        return $origen;
    }

    public function esDestino(string $path): bool
    {
        foreach (self::DESTINOS as $prefijo) {
            if (preg_match('~\A'.preg_quote($prefijo, '~').'[1-9][0-9]*\z~', $path) === 1) {
                return true;
            }
        }

        return false;
    }

    public function enlacePropuesta(Turno $turno): string
    {
        $path = parse_url(ResponderOfertaProfesor::getUrl(
            ['record' => $turno->id], panel: 'alumno',
        ), PHP_URL_PATH);

        return $this->firmar(
            (string) $path,
            (int) $turno->alumno_id,
            now()->addDays(7)->min($turno->inicioDateTime()),
        );
    }

    public function destinoPago(Turno $turno): string
    {
        return self::DESTINOS['pago'].$turno->getKey();
    }

    public function enlacePago(Turno $turno): string
    {
        // Mantener la vigencia del enlace de pago anterior: la Page valida el turno.
        return $this->firmar($this->destinoPago($turno), (int) $turno->alumno_id);
    }

    public function continuar(Request $request, string $path, int $alumnoId, ?DateTimeInterface $vence = null): RedirectResponse
    {
        abort_unless($this->esDestino($path) && $alumnoId > 0, 404);

        $usuario = Auth::user();
        if ($usuario?->role === 'alumno' && (int) $usuario->id === $alumnoId) {
            return redirect($path);
        }

        // El llamador debe haber validado la firma original antes de delegar.
        if ($request->getSchemeAndHttpHost() !== $this->origen()) {
            return redirect()->away($this->firmar($path, $alumnoId, $vence));
        }

        foreach (['web', 'alumno', 'profesor', 'admin'] as $guard) {
            if (array_key_exists($guard, config('auth.guards', []))) {
                Auth::guard($guard)->logout();
            }
        }

        $request->session()->invalidate();
        $request->session()->regenerateToken();
        $request->session()->put('url.intended', $this->origen().$path);

        return redirect($this->origen().'/alumno/login');
    }

    public function firmar(string $path, int $alumnoId, ?DateTimeInterface $vence = null): string
    {
        abort_unless($this->esDestino($path) && $alumnoId > 0, 404);

        // No modificar el generador global: pagos y reemplazos conservan sus URLs.
        $urls = clone URL::getFacadeRoot();
        // Descartar el RouteUrlGenerator interno, que conserva una referencia al original.
        $urls->setRequest($urls->getRequest());
        $urls->forceRootUrl($this->origen());
        $urls->forceScheme(parse_url($this->origen(), PHP_URL_SCHEME));

        return $urls->signedRoute('mail.access', [
            'panel' => 'alumno',
            'alumno' => $alumnoId,
            'target' => base64_encode($path),
        ], $vence);
    }
}
