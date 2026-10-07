<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Location;
use App\Models\LocationCategory;
use Illuminate\Database\QueryException;

class LocationController extends Controller
{
    public function index()
    {
        $ubicaciones = Location::with(['locationCategory'])->get();
        $categorias = LocationCategory::all();
        return response()->json([
            'ubicaciones' => $ubicaciones,
            'categorias' => $categorias
        ], 200);
    }

    public function store(Request $request)
    {
        $datos = $request->validate([
            'name' => 'required|string|max:100',
            'description' => 'nullable|string',
            'location_category_id' => 'required|exists:location_category,id',
        ]);

        $datos['status'] = 'active';

        $location = Location::create($datos);

        return response()->json($location, 201);
    }
}
