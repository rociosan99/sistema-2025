@extends('layouts.app')

@section('title', 'Gestión de Materias')

@section('content')
<div class="container mt-5">
    <div class="card shadow-lg border-0 rounded-4">
        <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
            <h4 class="mb-0">Gestión de Materias</h4>
            <a href="{{ route('admin.materias.create') }} " class="btn btn-light btn-sm">
                <i class="bi bi-plus-circle"></i> Nueva Materia
            </a>
        </div>

        <div class="card-body">
            @if(session('success'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            <div class="table-responsive">
                <table id="materiasTable" class="table table-striped table-hover align-middle">
                    <thead class="table-primary text-center">
                        <tr>
                            <th>ID</th>
                            <th>Nombre</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($materias as $materia)
                            <tr>
                                <td class="text-center">{{ $materia->id }}</td>
                                <td>{{ $materia->nombre }}</td>
                                <td class="text-center">
                                    <a href=" {{ route('admin.materias.edit', $materia->id) }} " class="btn btn-sm btn-warning">
                                        <i class="bi bi-pencil-square"></i> Editar
                                    </a>
                                    <form action="{{ route('admin.materias.destroy', $materia->id) }}" method="POST" class="d-inline">
                                        @csrf
                                        @method('DELETE')
                                        <button class="btn btn-sm btn-danger" onclick="return confirm('¿Seguro que deseas eliminar esta materia?')">
                                            <i class="bi bi-trash"></i> Eliminar
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="text-center text-muted">No hay materias registradas.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

{{-- ✅ DataTables CDN --}}
@push('scripts')
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.5/css/dataTables.bootstrap5.min.css">
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.5/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.5/js/dataTables.bootstrap5.min.js"></script>

    <script>
        $(document).ready(function() {
            $('#materiasTable').DataTable({
                language: {
                    url: 'https://cdn.datatables.net/plug-ins/1.13.5/i18n/es-ES.json'
                },
                pageLength: 5,
                lengthMenu: [5, 10, 20],
                columnDefs: [
                    { orderable: false, targets: 2 } // desactiva orden en columna de acciones
                ]
            });
        });
    </script>
@endpush
@endsection
