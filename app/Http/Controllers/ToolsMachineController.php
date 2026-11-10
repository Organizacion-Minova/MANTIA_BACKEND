<?php

namespace App\Http\Controllers;

use App\Models\Tools;
use App\Models\ToolsMachine;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ToolsMachineController extends Controller
{
    /**
     * GET: consumibles asignados a una máquina.
     */
    public function index(Request $request)
    {
        $request->validate([
            'machine_id' => 'required|exists:machine,id',
        ]);

        $items = ToolsMachine::with('tool:id,name')
            ->where('machine_id', $request->machine_id)
            ->orderBy('id')
            ->get();

        return response()->json($items);
    }

    /**
     * GET: catálogo para el selector.
     * Solo consumibles que la máquina aún no tiene.
     */
    public function catalog(Request $request)
    {
        $request->validate([
            'machine_id' => 'required|exists:machine,id',
        ]);

        $asignados = ToolsMachine::where('machine_id', $request->machine_id)->pluck('tool_id');

        $tools = Tools::consumable()
            ->whereNotIn('id', $asignados)
            ->orderBy('name')
            ->get(['id', 'name'])
            ->makeHidden('stock_total'); // evita consultas extra por el $appends del modelo

        return response()->json($tools);
    }

    /**
     * POST: asignar un consumible a una máquina.
     */
    public function store(Request $request)
    {
        $validado = $request->validate([
            'machine_id' => 'required|exists:machine,id',
            'tool_id' => [
                'required',
                'integer',
                Rule::unique('tools_machine', 'tool_id')
                    ->where(fn ($q) => $q->where('machine_id', $request->machine_id)),
                function ($attribute, $value, $fail) {
                    if (!Tools::consumable()->whereKey($value)->exists()) {
                        $fail('Solo se pueden asignar herramientas consumibles.');
                    }
                },
            ],
            'brand'        => 'nullable|string|max:50',
            'stock'        => 'required|integer|min:0|max:1000',
            'observations' => 'nullable|string',
        ], [
            'stock.required' => 'La cantidad es obligatoria.',
            'stock.integer' => 'La cantidad debe ser un número entero.',
            'stock.min' => 'La cantidad no puede ser negativa.',
            'stock.max' => 'La cantidad no puede superar los 1000.',

        ]);

        $item = ToolsMachine::create($validado);

        return response()->json($item->load('tool:id,name'), 201);
    }

    /**
     * Mostrar un equipo específico.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * PUT: editar marca, cantidad u observaciones.
     * La herramienta y la máquina no se cambian: se elimina y se crea otra.
     */
    public function update(Request $request, string $id)
    {
        $item = ToolsMachine::findOrFail($id);

        $validado = $request->validate([
            'brand'        => 'nullable|string|max:100',
            'stock'        => 'sometimes|required|integer|min:0',
            'observations' => 'nullable|string',
        ], [
            'stock.required' => 'La cantidad es obligatoria.',
            'stock.integer' => 'La cantidad debe ser un número entero.',
            'stock.min' => 'La cantidad no puede ser negativa.',
        ]);

        $item->update($validado);

        return response()->json($item->load('tool:id,name'));
    }

    /**
     * DELETE: quitar el consumible de la máquina.
     */
    public function destroy(string $id)
    {
        ToolsMachine::findOrFail($id)->delete();

        return response()->noContent();
    }
}