<?php

namespace App\Http\Controllers;

use App\Models\Location;
use Illuminate\Http\Request;

class LocationController extends Controller
{
    public function index()
    {
        return Location::with('locationCategory')->orderBy('name')->get();
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