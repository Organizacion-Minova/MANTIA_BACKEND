<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\InspectionFormatEquipment;
use App\Models\Equipment;
use Illuminate\Support\Facades\DB;

class InspectionFormatEquipmentController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $formatos = InspectionFormatEquipment::with('equipment')
            ->withCount('sections')
            ->orderByDesc('id')
            ->get();

        return response()->json($formatos);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validado = $request->validate([
            'equipment_id' => 'required|exists:equipment,id',
            'code' => 'nullable|string|max:50',
            'version' => 'nullable|string|max:20',
            'valid_from' => 'nullable|date',
            'header_fields' => 'nullable|array',
            'header_fields.*.key' => 'required|string|max:50',
            'header_fields.*.label' => 'required|string|max:100',
            'signers' => 'nullable|array',
            'signers.*.key' => 'required|in:inspector,collaborator,reviewer',
            'signers.*.label' => 'required|string|max:100',
            'sections' => 'required|array|min:1',
            'sections.*.title' => 'required|string|max:255',
            'sections.*.mode' => 'required|in:daily,weekly',
            'sections.*.options' => 'required|array|min:2',
            'sections.*.options.*' => 'required|string|max:10',
            'sections.*.instructions' => 'nullable|string|max:255',
            'sections.*.has_observation' => 'required|boolean',
            'sections.*.questions' => 'required|array|min:1',
            'sections.*.questions.*' => 'required|string|max:255',
        ], [
            'equipment_id.required' => 'Selecciona un equipo.',
            'equipment_id.exists' => 'El equipo seleccionado no es válido.',
            'header_fields.*.key.required' => 'La clave del campo de encabezado es obligatoria.',
            'header_fields.*.label.required' => 'El nombre del campo de encabezado es obligatorio.',
            'signers.*.key.required' => 'El tipo de firmante es obligatorio.',
            'signers.*.label.required' => 'El nombre del firmante es obligatorio.',
            'sections.required' => 'Agrega al menos una sección al formato.',
            'sections.min' => 'Agrega al menos una sección al formato.',
            'sections.*.title.required' => 'El nombre de la sección es obligatorio.',
            'sections.*.mode.required' => 'Selecciona la frecuencia de la sección.',
            'sections.*.options.required' => 'Agrega las opciones de respuesta de la sección.',
            'sections.*.options.min' => 'Agrega al menos dos opciones de respuesta a la sección.',
            'sections.*.options.*.required' => 'El nombre de cada opción de respuesta es obligatorio.',
            'sections.*.has_observation.required' => 'Indica si la sección permite observaciones.',
            'sections.*.questions.required' => 'Agrega al menos una pregunta a la sección.',
            'sections.*.questions.min' => 'Agrega al menos una pregunta a la sección.',
            'sections.*.questions.*.required' => 'El nombre de la pregunta es obligatorio.',
        ], [
            'equipment_id' => 'equipo',
            'code' => 'código del formato',
            'version' => 'versión del formato',
            'valid_from' => 'fecha de vigencia',
            'header_fields' => 'campos del encabezado',
            'header_fields.*.key' => 'clave del campo de encabezado',
            'header_fields.*.label' => 'nombre del campo de encabezado',
            'signers' => 'firmantes',
            'signers.*.key' => 'tipo de firmante',
            'signers.*.label' => 'nombre del firmante',
            'sections' => 'secciones',
            'sections.*.title' => 'nombre de la sección',
            'sections.*.mode' => 'frecuencia de la sección',
            'sections.*.options' => 'opciones de respuesta',
            'sections.*.options.*' => 'nombre de la opción de respuesta',
            'sections.*.instructions' => 'instrucciones de la sección',
            'sections.*.has_observation' => 'permiso de observaciones',
            'sections.*.questions' => 'preguntas de la sección',
            'sections.*.questions.*' => 'nombre de la pregunta',
        ]);

        $yaTiene = InspectionFormatEquipment::where('equipment_id', $validado['equipment_id'])
            ->where('is_active', true)
            ->exists();

        if ($yaTiene) {
            return response()->json([
                'message' => 'Este equipo ya tiene un formato activo.',
                'errors' => ['equipment_id' => ['Este equipo ya tiene un formato activo.']],
            ], 422);
        }

        $formato = DB::transaction(function () use ($validado) {
            $formato = InspectionFormatEquipment    ::create([
                'equipment_id' => $validado['equipment_id'],
                'code' => $validado['code'] ?? null,
                'version' => $validado['version'] ?? null,
                'valid_from' => $validado['valid_from'] ?? null,
                'header_fields' => $validado['header_fields'] ?? null,
                'is_active' => true,
            ]);

            foreach ($validado['sections'] as $i => $s) {
                $seccion = $formato->sections()->create([
                    'title' => $s['title'],
                    'position' => $i + 1,
                    'mode' => $s['mode'],
                    'options' => $s['options'],
                    'instructions' => $s['instructions'] ?? null,
                    'has_observation' => $s['has_observation'],
                ]);

                foreach ($s['questions'] as $j => $texto) {
                    $seccion->questions()->create([
                        'text' => $texto,
                        'position' => $j + 1,
                        'is_active' => true,
                    ]);
                }
            }

            return $formato;
        });

        return response()->json($formato->load(['equipment', 'sections.questions']), 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $formato = InspectionFormatEquipment::with([
            'equipment.ubicacion',
            'sections.questions',
        ])->findOrFail($id);

        return response()->json($formato);
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

    public function byEquipment(string $equipmentId)
    {
        $equipo = Equipment::with('ubicacion')->findOrFail($equipmentId);

        $formato = InspectionFormatEquipment::with([
            'sections.questions' => fn ($q) => $q->where('is_active', true),
        ])
            ->where('equipment_id', $equipo->id)
            ->where('is_active', true)
            ->first();

        return response()->json([
            'equipment' => $equipo,
            'format' => $formato,
        ]);
    }
}
