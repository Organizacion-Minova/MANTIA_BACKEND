<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Equipment;
class EquipmentController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $equipments = Equipment::with('ubicacion', 'company')
            ->get()
            ->makeHidden('image');

        return response()->json($equipments);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validado = $request->validate([
            'code' => 'required|string|max:50|unique:equipment,code',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'serial_number' => 'nullable|string|max:100',
            'responsible' => 'nullable|string|max:255',
            'status' => 'required|string|in:active,inactive',
            'registration_date' => 'required|date',
            'company_id' => 'required|exists:company,id',
            'location_id' => 'required|exists:location,id',
            'image' => 'nullable|image|max:2048', // 2048 KB = 2 MB
            'model' => 'nullable|string|max:100',
        ]);

        if ($request->hasFile('image')) {
            $validado['image'] = $request->file('image')->store('equipos', 'public');
        }

        $equipo = Equipment::create($validado);

        return response()->json($equipo, 201);
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
