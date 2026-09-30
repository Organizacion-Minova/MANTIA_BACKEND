<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Tools;

class ToolsController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $tools = Tools::with('ubicacion','condiciones')->get();
        return response()->json($tools);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validado = $request->validate([
            'name' => 'required|string|max:100',
            'location_id' => 'required|exists:location,id',
            'category_id' => 'required|exists:category,id',
            'condiciones' => 'required|array',
            'condiciones.bueno' => 'required|integer|min:0',
            'condiciones.regular' => 'required|integer|min:0',
            'condiciones.malo' => 'required|integer|min:0',
        ]);

        $tool = Tools::create([
            'name' => $validado['name'],
            'location_id' => $validado['location_id'],
            'category_id' => $validado['category_id'],
        ]);

        $tool->condiciones()->createMany([
            ['condition' => 'bueno', 'stock' => $validado['condiciones']['bueno']],
            ['condition' => 'regular', 'stock' => $validado['condiciones']['regular']],
            ['condition' => 'malo', 'stock' => $validado['condiciones']['malo']],
        ]);

        return response()->json($tool->load(['ubicacion', 'condiciones']), 201);

    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $tools = Tools::findOrFail($id);
        return response()->json($tools);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $tool = Tools::findOrFail($id);

    $validado = $request->validate([
        'name' => 'required|string|max:100',
        'location_id' => 'required|exists:location,id',
        'category_id' => 'required|exists:category,id',
        'condiciones' => 'required|array',
        'condiciones.bueno' => 'required|integer|min:0',
        'condiciones.regular' => 'required|integer|min:0',
        'condiciones.malo' => 'required|integer|min:0',
    ]);

    $tool->update([
        'name' => $validado['name'],
        'location_id' => $validado['location_id'],
        'category_id' => $validado['category_id'],
    ]);

    foreach ($validado['condiciones'] as $estado => $cantidad) {
        $tool->condiciones()->updateOrCreate(
            ['condition' => $estado],
            ['stock' => $cantidad]
        );
    }

    return response()->json($tool->load(['ubicacion', 'condiciones']));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $tools = Tools::findOrFail($id);
        $tools->delete();

        return response()->json(['message' => 'Herramienta eliminada correctamente']);
    }
}
