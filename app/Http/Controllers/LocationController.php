<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Location;
use App\Models\LocationCategory;
use Illuminate\Database\QueryException;

class LocationController extends Controller
{
    public function index()
    {
        $ubicaciones = Location::with(['locationCategory'])->get();
        $categorias = LocationCategory::all();
        return response()->json([
            'ubicaciones' => $ubicaciones,
            'categorias' => $categorias
        ], 200);
    }

    public function store(Request $request)
    {
        $datosValidados = $request->validate([
            'nombre' => 'required|string|max:100|unique:location,name',
            'descripcion' => 'required|string|max:5000',
            'categoria' => 'required|integer|exists:location_category,id',
        ], [
            'nombre.max' => 'El nombre no puede superar los 100 caracteres.',
            'nombre.unique' => 'Ya existe una ubicación registrada con este nombre.',
            'descripcion.max' => 'La descripción no puede superar los 5000 caracteres.',
            'categoria.required' => 'La categoría es obligatoria.',
            'categoria.integer' => 'La categoría seleccionada no es válida.',
        ]);

        $ubicacion = Location::create([
            'name' => $datosValidados['nombre'],
            'description' => $datosValidados['descripcion'],
            'status' => 'activa',
            'location_category_id' =>  $datosValidados['categoria'],
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Ubicación registrada correctamente.',
            'data' => $ubicacion,
        ], 201);
    }

    public function update(Request $request, $id)
    {
        $ubicacion = Location::with(['locationCategory'])->findOrFail($id);

        $datosValidados = $request->validate([
            'nombre' => 'required|string|max:100|unique:location,name,' . $id . ',id',
            'descripcion' => 'required|string|max:5000',
            'categoria' => 'required|integer|exists:location_category,id',
        ], [
            'nombre.max' => 'El nombre no puede superar los 100 caracteres.',
            'nombre.unique' => 'Ya existe una ubicación registrada con este nombre.',
            'descripcion.max' => 'La descripción no puede superar los 5000 caracteres.',
            'categoria.integer' => 'La categoría seleccionada no es válida.',
            'categoria.required' => 'La categoría es obligatoria.',
        ]);

        $ubicacion->update([
            'name' => $datosValidados['nombre'],
            'description' => $datosValidados['descripcion'],
            'status' => $ubicacion->status ?? 'activa',
            'location_category_id' =>  $datosValidados['categoria'],
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Ubicación actualizada correctamente.',
            'data' => $ubicacion,
        ], 200);
    }

    public function destroy($id)
    {
        try {
            $ubicacion = Location::findOrFail($id);
            $ubicacion->delete();

            return response()->json([
                'status' => 'success',
                'message' => 'Ubicación eliminada correctamente.'
            ], 200);

        } catch (QueryException $e) {
            if ($e->getCode() === '23503' || str_contains($e->getMessage(), 'foreign key constraint')) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'No se puede eliminar la ubicación porque tiene equipos, mediciones de gases, maquinas o herramientas asociadas.'
                ], 422); 
            }

            return response()->json([
                'status' => 'error',
                'message' => 'Error al eliminar la ubicación.'
            ], 500);
        }
    }
}
