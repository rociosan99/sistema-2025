@extends('layouts.app')

@section('title', 'Crear Materia')

@section('content')
<div class="container mt-5">
    <div class="card shadow-lg border-0 rounded-4">
        <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
            <h4 class="mb-0">
                <i class="bi bi-book"></i> Nueva Materia
            </h4>
            <a href="{{ route('admin.materias.index') }}" class="btn btn-light btn-sm">
                <i class="bi bi-arrow-left"></i> Volver
            </a>
        </div>

        <div class="card-body">
            <!-- Mensaje de error o éxito -->
            @if ($errors->any())
                <div class="alert alert-danger">
                    <strong>Ups...</strong> Hubo algunos problemas con los datos.<br><br>
                    <ul class="mb-0">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <!-- Formulario -->
            <form action="{{ route('admin.materias.store') }}" method="POST">
                @csrf

                <div class="mb-4">
                    <label for="nombre" class="form-label fw-semibold">
                        Nombre de la Materia
                    </label>
                    <input 
                        type="text" 
                        class="form-control form-control-lg" 
                        id="nombre" 
                        name="nombre" 
                        placeholder="Ej: Matemática, Inglés, Programación" 
                        value="{{ old('nombre') }}" 
                        required
                    >
                </div>

                <div class="d-flex justify-content-end gap-2">
                    <a href="{{ route('admin.materias.index') }}" class="btn btn-secondary">
                        <i class="bi bi-x-circle"></i> Cancelar
                    </a>
                    <button type="submit" class="btn btn-success">
                        <i class="bi bi-check-circle"></i> Guardar Materia
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
