<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use App\Models\LocationCategory;
use Illuminate\Database\QueryException;

class LocationCategoryController extends Controller
{
    private function reglas($id = null)
    {
        $reglas = [
            'nombre' => ['required', 'string', 'max:45', 'regex:/^[\p{L}\p{N}\s\-]+$/u', Rule::unique('location_category', 'name')->ignore($id)],
            'descripcion' => 'required|string|max:5000',
        ];

        if ($id) {
            $reglas['estado'] = ['required', Rule::in(['active', 'inactive'])];
        }

        return $reglas;
    }

    private function mensajes()
    {
        return [
            'nombre.required' => 'El nombre es obligatorio.',
            'nombre.max' => 'El nombre no puede superar los 45 caracteres.',
            'nombre.regex' => 'El nombre solo puede contener letras, números, espacios y guiones.',
            'nombre.unique' => 'Ya existe una categoría registrada con este nombre.',
            'descripcion.required' => 'La descripción es obligatoria.',
            'descripcion.max' => 'La descripción no puede superar los 5000 caracteres.',
            'estado.required' => 'El estado es obligatorio.',
            'estado.in' => 'El estado seleccionado no es válido.',
        ];
    }

    public function index()
    {
        $categorias = LocationCategory::all();
        return response()->json($categorias, 200);
    }

    public function store(Request $request)
    {
        $datosValidados = $request->validate($this->reglas(), $this->mensajes());

        $categoria = LocationCategory::create([
            'name' => $datosValidados['nombre'],
            'description' => $datosValidados['descripcion'],
            'status' => 'active',
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

        $datosValidados = $request->validate($this->reglas($id), $this->mensajes());

        $categoria->update([
            'name' => $datosValidados['nombre'],
            'description' => $datosValidados['descripcion'],
            'status' => $datosValidados['estado'],
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
