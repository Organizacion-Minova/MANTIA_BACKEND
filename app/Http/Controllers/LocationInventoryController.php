<?php

namespace App\Http\Controllers;

use App\Models\Location;
use App\Services\LocationInventoryService;
use Illuminate\Http\Request;


class LocationInventoryController extends Controller
{
    public function show(Request $request, LocationInventoryService $servicio, $id)
    {
        $ubicacion = Location::findOrFail($id);

        return response()->json($servicio->obtener($request->user(), $ubicacion));
    }
}