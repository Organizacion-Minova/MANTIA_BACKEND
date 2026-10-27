<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\InspectionQuestion;
use Illuminate\Validation\Rule;

class InspectionQuestionController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $request->validate([
            'machine_id' => 'required|exists:machine,id',
        ]);

        $preguntas = InspectionQuestion::where('machine_id', $request->machine_id)
            ->where('is_active', true)
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
            'machine_id' => 'required|exists:machine,id',
            'text' => [
                'required', 'string', 'max:255',
                Rule::unique('inspection_question', 'text')
                    ->where(fn ($q) => $q->where('machine_id', $request->machine_id)),
            ],
            'type' => 'required|in:yes_no_na',
        ]);

        $ultima = InspectionQuestion::where('machine_id', $validado['machine_id'])->max('position');
        $validado['position'] = ($ultima ?? 0) + 1;

        $pregunta = InspectionQuestion::create($validado);

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
