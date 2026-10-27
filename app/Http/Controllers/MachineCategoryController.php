<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\MachineCategory;

class MachineCategoryController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $categories = MachineCategory::all();
        return response()->json($categories);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validado = $request->validate([
            'name' => 'required|string|max:255|unique:machine_category,name',
            'description' => 'nullable|string',
        ],[
            'name.required' => 'El nombre es obligatorio.',
            'name.string' => 'El nombre debe ser texto.',
            'name.max' => 'El nombre no puede superar los 255 caracteres.',
            'description.string' => 'La descripción debe ser texto.',
        ]);

        $categoria = MachineCategory::create($validado);

        return response()->json($categoria, 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $categoria = MachineCategory::findOrFail($id);

        $validado = $request->validate([
            'name' => 'required|string|max:255|unique:machine_category,name,' . $categoria->id    ,
            'description' => 'nullable|string',
            'status' => 'required|in:active,inactive',
        ],[
            'name.required' => 'El nombre es obligatorio.',
            'name.string' => 'El nombre debe ser texto.',
            'name.max' => 'El nombre no puede superar los 255 caracteres.',
            'name.unique' => 'El nombre ya está en uso. Por favor, elige otro nombre.',
            'description.string' => 'La descripción debe ser texto.',
            'status.required' => 'El estado es obligatorio.',
            'status.in' => 'El estado debe ser "active" o "inactive".',
        ]);

        $categoria->update($validado);

        return response()->json($categoria);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $categoria = MachineCategory::findOrFail($id);
        $categoria->delete();

        return response()->json(['message' => 'Categoría eliminada correctamente']);
    }
}