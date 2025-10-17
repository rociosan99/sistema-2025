@extends('layouts.app')

@section('title', 'Gestión de Usuarios')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h2>Gestión de Usuarios</h2>
    <a href="{{ route('admin.users.create') }}" class="btn btn-primary">Agregar Usuario</a>
</div>

<form method="GET" class="mb-3">
    <div class="row g-2">
        <div class="col-md-4">
            <input type="text" name="search" class="form-control" placeholder="Buscar por nombre o email" value="{{ request('search') }}">
        </div>
        <div class="col-md-3">
            <select name="role" class="form-select">
                <option value="">Todos los roles</option>
                <option value="administrador" {{ request('role') == 'administrador' ? 'selected' : '' }}>Administrador</option>
                <option value="profesor" {{ request('role') == 'profesor' ? 'selected' : '' }}>Profesor</option>
                <option value="alumno" {{ request('role') == 'alumno' ? 'selected' : '' }}>Alumno</option>
            </select>
        </div>
        <div class="col-md-2">
            <button type="submit" class="btn btn-secondary">Filtrar</button>
        </div>
    </div>
</form>

<table class="table table-bordered table-hover">
    <thead class="table-dark">
        <tr>
            <th>Nombre</th>
            <th>Email</th>
            <th>Rol</th>
            <th>Acciones</th>
        </tr>
    </thead>
    <tbody>
        @foreach($users as $user)
        <tr>
            <td>{{ $user->name }}</td>
            <td>{{ $user->email }}</td>
            <td>{{ ucfirst($user->role) }}</td>
            <td>
                <a href="{{ route('admin.users.edit', $user) }}" class="btn btn-sm btn-warning">Editar</a>
                <form action="{{ route('admin.users.destroy', $user) }}" method="POST" style="display:inline-block;">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('¿Estás seguro de eliminar este usuario?')">
                        Eliminar
                    </button>
                </form>

            </td>
        </tr>
        @endforeach
    </tbody>
</table>

{{ $users->withQueryString()->links() }}
@endsection
