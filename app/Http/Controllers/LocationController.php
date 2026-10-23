<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use App\Models\Location;
use App\Models\LocationCategory;
use Illuminate\Database\QueryException;

class LocationController extends Controller
{
    private function reglas($ubicacion = null)
    {
        $actual = $ubicacion?->location_category_id;

        $reglas = [
            'nombre' => ['required', 'string', 'max:100', 'regex:/^[\p{L}\p{N}\s\-_.,#\/()°:]+$/u', Rule::unique('location', 'name')->ignore($ubicacion?->id)],
            'descripcion' => 'required|string|max:5000',
            'categoria' => [
                'required',
                'integer',
                Rule::exists('location_category', 'id')->where(
                    fn ($q) => $q->where(fn ($q) => $q->where('status', 'active')->orWhere('id', $actual))
                ),
            ],
        ];

        if ($ubicacion) {
            $reglas['estado'] = ['required', Rule::in(['active', 'inactive'])];
        }

        return $reglas;
    }

    private function mensajes()
    {
        return [
            'nombre.required' => 'El nombre es obligatorio.',
            'nombre.max' => 'El nombre no puede superar los 100 caracteres.',
            'nombre.regex' => 'El nombre solo puede contener letras, números, espacios y los caracteres - _ . , # / ( ) ° :',
            'nombre.unique' => 'Ya existe una ubicación registrada con este nombre.',
            'descripcion.required' => 'La descripción es obligatoria.',
            'descripcion.max' => 'La descripción no puede superar los 5000 caracteres.',
            'categoria.required' => 'La categoría es obligatoria.',
            'categoria.integer' => 'La categoría seleccionada no es válida.',
            'categoria.exists' => 'La categoría seleccionada no existe o está inactiva.',
            'estado.required' => 'El estado es obligatorio.',
            'estado.in' => 'El estado seleccionado no es válido.',
        ];
    }

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
        $datosValidados = $request->validate($this->reglas(), $this->mensajes());

        $ubicacion = Location::create([
            'name' => $datosValidados['nombre'],
            'description' => $datosValidados['descripcion'] ?? null,
            'status' => 'active',
            'location_category_id' => $datosValidados['categoria'],
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Ubicación registrada correctamente.',
            'data' => $ubicacion->load('locationCategory'),
        ], 201);
    }

    public function update(Request $request, $id)
    {
        $ubicacion = Location::findOrFail($id);

        $datosValidados = $request->validate($this->reglas($ubicacion), $this->mensajes());

        $ubicacion->update([
            'name' => $datosValidados['nombre'],
            'description' => $datosValidados['descripcion'] ?? null,
            'status' => $datosValidados['estado'],
            'location_category_id' => $datosValidados['categoria'],
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Ubicación actualizada correctamente.',
            'data' => $ubicacion->load('locationCategory'),
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
                    'message' => 'No se puede eliminar la ubicación porque tiene equipos, mediciones de gases, máquinas o herramientas asociadas.'
                ], 422);
            }

            return response()->json([
                'status' => 'error',
                'message' => 'Error al eliminar la ubicación.'
            ], 500);
        }
    }
}