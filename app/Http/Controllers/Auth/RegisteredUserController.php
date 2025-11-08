<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Illuminate\View\View;

class RegisteredUserController extends Controller
{
    /**
     * Mostrar formulario de registro.
     */
    public function create(): View
    {
        return view('auth.register');
    }

    /**
     * Guardar nuevo usuario registrado.
     */
    public function store(Request $request): RedirectResponse
    {
        // ✅ Validar datos, incluyendo el apellido y el rol
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'apellido' => ['required', 'string', 'max:255'], // nuevo campo
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'role' => ['required', 'in:admin,profesor,alumno'],
        ]);

        // ✅ Crear usuario
        $user = User::create([
            'name' => $request->name,
            'apellido' => $request->apellido, // nuevo campo
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => $request->role,
        ]);

        // ✅ Disparar evento de registro
        event(new Registered($user));

        // ✅ Autenticar al usuario
        Auth::login($user);

        // ✅ Redirigir según el rol
        if ($user->role === 'admin') {
            return redirect('/admin'); // panel admin de Filament
        } elseif ($user->role === 'profesor') {
            return redirect('/profesor'); // panel profesor de Filament
        } else {
            return redirect('/alumno/dashboard'); // vista manual del alumno
        }
    }
}
