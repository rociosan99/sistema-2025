@extends('layouts.app')

@section('title', 'Editar Materia')

@section('content')
<div class="d-flex justify-content-end mb-3">
    <!-- Botón Volver -->
    <a href="{{ route('admin.materias.index') }}" class="btn btn-secondary">Volver</a>
</div>

<div class="d-flex justify-content-center align-items-center" style="min-height: 80vh;">
    <div class="card shadow-lg p-4" style="width: 500px;">
        <h3 class="text-center mb-4">Editar Materia</h3>

        <!-- Formulario -->
        <form method="POST" action="{{ route('admin.materias.update', $materia) }}">
            @csrf
            @method('PUT') <!-- importante para indicar que es actualización -->

            <!-- Nombre -->
            <div class="mb-3">
                <label for="nombre" class="form-label">Nombre de la Materia</label>
                <input type="text" class="form-control" id="nombre" name="nombre" placeholder="Ej: Matemática" value="{{ old('nombre', $materia->nombre) }}" required>
                @error('nombre')
                    <small class="text-danger">{{ $message }}</small>
                @enderror
            </div>

            <!-- Botón Guardar -->
            <div class="d-grid">
                <button type="submit" class="btn btn-primary">Actualizar Materia</button>
            </div>
        </form>
    </div>
</div>
@endsection
