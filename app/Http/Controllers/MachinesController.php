<?php

namespace App\Http\Controllers;

use App\Models\Machine;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class MachinesController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $machines = Machine::with('category_maquina', 'ubicacion', 'company')->get();

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
            'name' => 'required|string|max:100',
            'model' => 'required|string|max:80',
            'serial_number' => 'required|string|max:100|unique:machine,serial_number',
            'acquisition_date' => 'required|date',
            'acquisition_cost' => 'required|numeric|min:0',
            'status' => 'required|string|in:active,inactive,maintenance',
            'warranty' => 'required|nullable|integer|min:0',
            'image' => 'nullable|image|max:10240',
            'location_id' => 'required|exists:location,id',
            'machine_category_id' => 'required|exists:machine_category,id',
            'company_id' => 'required|exists:company,id',
            'responsible' => 'required|string|max:45',
            'machine_usage' => 'required|string|max:45',
            'in_operation' => 'required|boolean',
            'characteristics' => 'required|string',
        ]);
        if ($request->hasFile('image')) {
            $validado['image'] = $request->file('image')->store('machines', 'public');
        }
        $machine = Machine::create($validado);

        return response()->json($machine->load(['category_maquina', 'ubicacion', 'company']), 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $machine = Machine::with('category_maquina', 'ubicacion', 'company')->findOrFail($id);

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
            'location_id' => 'required|exists:location,id',
            'machine_category_id' => 'required|exists:machine_category,id',
            'company_id' => 'required|exists:company,id',
            'responsible' => 'required|string|max:45',
            'machine_usage' => 'required|string|max:45',
            'in_operation' => 'required|boolean',
            'characteristics' => 'required|string',
        ]);

        if ($request->hasFile('image')) {
            $imagenAnterior = $machine->image;
            $validado['image'] = $request->file('image')->store('machines', 'public');
        }
        $machine->update($validado);

        if (isset($imagenAnterior)) {
            Storage::disk('public')->delete($imagenAnterior);
        }

        return response()->json($machine->load(['category_maquina', 'ubicacion', 'company']));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $machine = Machine::findOrFail($id);
        $image = $machine->image;
        $machine->delete();
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
