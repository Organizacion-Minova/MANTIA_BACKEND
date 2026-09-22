<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\LocationCategory;
use Illuminate\Database\QueryException;

class LocationCategoryController extends Controller
{
    public function index()
    {
        $categorias = LocationCategory::all();
        return response()->json($categorias, 200);
    }

    public function store(Request $request)
    {
        $datosValidados = $request->validate([
            'nombre' => 'required|string|max:45|unique:location_category,name',
            'descripcion' => 'required|string|max:5000',
        ], [
            'nombre.max' => 'El nombre no puede superar los 45 caracteres.',
            'nombre.unique' => 'Ya existe una categoría registrada con este nombre.',
            'descripcion.max' => 'La descripción no puede superar los 5000 caracteres.',
        ]);

        $categoria = LocationCategory::create([
            'name' => $datosValidados['nombre'],
            'description' => $datosValidados['descripcion'],
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Categoría registrada correctamente.',
            'data' => $categoria,
        ], 201);
    }

    public function update(Request $request, $id)
    {
        $categoria = LocationCategory::findOrFail($id);

        $datosValidados = $request->validate([
            'nombre' => 'required|string|max:45|unique:location_category,name,' . $id . ',id',
            'descripcion' => 'required|string|max:5000',
        ], [
            'nombre.max' => 'El nombre no puede superar los 45 caracteres.',
            'nombre.unique' => 'Ya existe una categoría registrada con este nombre.',
            'descripcion.max' => 'La descripción no puede superar los 5000 caracteres.',
        ]);

        $categoria->update([
            'name' => $datosValidados['nombre'],
            'description' => $datosValidados['descripcion'],
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Categoría actualizada correctamente.',
            'data' => $categoria,
        ], 200);
    }

    public function destroy($id)
    {
        try {
            $categoria = LocationCategory::findOrFail($id);
            $categoria->delete();

            return response()->json([
                'status' => 'success',
                'message' => 'Categoría eliminada correctamente.'
            ], 200);

        } catch (QueryException $e) {
            if ($e->getCode() === '23503' || str_contains($e->getMessage(), 'foreign key constraint')) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'No se puede eliminar la categoría porque tiene ubicaciones asociadas.'
                ], 422); 
            }

            return response()->json([
                'status' => 'error',
                'message' => 'Error al eliminar la categoría.'
            ], 500);
        }
    }
}
