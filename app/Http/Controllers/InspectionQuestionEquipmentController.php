<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\InspectionQuestionEquipment;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class InspectionQuestionEquipmentController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $request->validate([
            'equipment_id'     => 'required|exists:equipment,id',
            'include_inactive' => 'sometimes|boolean',
        ]);

        $preguntas = InspectionQuestionEquipment::where('equipment_id', $request->equipment_id)
            ->when(!$request->boolean('include_inactive'), fn ($q) => $q->where('is_active', true))
            ->orderBy('position')
            ->get();

        return response()->json($preguntas);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validado = $request->validate([
            'equipment_id' => 'required|exists:equipment,id',
            'text' => [
                'required', 'string', 'max:255',
                Rule::unique('inspection_question_equipment', 'text')
                    ->where(fn ($q) => $q->where('equipment_id', $request->equipment_id)),
            ],
            'type' => 'sometimes|in:yes_no_na',
        ]);

        $ultima = InspectionQuestionEquipment::where('equipment_id', $validado['equipment_id'])->max('position');
        $validado['position'] = ($ultima ?? 0) + 1;

        $pregunta = InspectionQuestionEquipment::create($validado);

        return response()->json($pregunta, 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {

    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
    public function reorder(Request $request)
    {
        $request->validate([
            'equipment_id' => 'required|exists:equipment,id',
            'ids'          => 'required|array',
            'ids.*'        => 'integer|exists:inspection_question_equipment,id',
        ]);

        DB::transaction(function () use ($request) {
            foreach ($request->ids as $i => $id) {
                InspectionQuestionEquipment::where('id', $id)
                    ->where('equipment_id', $request->equipment_id)
                    ->update(['position' => $i + 1]);
            }
        });

        return response()->json(['message' => 'Orden actualizado.']);
    }
}
