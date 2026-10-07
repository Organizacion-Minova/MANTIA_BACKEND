<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\Machine;
use Illuminate\Http\Request;

class MachineController extends Controller
{
    public function index()
    {
        return Machine::with(['location', 'machineCategory'])->latest()->get();
    }

    public function store(Request $request)
    {
        $datos = $request->validate([
            'code' => 'required|string|max:20|unique:machine,code',
            'name' => 'required|string|max:100',
            'model' => 'nullable|string|max:80',
            'serial_number' => 'nullable|string|max:100',
            'acquisition_date' => 'nullable|date',
            'acquisition_cost' => 'nullable|numeric',
            'status' => 'required|in:active,inactive,maintenance',
            'warranty' => 'nullable|integer',
            'location_id' => 'required|exists:location,id',
            'machine_category_id' => 'required|exists:machine_category,id',
            'responsible' => 'required|string|max:45',
            'machine_usage' => 'required|string|max:45',
            'in_operation' => 'required|boolean',
            'characteristics' => 'required|string',
        ]);

        $datos['company_id'] = Company::query()->value('tax_id');

        $machine = Machine::create($datos);

        return response()->json($machine, 201);
    }
}