<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Equipment;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class EquipmentController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $equipments = Equipment::with('ubicacion', 'company')
            ->get();

        return response()->json($equipments);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validado = $request->validate([
            'code' => 'required|string|max:20|unique:equipment,code',
            'name' => 'required|string|max:100|unique:equipment,name',
            'description' => 'required|string',
            'serial_number' => 'required|string|max:20|unique:equipment,serial_number',
            'responsible' => 'required|string|max:100',
            'status' => 'required|string|in:active,inactive,maintenance',
            'registration_date' => 'required|date',
            'company_id' => 'required|exists:company,id',
            'location_id' => [
                'required',
                Rule::exists('location', 'id')->where(function ($query) {
                    $query->where('status', 'active');
                }),
            ],
            'model' => 'required|string|max:20',
            'image' => 'required|image|max:10240',
        ], [
            'code.required' => 'El código es obligatorio.',
            'code.string' => 'El código debe ser texto.',
            'code.max' => 'El código no puede superar los 20 caracteres.',
            'code.unique' => 'Este código ya está registrado. Ingresa un código diferente.',
            'name.required' => 'El nombre es obligatorio.',
            'name.string' => 'El nombre debe ser texto.',
            'name.max' => 'El nombre no puede superar los 100 caracteres.',
            'description.required' => 'La descripción es obligatoria.',
            'description.string' => 'La descripción debe ser texto.',
            'serial_number.required' => 'El número de serie es obligatorio.',
            'serial_number.string' => 'El número de serie debe ser texto.',
            'serial_number.max' => 'El número de serie no puede superar los 20 caracteres.',
            'serial_number.unique' => 'Este número de serie ya está registrado. Ingresa un número de serie diferente.',
            'responsible.required' => 'El responsable es obligatorio.',
            'responsible.string' => 'El responsable debe ser texto.',
            'responsible.max' => 'El responsable no puede superar los 100 caracteres.',
            'status.required' => 'El estado es obligatorio.',
            'status.string' => 'El estado debe ser texto.',
            'status.in' => 'El estado debe ser uno de los valores permitidos.',
            'registration_date.required' => 'La fecha de registro es obligatoria.',
            'registration_date.date' => 'La fecha de registro debe ser una fecha válida.',
            'company_id.required' => 'La empresa es obligatoria.',
            'company_id.exists' => 'La empresa seleccionada no existe.',
            'location_id.required' => 'La ubicación es obligatoria.',
            'location_id.exists' => 'La ubicación seleccionada no existe.',
            'model.required' => 'El modelo es obligatorio.',
            'image.required' => 'La imagen es obligatoria.',
            'image.image' => 'El archivo debe ser una imagen.',
            'image.max' => 'El tamaño de la imagen no puede superar los 10 MB.',
        ]);

        $validado['image'] = $request->file('image')->store('equipos', 'public');

        $equipo = Equipment::create($validado);

        return response()->json($equipo->load(['company', 'ubicacion']), 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $equipo = Equipment::with('ubicacion', 'company')->findOrFail($id);
        return response()->json($equipo);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $equipo = Equipment::findOrFail($id);

        $validado = $request->validate([
            'code' => 'required|string|max:20|unique:equipment,code,' . $equipo->id,
            'name' => 'required|string|max:100',
            'description' => 'required|string',
            'serial_number' => 'required|string|max:20|unique:equipment,serial_number,' . $equipo->id,
            'responsible' => 'required|string|max:100',
            'status' => 'required|string|in:active,inactive,maintenance',
            'registration_date' => 'required|date',
            'company_id' => 'required|exists:company,id',
            'location_id' => [
                'required',
                Rule::exists('location', 'id')->where(function ($query) {
                    $query->where('status', 'active');
                }),
            ],
            'model' => 'required|string|max:20',
            'image' => 'nullable|image|max:10240',
        ], [
            'code.required' => 'El código es obligatorio.',
            'code.string' => 'El código debe ser texto.',
            'code.max' => 'El código no puede superar los 20 caracteres.',
            'code.unique' => 'Este código ya está asignado a otro equipo.',
            'name.required' => 'El nombre es obligatorio.',
            'name.string' => 'El nombre debe ser texto.',
            'name.max' => 'El nombre no puede superar los 100 caracteres.',
            'description.required' => 'La descripción es obligatoria.',
            'description.string' => 'La descripción debe ser texto.',
            'serial_number.required' => 'El número de serie es obligatorio.',
            'serial_number.string' => 'El número de serie debe ser texto.',
            'serial_number.max' => 'El número de serie no puede superar los 20 caracteres.',
            'serial_number.unique' => 'Este número de serie ya está asignado a otro equipo.',
            'responsible.required' => 'El responsable es obligatorio.',
            'responsible.string' => 'El responsable debe ser texto.',
            'responsible.max' => 'El responsable no puede superar los 100 caracteres.',
            'status.required' => 'El estado es obligatorio.',
            'status.string' => 'El estado debe ser texto.',
            'status.in' => 'El estado debe ser uno de los valores permitidos.',
            'registration_date.required' => 'La fecha de registro es obligatoria.',
            'registration_date.date' => 'La fecha de registro debe ser una fecha válida.',
            'company_id.required' => 'La empresa es obligatoria.',
            'company_id.exists' => 'La empresa seleccionada no existe.',
            'location_id.required' => 'La ubicación es obligatoria.',
            'location_id.exists' => 'La ubicación seleccionada no existe.',
            'model.required' => 'El modelo es obligatorio.',
            'image.image' => 'El archivo debe ser una imagen.',
            'image.max' => 'El tamaño de la imagen no puede superar los 10 MB.',
            'machine_category_id.required' => 'La categoría del equipo es obligatoria.',
            'machine_category_id.exists' => 'La categoría del equipo seleccionada no existe.',
        ]);

        if ($request->hasFile('image')) {
            $imagenAnterior = $equipo->image;
            $validado['image'] = $request->file('image')->store('equipos', 'public');
        }

        $equipo->update($validado);

        if (isset($imagenAnterior) && $imagenAnterior) {
            Storage::disk('public')->delete($imagenAnterior);
        }

        return response()->json($equipo->load(['company', 'ubicacion']));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $equipment = Equipment::findOrFail($id);
        $equipment->delete();
        return response()->json(['message' => 'Equipo eliminado correctamente']);
    }
}


