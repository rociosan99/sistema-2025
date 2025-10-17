<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Materia;
use Illuminate\Http\Request;

class MateriaController extends Controller
{
    /**
     * Mostrar listado de materias.
     */
    public function index()
    {
        $materias = Materia::all();
        // Retorna la vista con las materias (sin usar compact)
        return view('admin.materias.index', ['materias' => $materias]);
    }

    /**
     * Mostrar formulario para crear una nueva materia.
     */
    public function create()
    {
        return view('admin.materias.create');
    }

    /**
     * Guardar una nueva materia en la base de datos.
     */
    public function store(Request $request)
    {
        $request->validate([
            'nombre' => 'required|string|max:255',
        ]);

        Materia::create([
            'nombre' => $request->nombre,
        ]);

        return redirect()->route('admin.materias.index')->with('success', 'Materia creada correctamente.');
    }

    /**
     * Mostrar formulario de edición de una materia.
     */
    public function edit(Materia $materia)
    {
        return view('admin.materias.edit', ['materia' => $materia]);
    }

    /**
     * Actualizar una materia existente.
     */
    public function update(Request $request, Materia $materia)
    {
        $request->validate([
            'nombre' => 'required|string|max:255',
        ]);

        $materia->update([
            'nombre' => $request->nombre,
        ]);

        return redirect()->route('admin.materias.index')->with('success', 'Materia actualizada correctamente.');
    }

    /**
     * Eliminar una materia.
     */
    public function destroy(Materia $materia)
    {
        $materia->delete();

        return redirect()->route('admin.materias.index')->with('success', 'Materia eliminada correctamente.');
    }
}
