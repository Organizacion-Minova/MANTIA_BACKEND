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
        $this->normalizeBooleanInput($request, 'in_operation');

        $validado = $request->validate([
            'code' => 'required|string|max:20|unique:machine,code',
            'name' => 'required|string|max:100|unique:machine,name',
            'model' => 'required|string|max:80',
            'serial_number' => 'required|string|max:100|unique:machine,serial_number',
            'acquisition_date' => 'required|date',
            'acquisition_cost' => 'required|numeric|min:0',
            'status' => 'required|string|in:active,inactive,maintenance',
            'warranty' => 'required|nullable|integer|min:0',
            'image' => 'nullable|image|max:10240',
            'location_id' => [
                'required',
                Rule::exists('location', 'id')->where(function ($query) {
                    $query->where('status', 'active');
                }),
            ],
            'machine_category_id' => 'required|exists:machine_category,id',
            'brand' => 'required|string|max:100',
            'responsible' => ['required', 'string', 'max:45', new RolResponsable()],
            'machine_usage' => 'required|string|max:45',
            'in_operation' => 'required|boolean',
            'characteristics' => 'required|string',
        ],[
            'code.required' => 'El código es obligatorio.',
            'code.string' => 'El código debe ser texto.',
            'code.max' => 'El código no puede superar los 20 caracteres.',
            'code.unique' => 'Este código ya está asignado a otra máquina.',
            'name.required' => 'El nombre es obligatorio.',
            'name.string' => 'El nombre debe ser texto.',
            'name.max' => 'El nombre no puede superar los 100 caracteres.',
            'model.required' => 'El modelo es obligatorio.',
            'model.string' => 'El modelo debe ser texto.',
            'model.max' => 'El modelo no puede superar los 80 caracteres.',
            'serial_number.required' => 'El número de serie es obligatorio.',
            'serial_number.string' => 'El número de serie debe ser texto.',
            'serial_number.max' => 'El número de serie no puede superar los 100 caracteres.',
            'serial_number.unique' => 'Este número de serie ya está asignado a otra máquina.',
            'acquisition_date.required' => 'La fecha de adquisición es obligatoria.',
            'acquisition_date.date' => 'La fecha de adquisición debe ser una fecha válida.',
            'acquisition_cost.required' => 'El costo de adquisición es obligatorio.',
            'acquisition_cost.numeric' => 'El costo de adquisición debe ser un número.',
            'acquisition_cost.min' => 'El costo de adquisición no puede ser negativo.',
            'status.required' => 'El estado es obligatorio.',
            'status.string' => 'El estado debe ser texto.',
            'status.in' => "El estado debe ser uno de los siguientes: active, inactive, maintenance.",
            'warranty.required' => 'La garantía es obligatoria.',
            'warranty.integer' => 'La garantía debe ser un número entero.',
            'warranty.min' => 'La garantía no puede ser negativa.',
            'image.image' => 'El archivo debe ser una imagen.',
            'image.max' => 'La imagen no puede superar los 10 MB.',
            'location_id.required' => 'La ubicación es obligatoria.',
            'location_id.exists' => 'La ubicación seleccionada no existe.',
            'machine_category_id.required' => 'La categoría de la máquina es obligatoria.',
            'machine_category_id.exists' => 'La categoría de la máquina seleccionada no existe.',

        ]);
        if ($request->hasFile('image')) {
            $validado['image'] = $request->file('image')->store('machines', 'public');
        }
        $machine = Machine::create($validado);

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
        $this->normalizeBooleanInput($request, 'in_operation');

        $validado = $request->validate([
            'code' => 'required|string|max:20|unique:machine,code,'.$machine->id,
            'name' => 'required|string|max:100',
            'model' => 'required|string|max:80',
            'serial_number' => 'required|string|max:100|unique:machine,serial_number,'.$machine->id,
            'acquisition_date' => 'required|date',
            'acquisition_cost' => 'required|numeric|min:0',
            'status' => 'required|string|in:active,inactive,maintenance',
            'warranty' => 'required|nullable|integer|min:0',
            'image' => 'nullable|image|max:10240',
            'location_id' => [
                'required',
                Rule::exists('location', 'id')->where(function ($query) {
                    $query->where('status', 'active');
                }),
            ],
            'machine_category_id' => 'required|exists:machine_category,id',
            'brand' => 'required|string|max:100',
            'responsible' => ['required', 'string', 'max:45', new RolResponsable($machine->responsible)],
            'machine_usage' => 'required|string|max:45',
            'in_operation' => 'required|boolean',
            'characteristics' => 'required|string',
        ], [
            'code.required' => 'El código es obligatorio.',
            'code.string' => 'El código debe ser texto.',
            'code.max' => 'El código no puede superar los 20 caracteres.',
            'code.unique' => 'Este código ya está asignado a otra máquina.',
            'name.required' => 'El nombre es obligatorio.',
            'name.string' => 'El nombre debe ser texto.',
            'name.max' => 'El nombre no puede superar los 100 caracteres.',
            'model.required' => 'El modelo es obligatorio.',
            'model.string' => 'El modelo debe ser texto.',
            'model.max' => 'El modelo no puede superar los 80 caracteres.',
            'serial_number.required' => 'El número de serie es obligatorio.',
            'serial_number.string' => 'El número de serie debe ser texto.',
            'serial_number.max' => 'El número de serie no puede superar los 100 caracteres.',
            'serial_number.unique' => 'Este número de serie ya está asignado a otra máquina.',
            'acquisition_date.required' => 'La fecha de adquisición es obligatoria.',
            'acquisition_date.date' => 'La fecha de adquisición debe ser una fecha válida.',
            'acquisition_cost.required' => 'El costo de adquisición es obligatorio.',
            'acquisition_cost.numeric' => 'El costo de adquisición debe ser un número.',
            'acquisition_cost.min' => 'El costo de adquisición no puede ser negativo.',
            'status.required' => 'El estado es obligatorio.',
            'status.string' => 'El estado debe ser texto.',
            'status.in' => "El estado debe ser uno de los siguientes: active, inactive, maintenance.",
            'warranty.required' => 'La garantía es obligatoria.',
            'warranty.integer' => 'La garantía debe ser un número entero.',
            'warranty.min' => 'La garantía no puede ser negativa.',
            'image.image' => 'El archivo debe ser una imagen.',
            'image.max' => 'La imagen no puede superar los 10 MB.',
            'location_id.required' => 'La ubicación es obligatoria.',
            'location_id.exists' => 'La ubicación seleccionada no existe.',
            'machine_category_id.required' => 'La categoría de la máquina es obligatoria.',
            'machine_category_id.exists' => 'La categoría de la máquina seleccionada no existe.',]);

        if ($request->hasFile('image')) {
            $imagenAnterior = $machine->image;
            $validado['image'] = $request->file('image')->store('machines', 'public');
        }
        $machine->update($validado);

        if (isset($imagenAnterior)) {
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
        $image = $machine->image;

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

        if ($image) {
            Storage::disk('public')->delete($image);
        }

        return response()->json(['message' => 'Equipo eliminado correctamente']);
    }

    private function normalizeBooleanInput(Request $request, string $field): void
    {
        $value = $request->input($field);

        if (is_string($value) && in_array(strtolower($value), ['true', 'false'], true)) {
            $request->merge([$field => strtolower($value) === 'true']);
        }
    }
}