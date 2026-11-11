<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use App\Models\Tools;
use App\Services\AlertaStockHerramienta;
use Illuminate\Validation\Rule;

class ToolsController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $tools = Tools::with('ubicacion','condiciones','categoria')->get();
        return response()->json($tools);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validado = $request->validate([
            'code' => 'nullable|string|max:30|unique:tool,code',
            'name' => 'required|string|max:100|unique:tool,name',
            'location_id' => [
                'required',
                Rule::exists('location', 'id')->where(function ($query) {
                    $query->where('status', 'active');
                }),
            ],
            'category_id' => 'required|exists:category,id',
            'condiciones' => 'required|array',
            'condiciones.bueno' => 'required|integer|min:0|max:1000',
            'condiciones.regular' => 'required|integer|min:0|max:1000',
            'condiciones.malo' => 'required|integer|min:0|max:1000',
        ],[
            'code.string' => 'El código debe ser texto.',
            'code.max' => 'El código no puede superar los 30 caracteres.',
            'code.unique' => 'Ya existe una herramienta registrada con este código.',
            'name.required' => 'El nombre es obligatorio.',
            'name.string' => 'El nombre debe ser texto.',
            'name.max' => 'El nombre no puede superar los 100 caracteres.',
            'name.unique' => 'Ya existe una herramienta registrada con este nombre.',
            'location_id.required' => 'La ubicación es obligatoria.',
            'location_id.exists' => 'La ubicación seleccionada no existe.',
            'category_id.required' => 'La categoría es obligatoria.',
            'category_id.exists' => 'La categoría seleccionada no existe.',
            'condiciones.required' => 'Las condiciones son obligatorias.',
            'condiciones.array' => 'Las condiciones deben ser un arreglo.',
            'condiciones.bueno.required' => 'La cantidad de herramientas en buen estado es obligatoria.',
            'condiciones.bueno.integer' => 'La cantidad de herramientas en buen estado debe ser un número entero.',
            'condiciones.bueno.min' => 'La cantidad de herramientas en buen estado no puede ser negativa.',
            'condiciones.regular.required' => 'La cantidad de herramientas en estado regular es obligatoria.',
            'condiciones.regular.integer' => 'La cantidad de herramientas en estado regular debe ser un número entero.',
            'condiciones.regular.min' => 'La cantidad de herramientas en estado regular no puede ser negativa.',
            'condiciones.malo.required' => 'La cantidad de herramientas en mal estado es obligatoria.',
            'condiciones.malo.integer' => 'La cantidad de herramientas en mal estado debe ser un número entero.',
            'condiciones.malo.min' => 'La cantidad de herramientas en mal estado no puede ser negativa.',
            'condiciones.bueno.max' => 'La cantidad de herramientas en buen estado no puede superar los 1000.',
            'condiciones.regular.max' => 'La cantidad de herramientas en estado regular no puede superar los 1000.',
            'condiciones.malo.max' => 'La cantidad de herramientas en mal estado no puede superar los 1000.',
        ]);
        $this->validarStockMinimo($validado['condiciones']);

        $tool = Tools::create([
            'code' => $validado['code'] ?? null,
            'name' => $validado['name'],
            'location_id' => $validado['location_id'],
            'category_id' => $validado['category_id'],
        ]);

        $tool->condiciones()->createMany([
            ['condition' => 'bueno', 'stock' => $validado['condiciones']['bueno']],
            ['condition' => 'regular', 'stock' => $validado['condiciones']['regular']],
            ['condition' => 'malo', 'stock' => $validado['condiciones']['malo']],
        ]);

        // Alerta de stock bajo / mal estado, ya con las tres condiciones guardadas
        AlertaStockHerramienta::revisar($tool);

        return response()->json($tool->load(['ubicacion', 'condiciones', 'categoria']), 201);

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
            'code' => 'nullable|string|max:30|unique:tool,code,' . $tool->id,
            'name' => 'required|string|max:100|unique:tool,name,' . $tool->id,
            'location_id' => [
                    'required',
                    Rule::exists('location', 'id')->where(function ($query) {
                        $query->where('status', 'active');
                    }),
                ],
            'category_id' => 'required|exists:category,id',
            'condiciones' => 'required|array',
            'condiciones.bueno' => 'required|integer|min:0|max:1000',
            'condiciones.regular' => 'required|integer|min:0|max:1000',
            'condiciones.malo' => 'required|integer|min:0|max:1000',
        ],[
            'code.string' => 'El código debe ser texto.',
            'code.max' => 'El código no puede superar los 50 caracteres.',
            'code.unique' => 'Ya existe una herramienta registrada con este código.',
            'name.required' => 'El nombre es obligatorio.',
            'name.string' => 'El nombre debe ser texto.',
            'name.max' => 'El nombre no puede superar los 100 caracteres.',
            'name.unique' => 'Ya existe una herramienta registrada con este nombre.',
            'location_id.required' => 'La ubicación es obligatoria.',
            'location_id.exists' => 'La ubicación seleccionada no existe.',
            'category_id.required' => 'La categoría es obligatoria.',
            'category_id.exists' => 'La categoría seleccionada no existe.',
            'condiciones.required' => 'Las condiciones son obligatorias.',
            'condiciones.array' => 'Las condiciones deben ser un arreglo.',
            'condiciones.bueno.required' => 'La cantidad de herramientas en buen estado es obligatoria.',
            'condiciones.bueno.integer' => 'La cantidad de herramientas en buen estado debe ser un número entero.',
            'condiciones.bueno.min' => 'La cantidad de herramientas en buen estado no puede ser negativa.',
            'condiciones.regular.required' => 'La cantidad de herramientas en estado regular es obligatoria.',
            'condiciones.regular.integer' => 'La cantidad de herramientas en estado regular debe ser un número entero.',
            'condiciones.regular.min' => 'La cantidad de herramientas en estado regular no puede ser negativa.',
            'condiciones.malo.required' => 'La cantidad de herramientas en mal estado es obligatoria.',
            'condiciones.malo.integer' => 'La cantidad de herramientas en mal estado debe ser un número entero.',
            'condiciones.malo.min' => 'La cantidad de herramientas en mal estado no puede ser negativa.',
            'condiciones.bueno.max' => 'La cantidad de herramientas en buen estado no puede superar los 1000.',
            'condiciones.regular.max' => 'La cantidad de herramientas en estado regular no puede superar los 1000.',
            'condiciones.malo.max' => 'La cantidad de herramientas en mal estado no puede superar los 1000.',
        ]);

        $this->validarStockMinimo($validado['condiciones']);

        // Stock antes de editar, para avisar solo si baja o aumentan las de mal estado
        $stockAntes = $tool->condiciones()->pluck('stock', 'condition')->map(fn ($n) => (int) $n)->all();

        $datosActualizacion = [
            'name' => $validado['name'],
            'location_id' => $validado['location_id'],
            'category_id' => $validado['category_id'],
        ];
        if (array_key_exists('code', $validado)) {
            $datosActualizacion['code'] = $validado['code'];
        }
        $tool->update($datosActualizacion);

        foreach ($validado['condiciones'] as $estado => $cantidad) {
            $tool->condiciones()->updateOrCreate(
                ['condition' => $estado],
                ['stock' => $cantidad]
            );
        }

        AlertaStockHerramienta::revisar($tool, $stockAntes);

        return response()->json($tool->load(['ubicacion', 'condiciones','categoria']));
    }

    private function validarStockMinimo(array $condiciones): void
    {
        if ($condiciones['bueno'] + $condiciones['regular'] + $condiciones['malo'] < 1) {
            throw ValidationException::withMessages([
                'condiciones' => 'La suma de las herramientas debe ser al menos 1.',
            ]);
        }
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