<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\InspectionFormatEquipment;
use App\Models\Equipment;
use App\Models\MachineCategory;
use App\Models\Machine;
use App\Models\InspectionFormatMachine;

class InspectionOverviewController extends Controller
{
    public function equipment()
    {
        $conFormato = InspectionFormatEquipment::where('is_active', true)->pluck('equipment_id')->all();

        $equipos = Equipment::select('id', 'code', 'name')
            ->orderBy('name')
            ->get()
            ->map(fn ($e) => [
                'id' => $e->id,
                'code' => $e->code,
                'name' => $e->name,
                'has_format' => in_array($e->id, $conFormato),
            ]);

        return response()->json($equipos);
    }
    public function machines()
    {
        $categoriasConFormato = InspectionFormatMachine::where('is_active', true)->pluck('category_id')->all();
        $categorias = MachineCategory::pluck('name', 'id');

        $maquinas = Machine::select('id', 'name', 'machine_category_id')
            ->orderBy('name')
            ->get()
            ->map(fn ($m) => [
                'id' => $m->id,
                'name' => $m->name,
                'category' => $categorias[$m->machine_category_id] ?? null,
                'has_format' => in_array($m->machine_category_id, $categoriasConFormato),
            ]);

        return response()->json($maquinas);
    }
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
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
