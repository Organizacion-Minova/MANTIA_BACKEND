<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\InspectionEquipment;
use App\Models\InspectionQuestionEquipment;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class InspectionEquipmentController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $request->validate([
            'equipment_id' => 'sometimes|exists:equipment,id',
        ]);

        $inspecciones = InspectionEquipment::with('equipo')
            ->when($request->filled('equipment_id'), fn ($q) => $q->where('equipment_id', $request->equipment_id))
            ->orderByDesc('inspection_date')
            ->orderByDesc('id')
            ->paginate(15);

        return response()->json($inspecciones);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validado = $request->validate([
            // Cabecera
            'equipment_id'    => 'required|exists:equipment,id',
            'inspector_name'  => 'required|string|max:255',
            'inspection_date' => 'required|date',
            'inspection_time' => 'nullable|date_format:H:i',
            'equipment_brand' => 'required|string|max:255',
            'voltage'         => 'nullable|string|max:50',
            'serial_number'   => 'nullable|string|max:100',
            'area'            => 'required|string|max:255',

            // Pie
            'general_observations'        => 'nullable|string',
            'responsible_position'        => 'nullable|string|max:255',
            'responsible_signature'       => 'nullable|image|max:2048',
            'warehouse_manager_name'      => 'nullable|string|max:255',
            'warehouse_manager_signature' => 'nullable|image|max:2048',

            // Respuestas
            'answers'               => 'required|array|min:1',
            'answers.*.question_id' => 'required|integer|distinct|exists:inspection_question_equipment,id',
            'answers.*.answer'      => 'required|in:yes,no,na',
            'answers.*.observation' => 'nullable|string',
        ]);

        // Deben responderse TODAS las preguntas activas de ese equipo
        $esperadas = InspectionQuestionEquipment::where('equipment_id', $validado['equipment_id'])
            ->where('is_active', true)
            ->pluck('id')->sort()->values()->all();

        $enviadas = collect($validado['answers'])
            ->pluck('question_id')->map(fn ($id) => (int) $id)
            ->sort()->values()->all();

        if ($esperadas !== $enviadas) {
            return response()->json([
                'message' => 'Debes responder todas las preguntas activas del equipo.',
                'errors'  => ['answers' => ['Las respuestas no coinciden con las preguntas activas.']],
            ], 422);
        }

        $answers = $validado['answers'];
        unset($validado['answers']);

        $rutasGuardadas = [];

        try {
            $inspeccion = DB::transaction(function () use ($request, $validado, $answers, &$rutasGuardadas) {
                foreach (['responsible_signature', 'warehouse_manager_signature'] as $campo) {
                    if ($request->hasFile($campo)) {
                        $ruta = $request->file($campo)->store('signatures', 'public');
                        $validado[$campo] = $ruta;
                        $rutasGuardadas[] = $ruta;
                    }
                }

                $inspeccion = InspectionEquipment::create($validado);
                $inspeccion->answers()->createMany($answers);

                return $inspeccion;
            });
        } catch (\Throwable $e) {
            // Si falló la transacción, no dejar firmas huérfanas en disco
            Storage::disk('public')->delete($rutasGuardadas);
            throw $e;
        }

        return response()->json($inspeccion->load('answers'), 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $inspeccion = InspectionEquipment::with(['equipo', 'answers.question'])->findOrFail($id);

        return response()->json($inspeccion);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
