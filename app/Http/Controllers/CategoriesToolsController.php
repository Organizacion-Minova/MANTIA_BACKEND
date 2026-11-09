<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\CategoryTools;
use Illuminate\Validation\Rule;

class CategoriesToolsController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $categories = CategoryTools::all();
        return response()->json($categories);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validado = $request->validate([
            'name' => 'required|string|max:50|unique:category,name',
            'description' => 'required|string',
            'status' => 'required|string|in:Activo,Inactivo,activo,inactivo,active,inactive',
            'category_group_id' => 'required|exists:category_group,id'
        ],[
            'name.required' => 'El nombre es obligatorio.',
            'name.string' => 'El nombre debe ser texto.',
            'name.max' => 'El nombre no puede superar los 50 caracteres.',
            'name.unique' => 'Ya existe una categoría con este nombre.',
            'description.required' => 'La descripción es obligatoria.',
            'description.string' => 'La descripción debe ser texto.',
            'status.required' => 'El estado es obligatorio.',
            'status.string' => 'El estado debe ser texto.',
            'status.in' => 'El estado debe ser uno de los valores permitidos.',
            'category_group_id.required' => 'El grupo de categoría es obligatorio.',
            'category_group_id.exists' => 'El grupo de categoría seleccionado no existe.'
        ]);

        $validado['status'] = in_array(strtolower($validado['status']), ['activo', 'active'])
            ? 'Activo'
            : 'Inactivo';

        $categoria = CategoryTools::create($validado);

        return response()->json($categoria, 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
       $categoria = CategoryTools::findOrFail($id);
       return response()->json($categoria);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $categoria = CategoryTools::findOrFail($id);

        $validado = $request->validate([
            'name' => 'required','string','max:50',Rule::unique('category', 'name')->ignore($id),
            'description' => 'required|string',
            'status' => 'required|string|in:Activo,Inactivo,activo,inactivo,active,inactive',
            'category_group_id' => 'required|exists:category_group,id'
        ],[
            'name.required' => 'El nombre es obligatorio.',
            'name.string' => 'El nombre debe ser texto.',
            'name.max' => 'El nombre no puede superar los 50 caracteres.',
            'name.unique' => 'Ya existe una categoría con este nombre.',
            'description.required' => 'La descripción es obligatoria.',
            'description.string' => 'La descripción debe ser texto.',
            'status.required' => 'El estado es obligatorio.',
            'status.string' => 'El estado debe ser texto.',
            'status.in' => 'El estado debe ser uno de los valores permitidos.',
            'category_group_id.required' => 'El grupo de categoría es obligatorio.',
            'category_group_id.exists' => 'El grupo de categoría seleccionado no existe.'
        ]);

        $validado['status'] = in_array(strtolower($validado['status']), ['activo', 'active'])
            ? 'Activo'
            : 'Inactivo';
            
        $categoria->update($validado);

        return response()->json($categoria);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $categoria = CategoryTools::findOrFail($id);
        $categoria->delete();

        return response()->json(['message' => 'Categoría eliminada correctamente']);
    }
}