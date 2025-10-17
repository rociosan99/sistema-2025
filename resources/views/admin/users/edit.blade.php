@extends('layouts.app')

@section('title', 'Editar Usuario')

@section('content')
<div class="d-flex justify-content-end mb-3">
    <!-- Botón Volver -->
    <a href="{{ route('admin.users.index') }}" class="btn btn-secondary">Volver</a>
</div>

<div class="d-flex justify-content-center align-items-center" style="min-height: 80vh;">
    <div class="card shadow-lg p-4" style="width: 500px;">
        <h3 class="text-center mb-4">Editar Usuario</h3>

        <!-- Formulario -->
        <form method="POST" action="{{ route('admin.users.update', $user->id) }}">
            @csrf
            @method('PUT')

            <!-- Nombre y Apellido -->
            <div class="mb-3">
                <label for="name" class="form-label">Nombre y Apellido</label>
                <input type="text" class="form-control" id="name" name="name" 
                       value="{{ old('name', $user->name) }}" required>
                @error('name')
                    <small class="text-danger">{{ $message }}</small>
                @enderror
            </div>

            <!-- Email -->
            <div class="mb-3">
                <label for="email" class="form-label">Correo Electrónico</label>
                <input type="email" class="form-control" id="email" name="email" 
                       value="{{ old('email', $user->email) }}" required>
                @error('email')
                    <small class="text-danger">{{ $message }}</small>
                @enderror
            </div>

            <!-- Rol -->
            <div class="mb-3">
                <label for="role" class="form-label">Rol</label>
                <select class="form-select" id="role" name="role" required>
                    <option value="">Seleccione un rol</option>
                    <option value="administrador" {{ old('role', $user->role) == 'administrador' ? 'selected' : '' }}>Administrador</option>
                    <option value="profesor" {{ old('role', $user->role) == 'profesor' ? 'selected' : '' }}>Profesor</option>
                    <option value="alumno" {{ old('role', $user->role) == 'alumno' ? 'selected' : '' }}>Alumno</option>
                </select>
                @error('role')
                    <small class="text-danger">{{ $message }}</small>
                @enderror
            </div>

            <!-- Password (opcional) -->
            <div class="mb-3">
                <label for="password" class="form-label">Contraseña (dejar en blanco si no quiere cambiarla)</label>
                <input type="password" class="form-control" id="password" name="password">
                @error('password')
                    <small class="text-danger">{{ $message }}</small>
                @enderror
            </div>

            <!-- Confirmación Password -->
            <div class="mb-3">
                <label for="password_confirmation" class="form-label">Confirmar Contraseña</label>
                <input type="password" class="form-control" id="password_confirmation" name="password_confirmation">
            </div>

            <!-- Botón Guardar -->
            <div class="d-grid">
                <button type="submit" class="btn btn-primary">Actualizar Usuario</button>
            </div>
        </form>
    </div>
</div>
@endsection
