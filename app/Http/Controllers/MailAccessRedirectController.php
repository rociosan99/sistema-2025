<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class MailAccessRedirectController extends Controller
{
    public function __invoke(Request $request)
    {
        $panel = (string) $request->query('panel');
        $encodedTarget = (string) $request->query('target');

        abort_unless(in_array($panel, ['alumno', 'profesor', 'admin'], true), 404);
        abort_unless($encodedTarget !== '', 404);

        $targetPath = base64_decode($encodedTarget, true);

        abort_unless(is_string($targetPath) && $targetPath !== '', 404);

        // Solo permitimos rutas internas relativas
        abort_unless(str_starts_with($targetPath, '/'), 403);
        abort_unless(! str_starts_with($targetPath, '//'), 403);

        foreach (['web', 'alumno', 'profesor', 'admin'] as $guard) {
            if (array_key_exists($guard, config('auth.guards', []))) {
                Auth::guard($guard)->logout();
            }
        }

        if ($request->hasSession()) {
            $request->session()->invalidate();
            $request->session()->regenerateToken();
            $request->session()->put('url.intended', url($targetPath));
        }

        return redirect(match ($panel) {
            'alumno' => '/alumno/login',
            'profesor' => '/profesor/login',
            'admin' => '/admin/login',
        });
    }
}