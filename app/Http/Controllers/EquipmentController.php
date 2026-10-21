<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Equipment;
use Illuminate\Support\Facades\Storage;
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
            'name' => 'required|string|max:100',
            'description' => 'required|string',
            'serial_number' => 'required|string|max:20|unique:equipment,serial_number',
            'responsible' => 'required|string|max:100',
            'status' => 'required|string|in:active,inactive,maintenance',
            'registration_date' => 'required|date',
            'company_id' => 'required|exists:company,id',
            'location_id' => 'required|exists:location,id',
            'model' => 'required|string|max:20',
            'image' => 'required|image|max:10240',
        ], [
            'code.required' => 'El código es obligatorio.',
            'code.string' => 'El código debe ser texto.',
            'code.max' => 'El código no puede superar los 20 caracteres.',
            'code.unique' => 'Este código ya está registrado. Ingresa un código diferente.',
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
            'location_id' => 'required|exists:location,id',
            'model' => 'required|string|max:20',
            'image' => 'nullable|image|max:10240',
        ], [
            'code.required' => 'El código es obligatorio.',
            'code.string' => 'El código debe ser texto.',
            'code.max' => 'El código no puede superar los 20 caracteres.',
            'code.unique' => 'Este código ya está asignado a otro equipo.',
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


