<?php

namespace App\Http\Controllers;

use App\Models\GasMeasurement;
use App\Models\GasMeasurementDetail;
use App\Models\Location;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\QueryException;
use Carbon\Carbon;

class GasMeasurementController extends Controller
{
    public function index()
    {
        $mediciones = GasMeasurement::select('id', 'measurement_date', 'measurement_time')->with('detail.location')->orderBy('measurement_date', 'desc')->get();

        return response()->json([
            'status' => 'success',
            'data' => $mediciones
        ], 200);
    }

    public function store(Request $request)
    {
        $request->validate([
            'measurement_time' => 'nullable|date_format:H:i',
        ], [
            'measurement_time.date_format' => 'La hora de ingreso debe tener el formato HH:MM.',
        ]);

        $today = Carbon::today()->toDateString();

        if (GasMeasurement::where('measurement_date', $today)->exists()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Ya existe una medición de gases registrada para el día de hoy.',
            ], 422);
        }

        $ubicacionesMina = Location::where('status', 'active')->whereHas('locationCategory', function ($query) {
            $query->where('name', 'Mina');
        })->get();

        if ($ubicacionesMina->isEmpty()) {
            return response()->json([
                'status' => 'error',
                'message' => 'No se encontraron ubicaciones activas para la medición de gases.'
            ], 422);
        }

        DB::beginTransaction();
        try {
            $medicion = GasMeasurement::create([
                'measurement_date' => $today,
                'measurement_time' => $request->input('measurement_time'),
            ]);

            foreach ($ubicacionesMina as $ubicacion) {
                GasMeasurementDetail::create([
                    'gas_measurement_id' => $medicion->id,
                    'location_id' => $ubicacion->id,
                ]);
            }

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Medición de gases registrada correctamente.',
                'data' => $medicion->load('detail.location')
            ], 201);

        } catch (QueryException $e) {
            DB::rollBack();
            return response()->json([
                'status' => 'error',
                'message' => 'No se pudo registrar la medición de gases.'
            ], 500);
        }
    }

    public function show($id)
    {
        $medicion = GasMeasurement::with([
            'detail' => fn ($query) => $query->orderBy('id'),
            'detail.location',
        ])->findOrFail($id);

        return response()->json([
            'status' => 'success',
            'data' => $medicion
        ], 200);
    }

    public function updateDetail(Request $request, $detailId)
    {
        $detalle = GasMeasurementDetail::findOrFail($detailId);

        $datosValidados = $request->validate([
            'oxygen' => 'required|numeric|decimal:0,2|min:0|max:100',
            'methane' => 'required|numeric|decimal:0,2|min:0|max:100',
            'carbon_dioxide' => 'required|numeric|decimal:0,2|min:0|max:100',
            'hydrogen_sulfide' => 'required|numeric|decimal:0,2|min:0|max:999',
            'carbon_monoxide' => 'required|numeric|decimal:0,2|min:0|max:999',
            'nitrogen_dioxide' => 'required|numeric|decimal:0,2|min:0|max:999',
            'responsible_name' => ['required', 'string', 'max:100', "regex:/^[\p{L}\s.'\-]+$/u"],
            'observation' => ['required', 'string', 'max:100', 'regex:/^[\p{L}\p{N}\s.,;:()\-\/%°#¿?¡!]+$/u'],
        ], [
            'oxygen.required' => 'El oxígeno es obligatorio.',
            'methane.required' => 'El metano es obligatorio.',
            'carbon_dioxide.required' => 'El dióxido de carbono es obligatorio.',
            'hydrogen_sulfide.required' => 'El ácido sulfhídrico es obligatorio.',
            'carbon_monoxide.required' => 'El monóxido de carbono es obligatorio.',
            'nitrogen_dioxide.required' => 'El dióxido de nitrógeno es obligatorio.',
            'responsible_name.required' => 'El responsable de la medición es obligatorio.',
            'observation.required' => 'Las observaciones son obligatorias.',
            'oxygen.numeric' => 'El valor de oxígeno debe ser un número.',
            'oxygen.decimal' => 'El valor admite máximo 2 decimales.',
            'methane.decimal' => 'El valor admite máximo 2 decimales.',
            'carbon_dioxide.decimal' => 'El valor admite máximo 2 decimales.',
            'hydrogen_sulfide.decimal' => 'El valor admite máximo 2 decimales.',
            'carbon_monoxide.decimal' => 'El valor admite máximo 2 decimales.',
            'nitrogen_dioxide.decimal' => 'El valor admite máximo 2 decimales.',
            'oxygen.min' => 'El oxígeno no puede ser menor a el 0%.',
            'oxygen.max' => 'El oxígeno no puede superar el 100%.',
            'methane.numeric' => 'El valor de metano debe ser un número.',
            'methane.min' => 'El metano no puede ser menor a el 0%.',
            'methane.max' => 'El metano no puede superar el 100%.',
            'carbon_dioxide.numeric' => 'El valor de dióxido de carbono debe ser numérico.',
            'carbon_dioxide.min' => 'El dióxido de carbono no puede ser menor a el 0%.',
            'carbon_dioxide.max' => 'El dióxido de carbono no puede superar el 100%.',
            'hydrogen_sulfide.numeric' => 'El valor del ácido sulfhídrico debe ser numérico.',
            'hydrogen_sulfide.min' => 'El ácido sulfhídrico no puede ser menor a 0.',
            'hydrogen_sulfide.max' => 'El ácido sulfhídrico no puede superar 999.',
            'carbon_monoxide.numeric' => 'El valor de monóxido de carbono debe ser numérico.',
            'carbon_monoxide.min' => 'El monóxido de carbono no puede ser menor a 0.',
            'carbon_monoxide.max' => 'El monóxido de carbono no puede superar 999.',
            'nitrogen_dioxide.numeric' => 'El valor de dióxido de nitrógeno debe ser numérico.',
            'nitrogen_dioxide.min' => 'El dióxido de nitrógeno no puede ser menor a 0.',
            'nitrogen_dioxide.max' => 'El dióxido de nitrógeno no puede superar 999.',
            'responsible_name.max' => 'El nombre del responsable no puede superar los 100 caracteres.',
            'responsible_name.regex' => 'El nombre del responsable solo puede contener letras, espacios, puntos, apóstrofes y guiones.',
            'observation.max' => 'La observación no puede superar los 100 caracteres.',
            'observation.regex' => 'La observación contiene caracteres no permitidos.',
        ]);

        $detalle->update($datosValidados);

        return response()->json([
            'status' => 'success',
            'message' => 'Medición de gases actualizada correctamente.',
            'data' => $detalle
        ], 200);
    }

    public function updateHeader(Request $request, $id)
    {
        $medicion = GasMeasurement::findOrFail($id);

        $datosValidados = $request->validate([
            'measurement_time' => 'nullable|date_format:H:i',
            'chief_signature' => ['nullable', 'string', 'max:3000000', 'regex:/^data:image\/(png|jpeg|webp);base64,/'],
            'sst_signature' => ['nullable', 'string', 'max:3000000', 'regex:/^data:image\/(png|jpeg|webp);base64,/'],
        ], [
            'measurement_time.date_format' => 'La hora de ingreso debe tener el formato HH:MM.',
            'chief_signature.regex' => 'La firma del jefe de mina debe ser una imagen PNG, JPG o WEBP.',
            'chief_signature.max' => 'La firma del jefe de mina es demasiado pesada (máx. 2 MB).',
            'sst_signature.regex' => 'La firma del líder SST debe ser una imagen PNG, JPG o WEBP.',
            'sst_signature.max' => 'La firma del líder SST es demasiado pesada (máx. 2 MB).',
        ]);

        try {
            $medicion->update($datosValidados);

            return response()->json([
                'status' => 'success',
                'message' => 'Datos de la medición actualizados correctamente.',
                'data' => $medicion
            ], 200);

        } catch (QueryException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'No se pudieron actualizar los datos de la medición.'
            ], 500);
        }
    }

    public function destroy($id)
    {
        try {
            $medicion = GasMeasurement::findOrFail($id);
            $medicion->delete();

            return response()->json([
                'status' => 'success',
                'message' => 'Medición de gases eliminada correctamente.'
            ], 200);

        } catch (QueryException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'No se pudo eliminar la medición de gases.'
            ], 500);
        }
    }
}