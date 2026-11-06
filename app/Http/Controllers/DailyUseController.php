<?php

namespace App\Http\Controllers;

use App\Models\DailyUse;
use App\Rules\RolResponsable;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class DailyUseController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $dailyuse = DailyUse::with('machine')->get();
        return response()->json($dailyuse);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->merge(['date' => now()->toDateString()]);
        $validado = $request->validate([
            'date' => [
                'required',
                'date',
                Rule::unique('daily_use')->where(
                    fn ($query) => $query->where('machine_id', $request->input('machine_id'))
                ),
            ],
            'operating_status' => 'required|string|max:100',
            'operation_start' => 'required|date_format:H:i:s',
            'operation_end' => 'required|date_format:H:i:s',
            'responsible' => ['required', 'string', 'max:100', new RolResponsable()],
            'observations' => 'nullable|string|max:255',
            'machine_id' => 'required|exists:machine,id',
        ], [
            'date.required' => 'La fecha es obligatoria.',
            'date.date' => 'La fecha no es válida.',
            'date.unique' => 'Ya existe un registro para esta máquina en la fecha de hoy.',
            'operating_status.required' => 'El estado de funcionamiento es obligatorio.',
            'operating_status.string' => 'El estado de funcionamiento debe ser texto.',
            'operating_status.max' => 'El estado de funcionamiento no puede superar los 100 caracteres.',
            'operation_start.required' => 'La hora de inicio de operación es obligatoria.',
            'operation_start.date_format' => 'La hora de inicio de operación no es válida.',
            'operation_end.required' => 'La hora de finalización de operación es obligatoria.',
            'operation_end.date_format' => 'La hora de finalización de operación no es válida.',
            'responsible.required' => 'El responsable es obligatorio.',
            'responsible.string' => 'El responsable debe ser texto.',
            'responsible.max' => 'El responsable no puede superar los 100 caracteres.',
            'observations.string' => 'Las observaciones deben ser texto.',
            'observations.max' => 'Las observaciones no pueden superar los 255 caracteres.',
            'machine_id.required' => 'La máquina es obligatoria.',
            'machine_id.exists' => 'La máquina seleccionada no existe.',
        ]);
        $dailyuse = DailyUse::create($validado);
        return response()->json($dailyuse->load(['machine']), 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {

    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $actual = DailyUse::whereKey($id)->value('responsible');
        $request->merge(['date' => now()->toDateString()]);
        $validado = $request->validate([
            'date' => [
                'required',
                'date',
                Rule::unique('daily_use')->ignore($id)->where(
                    fn ($query) => $query->where('machine_id', $request->input('machine_id'))
                ),
            ],
            'operating_status' => 'required|string|max:100',
            'operation_start' => 'required|date_format:H:i:s',
            'operation_end' => 'required|date_format:H:i:s|after_or_equal:operation_start',
            'responsible' => ['required', 'string', 'max:100', new RolResponsable($actual)],
            'observations' => 'nullable|string|max:255',
            'machine_id' => 'required|exists:machine,id',
        ], [
            'date.required' => 'La fecha es obligatoria.',
            'date.date' => 'La fecha no es válida.',
            'date.unique' => 'Ya existe un registro para esta máquina en la fecha de hoy.',
            'operating_status.required' => 'El estado de funcionamiento es obligatorio.',
            'operating_status.string' => 'El estado de funcionamiento debe ser texto.',
            'operating_status.max' => 'El estado de funcionamiento no puede superar los 100 caracteres.',
            'operation_start.required' => 'La hora de inicio de operación es obligatoria.',
            'operation_start.date_format' => 'La hora de inicio de operación no es válida.',
            'operation_end.required' => 'La hora de finalización de operación es obligatoria.',
            'operation_end.date_format' => 'La hora de finalización de operación no es válida.',
            'operation_end.after_or_equal' => 'La hora fin no puede ser antes de la hora de inicio.',
            'responsible.required' => 'El responsable es obligatorio.',
            'responsible.string' => 'El responsable debe ser texto.',
            'responsible.max' => 'El responsable no puede superar los 100 caracteres.',
            'observations.string' => 'Las observaciones deben ser texto.',
            'observations.max' => 'Las observaciones no pueden superar los 255 caracteres.',
            'machine_id.required' => 'La máquina es obligatoria.',
            'machine_id.exists' => 'La máquina seleccionada no existe.',
        ]);
        $dailyuse = DailyUse::findOrFail($id);
        $dailyuse->update($validado);
        return response()->json($dailyuse->load(['machine']));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $dailyuse=DailyUse::findOrFail($id);
        $dailyuse->delete();
        return response()->json(['message'=>'Eliminar resgistro']);
    }
}
