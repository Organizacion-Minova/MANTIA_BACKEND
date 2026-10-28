<?php

namespace App\Http\Controllers;

use App\Models\Equipment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class EquipmentController extends Controller
{
    /**
     * Mostrar todos los equipos.
     */
    public function index()
    {
        $equipments = Equipment::with('ubicacion')->get();

        return response()->json($equipments);
    }

    /**
     * Registrar un nuevo equipo.
     */
    public function store(Request $request)
    {
        $validado = $request->validate([
            'code' => ['required','string','max:20','unique:equipment,code',],
            'name' => ['required','string','max:100','unique:equipment,name',],
            'model' => 'required|string|max:100',
            'serial_number' => ['required','string','max:100','unique:equipment,serial_number',],
            'responsible' => 'required|string|max:100',
            'status' => 'required|string|in:active,inactive,maintenance',
            'start_up_date' => 'nullable|date',
            'brand' => 'required|string|max:100',
            'plate' => 'nullable|string|max:100',
            'fuel' => 'nullable|string|max:255',
            'power' => 'nullable|string|max:100',
            'voltage' => 'nullable|string|max:100',
            'location_id' => ['required',Rule::exists('location', 'id')->where(fn ($query) => $query->where('status', 'active')),],
            'description' => 'required|string',
            'image' => 'required|image|max:10240',
        ], [
            'code.required' => 'El código del equipo es obligatorio.',
            'code.unique' => 'Este código ya está registrado.',
            'name.required' => 'El nombre del equipo es obligatorio.',
            'name.unique' => 'Ya existe un equipo con este nombre.',
            'model.required' => 'El modelo del equipo es obligatorio.',
            'serial_number.required' => 'El número de serie es obligatorio.',
            'serial_number.unique' => 'Este número de serie ya está registrado.',
            'responsible.required' => 'El responsable es obligatorio.',
            'status.required' => 'El estado del equipo es obligatorio.',
            'status.in' => 'El estado seleccionado no es válido.',
            'start_up_date.date' => 'La fecha de puesta en servicio no es válida.',
            'brand.required' => 'La marca del equipo es obligatoria.',
            'location_id.required' => 'Debes seleccionar una ubicación.',
            'location_id.exists' => 'La ubicación seleccionada no existe o está inactiva.',
            'description.required' => 'La descripción es obligatoria.',
            'image.required' => 'Debes seleccionar una imagen para el equipo.',
            'image.image' => 'El archivo seleccionado debe ser una imagen válida.',
            'image.max' => 'La imagen no puede superar los 10 MB.',
        ]);

        // Guardar la imagen en storage/app/public/equipos.
        $rutaImagen = $request->file('image')->store('equipos', 'public');

        if (!$rutaImagen) {
            return response()->json([
                'message' => 'No fue posible guardar la imagen del equipo.',
            ], 500);
        }

        $validado['image'] = $rutaImagen;

        try {
            $equipo = Equipment::create($validado);

            return response()->json(
                $equipo->load('ubicacion'),
                201
            );
        } catch (\Throwable $e) {
            // Evitar dejar una imagen sin registro asociado.
            Storage::disk('public')->delete($rutaImagen);

            throw $e;
        }
    }

    /**
     * Mostrar un equipo específico.
     */
    public function show(string $id)
    {
        $equipo = Equipment::with('ubicacion')->findOrFail($id);

        return response()->json($equipo);
    }

    /**
     * Actualizar un equipo existente.
     */
    public function update(Request $request, string $id)
    {
        $equipo = Equipment::findOrFail($id);

        $validado = $request->validate([
            'code' => ['required','string','max:20',Rule::unique('equipment', 'code')->ignore($equipo->id),],
            'name' => ['required','string','max:100',Rule::unique('equipment', 'name')->ignore($equipo->id),],
            'model' => 'required|string|max:100',
            'serial_number' => ['required','string','max:100',Rule::unique('equipment', 'serial_number')->ignore($equipo->id),],
            'responsible' => 'required|string|max:100',
            'status' => 'required|string|in:active,inactive,maintenance',
            'start_up_date' => 'nullable|date',
            'brand' => 'required|string|max:100',
            'plate' => 'nullable|string|max:100',
            'fuel' => 'nullable|string|max:255',
            'power' => 'nullable|string|max:100',
            'voltage' => 'nullable|string|max:100',
            'location_id' => ['required',Rule::exists('location', 'id')->where(fn ($query) => $query->where('status', 'active')),],
            'description' => 'required|string',
            'image' => 'sometimes|nullable|image|max:10240',
        ], [
            'code.required' => 'El código del equipo es obligatorio.',
            'code.unique' => 'Este código ya está asignado a otro equipo.',
            'name.required' => 'El nombre del equipo es obligatorio.',
            'name.unique' => 'Ya existe otro equipo con este nombre.',
            'model.required' => 'El modelo del equipo es obligatorio.',
            'serial_number.required' => 'El número de serie es obligatorio.',
            'serial_number.unique' => 'Este número de serie ya está asignado a otro equipo.',
            'responsible.required' => 'El responsable es obligatorio.',
            'status.required' => 'El estado del equipo es obligatorio.',
            'status.in' => 'El estado seleccionado no es válido.',
            'start_up_date.date' => 'La fecha de puesta en servicio no es válida.',
            'brand.required' => 'La marca del equipo es obligatoria.',
            'location_id.required' => 'Debes seleccionar una ubicación.',
            'location_id.exists' => 'La ubicación seleccionada no existe o está inactiva.',
            'description.required' => 'La descripción es obligatoria.',
            'image.image' => 'El archivo seleccionado debe ser una imagen válida.',
            'image.max' => 'La imagen no puede superar los 10 MB.',
        ]);

        $imagenAnterior = $equipo->image;
        $nuevaImagen = null;

        // Guardar la nueva imagen si se recibió un archivo.
        if ($request->hasFile('image')) {
            $nuevaImagen = $request->file('image')->store(
                'equipos',
                'public'
            );

            if (!$nuevaImagen) {
                return response()->json([
                    'message' => 'No fue posible guardar la nueva imagen.',
                ], 500);
            }

            $validado['image'] = $nuevaImagen;
        } else {
            // No modificar la imagen existente si no se envía otra.
            unset($validado['image']);
        }

        try {
            $equipo->update($validado);
        } catch (\Throwable $e) {
            // Si falla la actualización, eliminar la imagen nueva.
            if ($nuevaImagen) {
                Storage::disk('public')->delete($nuevaImagen);
            }

            throw $e;
        }

        // Eliminar la imagen anterior solo después de actualizar el equipo.
        if (
            $nuevaImagen &&
            $imagenAnterior &&
            $imagenAnterior !== $nuevaImagen
        ) {
            Storage::disk('public')->delete($imagenAnterior);
        }

        return response()->json(
            $equipo->fresh()->load('ubicacion')
        );
    }

    /**
     * Eliminar un equipo.
     */
    public function destroy(string $id)
    {
        $equipo = Equipment::findOrFail($id);

        $imagen = $equipo->image;

        $equipo->delete();

        if ($imagen) {
            Storage::disk('public')->delete($imagen);
        }

        return response()->json([
            'message' => 'Equipo eliminado correctamente.',
        ]);
    }
}