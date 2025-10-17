@extends('layouts.app')

@section('title', 'Dashboard Administrador')

@section('content')
<div class="row">
    <!-- Sidebar -->
    <nav class="col-md-3 col-lg-3 d-md-block bg-light sidebar collapse">
        <div class="position-sticky pt-3">
            <h5 class="px-3">Menú</h5>
            <ul class="nav flex-column">
                <li class="nav-item">
                    <a class="nav-link active" href="{{ route('admin.dashboard') }}">
                        Inicio
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="{{ route('admin.users.index') }}">
                        Gestión de Usuarios
                    </a>
                    <ul class="nav flex-column ms-3">
                        <li class="nav-item">
                            <a class="nav-link" href="{{ route('admin.users.index') }}">Ver Usuarios</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href=" ">Agregar Usuario</a>
                        </li>
                        <!-- Editar y Eliminar se gestionan desde la tabla de usuarios -->
                    </ul>
                </li>
                <li class="nav-item">
                    <a class="nav-link active" href="{{ route('admin.materias.index') }}">
                        Gestión de Materias
                    </a>

                </li>

                <!-- Podés agregar más secciones aquí -->
            </ul>
        </div>
    </nav>

    <!-- Main content -->
    <main class="col-md-9 col-lg-9 ms-sm-auto px-md-4">
        <div class="card">
            <div class="card-body">
                <h1>Bienvenido, Administrador</h1>
                <p>Desde aquí podrás gestionar usuarios y todo el sistema.</p>
            </div>
        </div>
    </main>
</div>
@endsection
