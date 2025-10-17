@extends('layouts.app')

@section('title', 'Crear Usuario')

@section('content')
<div class="d-flex justify-content-end mb-3">
    <!-- Botón Volver -->
    <a href="{{ route('admin.users.index') }}" class="btn btn-secondary">Volver</a>
</div>

<div class="d-flex justify-content-center align-items-center" style="min-height: 80vh;">
    <div class="card shadow-lg p-4" style="width: 500px;">
        <h3 class="text-center mb-4">Crear Nuevo Usuario</h3>

        <!-- Formulario -->
        <form method="POST" action="{{ route('admin.users.store') }}">
            @csrf

            <!-- Nombre y Apellido -->
            <div class="mb-3">
                <label for="name" class="form-label">Nombre y Apellido</label>
                <input type="text" class="form-control" id="name" name="name" placeholder="Ej: Juan Pérez" value="{{ old('name') }}" required>
                @error('name')
                    <small class="text-danger">{{ $message }}</small>
                @enderror
            </div>

            <!-- Email -->
            <div class="mb-3">
                <label for="email" class="form-label">Correo Electrónico</label>
                <input type="email" class="form-control" id="email" name="email" placeholder="Ej: juanperez@mail.com" value="{{ old('email') }}" required>
                @error('email')
                    <small class="text-danger">{{ $message }}</small>
                @enderror
            </div>

            <!-- Rol -->
            <div class="mb-3">
                <label for="role" class="form-label">Rol</label>
                <select class="form-select" id="role" name="role" required>
                    <option value="">Seleccione un rol</option>
                    <option value="administrador" {{ old('role') == 'administrador' ? 'selected' : '' }}>Administrador</option>
                    <option value="profesor" {{ old('role') == 'profesor' ? 'selected' : '' }}>Profesor</option>
                    <option value="alumno" {{ old('role') == 'alumno' ? 'selected' : '' }}>Alumno</option>
                </select>
                @error('role')
                    <small class="text-danger">{{ $message }}</small>
                @enderror
            </div>

            <!-- Password -->
            <div class="mb-3">
                <label for="password" class="form-label">Contraseña</label>
                <input type="password" class="form-control" id="password" name="password" required>
                @error('password')
                    <small class="text-danger">{{ $message }}</small>
                @enderror
            </div>

            <!-- Confirmación Password -->
            <div class="mb-3">
                <label for="password_confirmation" class="form-label">Confirmar Contraseña</label>
                <input type="password" class="form-control" id="password_confirmation" name="password_confirmation" required>
            </div>

            <!-- Botón Guardar -->
            <div class="d-grid">
                <button type="submit" class="btn btn-primary">Guardar Usuario</button>
            </div>
        </form>
    </div>
</div>
@endsection
