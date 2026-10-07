<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\CategoryTools;

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
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'status' => 'required|string|in:Activo,Inactivo,activo,inactivo,active,inactive',
            'category_group_id' => 'required|exists:category_group,id'
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
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'status' => 'required|string|in:Activo,Inactivo,activo,inactivo,active,inactive',
            'category_group_id' => 'required|exists:category_group,id'
        ]);

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
