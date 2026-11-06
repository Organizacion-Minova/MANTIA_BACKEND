<?php

namespace App\Http\Controllers;

use App\Models\Machine;
use App\Rules\RolResponsable;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class MachinesController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $machines = Machine::with('category_maquina', 'ubicacion')->get();

        return response()->json($machines);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validado = $request->validate([
            'code' => ['required', 'string', 'max:20', 'unique:machine,code'],
            'name' => 'required|string|max:100',
            'model' => 'nullable|string|max:80',
            'serial_number' => 'nullable|string|max:100',
            'responsible' => ['required', 'string', 'max:45', new RolResponsable],
            'status' => 'required|string|in:Activo,Inactivo,Mantenimiento',
            'start_up_date' => 'nullable|date',
            'brand' => 'required|string|max:100',
            'plate' => 'required|string|max:30',
            'fuel' => 'nullable|string|max:50',
            'power' => 'nullable|string|max:100',
            'voltage' => 'nullable|string|max:50',
            'image' => 'nullable|image|max:10240',
            'location_id' => [
                'required',
                Rule::exists('location', 'id')->where(function ($query) {
                    $query->where('status', 'active');
                }),
            ],
            'machine_category_id' => 'required|exists:machine_category,id',
            'characteristics' => 'string',
        ], [
            'code.required' => 'El código es obligatorio.',
            'code.string' => 'El código debe ser texto.',
            'code.max' => 'El código no puede superar los 20 caracteres.',
            'code.unique' => 'Este código ya está asignado a otra máquina.',
            'name.required' => 'El nombre es obligatorio.',
            'name.string' => 'El nombre debe ser texto.',
            'name.max' => 'El nombre no puede superar los 100 caracteres.',
            'model.string' => 'El modelo debe ser texto.',
            'model.max' => 'El modelo no puede superar los 80 caracteres.',
            'serial_number.string' => 'El número de serie debe ser texto.',
            'serial_number.max' => 'El número de serie no puede superar los 100 caracteres.',
            'status.required' => 'El estado es obligatorio.',
            'status.string' => 'El estado debe ser texto.',
            'status.in' => 'El estado debe ser uno de los siguientes: Activo, Inactivo, Mantenimiento.',
            'start_up_date.date' => 'La fecha de puesta en servicio debe ser una fecha válida.',
            'responsible.required' => 'El responsable es obligatorio.',
            'responsible.string' => 'El responsable debe ser texto.',
            'responsible.max' => 'El responsable no puede superar los 45 caracteres.',
            'brand.required' => 'La marca es obligatoria.',
            'brand.string' => 'La marca debe ser texto.',
            'brand.max' => 'La marca no puede superar los 100 caracteres.',
            'plate.required' => 'La placa es obligatoria.',
            'plate.string' => 'La placa debe ser texto.',
            'plate.max' => 'La placa no puede superar los 30 caracteres.',
            'fuel.string' => 'El combustible debe ser texto.',
            'fuel.max' => 'El combustible no puede superar los 50 caracteres.',
            'power.string' => 'La potencia debe ser texto.',
            'power.max' => 'La potencia no puede superar los 100 caracteres.',
            'voltage.string' => 'El voltaje debe ser texto.',
            'voltage.max' => 'El voltaje no puede superar los 50 caracteres.',
            'location_id.required' => 'La ubicación es obligatoria.',
            'location_id.exists' => 'La ubicación seleccionada no existe.',
            'machine_category_id.required' => 'La categoría de la máquina es obligatoria.',
            'machine_category_id.exists' => 'La categoría de la máquina seleccionada no existe.',
            'image.image' => 'El archivo seleccionado debe ser una imagen válida.',
            'image.max' => 'La imagen no puede superar los 10 MB.',
            'characteristics.string' => 'Las características deben ser texto.',
        ]);

        $rutaImagen = null;
        if ($request->hasFile('image')) {
            $rutaImagen = $request->file('image')->store('machines', 'public');

            if (! $rutaImagen) {
                return response()->json(['message' => 'No fue posible guardar la imagen de la máquina.'], 500);
            }

            $validado['image'] = $rutaImagen;
        }

        try {
            $machine = Machine::create($validado);
        } catch (\Throwable $e) {
            if ($rutaImagen) {
                Storage::disk('public')->delete($rutaImagen);
            }

            throw $e;
        }

        return response()->json($machine->load(['category_maquina', 'ubicacion']), 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $machine = Machine::with('category_maquina', 'ubicacion')->findOrFail($id);

        return response()->json($machine);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $machine = Machine::findOrFail($id);

        $validado = $request->validate([
            'code' => ['required', 'string', 'max:20', Rule::unique('machine', 'code')->ignore($machine->id)],
            'name' => 'required|string|max:100',
            'model' => 'nullable|string|max:80',
            'serial_number' => 'nullable|string|max:100',
            'responsible' => ['required', 'string', 'max:45', new RolResponsable($machine->responsible)],
            'status' => 'required|string|in:Activo,Inactivo,Mantenimiento',
            'start_up_date' => 'nullable|date',
            'brand' => 'required|string|max:100',
            'plate' => 'required|string|max:30',
            'fuel' => 'nullable|string|max:50',
            'power' => 'nullable|string|max:100',
            'voltage' => 'nullable|string|max:50',
            'image' => 'nullable|image|max:10240',
            'location_id' => [
                'required',
                Rule::exists('location', 'id')->where(function ($query) {
                    $query->where('status', 'active');
                }),
            ],
            'machine_category_id' => 'required|exists:machine_category,id',
            'characteristics' => 'string',
        ], [
            'code.required' => 'El código es obligatorio.',
            'code.string' => 'El código debe ser texto.',
            'code.max' => 'El código no puede superar los 20 caracteres.',
            'code.unique' => 'Este código ya está asignado a otra máquina.',
            'name.required' => 'El nombre es obligatorio.',
            'name.string' => 'El nombre debe ser texto.',
            'name.max' => 'El nombre no puede superar los 100 caracteres.',
            'model.string' => 'El modelo debe ser texto.',
            'model.max' => 'El modelo no puede superar los 80 caracteres.',
            'serial_number.string' => 'El número de serie debe ser texto.',
            'serial_number.max' => 'El número de serie no puede superar los 100 caracteres.',
            'status.required' => 'El estado es obligatorio.',
            'status.string' => 'El estado debe ser texto.',
            'status.in' => 'El estado debe ser uno de los siguientes: Activo, Inactivo, Mantenimiento.',
            'start_up_date.date' => 'La fecha de puesta en servicio debe ser una fecha válida.',
            'responsible.required' => 'El responsable es obligatorio.',
            'responsible.string' => 'El responsable debe ser texto.',
            'responsible.max' => 'El responsable no puede superar los 45 caracteres.',
            'brand.required' => 'La marca es obligatoria.',
            'brand.string' => 'La marca debe ser texto.',
            'brand.max' => 'La marca no puede superar los 100 caracteres.',
            'plate.required' => 'La placa es obligatoria.',
            'plate.string' => 'La placa debe ser texto.',
            'plate.max' => 'La placa no puede superar los 30 caracteres.',
            'fuel.string' => 'El combustible debe ser texto.',
            'fuel.max' => 'El combustible no puede superar los 50 caracteres.',
            'power.string' => 'La potencia debe ser texto.',
            'power.max' => 'La potencia no puede superar los 100 caracteres.',
            'voltage.string' => 'El voltaje debe ser texto.',
            'voltage.max' => 'El voltaje no puede superar los 50 caracteres.',
            'location_id.required' => 'La ubicación es obligatoria.',
            'location_id.exists' => 'La ubicación seleccionada no existe.',
            'machine_category_id.required' => 'La categoría de la máquina es obligatoria.',
            'machine_category_id.exists' => 'La categoría de la máquina seleccionada no existe.',
            'image.image' => 'El archivo seleccionado debe ser una imagen válida.',
            'image.max' => 'La imagen no puede superar los 10 MB.',
            'characteristics.string' => 'Las características deben ser texto.',
        ]);

        $imagenAnterior = $machine->image;
        $rutaImagen = null;

        if ($request->hasFile('image')) {
            $rutaImagen = $request->file('image')->store('machines', 'public');

            if (! $rutaImagen) {
                return response()->json(['message' => 'No fue posible guardar la imagen de la máquina.'], 500);
            }

            $validado['image'] = $rutaImagen;
        } else {
            unset($validado['image']);
        }

        try {
            $machine->update($validado);
        } catch (\Throwable $e) {
            if ($rutaImagen) {
                Storage::disk('public')->delete($rutaImagen);
            }

            throw $e;
        }

        if ($rutaImagen && $imagenAnterior && $imagenAnterior !== $rutaImagen) {
            Storage::disk('public')->delete($imagenAnterior);
        }

        return response()->json($machine->load(['category_maquina', 'ubicacion']));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $machine = Machine::findOrFail($id);
        $imagen = $machine->image;

        try {
            // Transacción propia: en PostgreSQL un error deja inservible la transacción que lo contiene
            DB::transaction(fn () => $machine->delete());
        } catch (QueryException $e) {
            // 23503 (PostgreSQL) / 23000 (MySQL): la máquina tiene uso diario o inspecciones registradas
            if (in_array($e->getCode(), ['23503', '23000'], true)) {
                return response()->json([
                    'message' => 'No se puede eliminar la máquina porque tiene registros de uso diario o inspecciones.',
                ], 422);
            }
            throw $e;
        }

        if ($imagen) {
            Storage::disk('public')->delete($imagen);
        }

        return response()->json(['message' => 'Equipo eliminado correctamente']);
    }

    public function options()
    {
        return response()->json(
            Machine::select('id', 'name')->orderBy('name')->get()
        );
    }
}
