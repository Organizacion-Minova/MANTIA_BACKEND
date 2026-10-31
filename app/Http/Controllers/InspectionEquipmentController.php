<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Equipment;
use App\Models\InspectionEquipment;
use App\Models\InspectionFormatEquipment;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class InspectionEquipmentController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function indexByEquipment(string $equipmentId)
    {
        $equipo = Equipment::findOrFail($equipmentId);

        $inspecciones = InspectionEquipment::whereHas('format', fn ($q) => $q->where('equipment_id', $equipo->id))
            ->with(['format', 'signatures' => fn ($q) => $q->where('role', 'inspector')])
            ->orderByDesc('week_start')
            ->get()
            ->map(fn ($i) => [
                'id' => $i->id,
                'week_start' => $i->week_start,
                'week_end' => $i->week_end,
                'inspector' => $i->signatures->first()?->name,
                'format_version' => $i->format?->version,
            ]);

        return response()->json(['equipment' => $equipo, 'inspections' => $inspecciones]);
    }

    public function week(Request $request, string $formatId)
    {
        $request->validate(['week_start' => 'required|date']);

        $lunes = Carbon::parse($request->week_start)->startOfWeek(Carbon::MONDAY);

        $inspeccion = InspectionEquipment::with(['answers', 'signatures'])
            ->where('format_id', $formatId)
            ->where('week_start', $lunes->toDateString())
            ->first();

        return response()->json([
            'week_start' => $lunes->toDateString(),
            'week_end' => $lunes->copy()->addDays(6)->toDateString(),
            'inspection' => $inspeccion,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $v = $request->validate([
            'format_id' => 'required|exists:inspection_format_equipment,id',
            'week_start' => 'required|date',
            'header_data' => 'nullable|array',
            'header_data.*' => 'nullable|string|max:255',
            'general_observations' => 'nullable|string',
            'answers' => 'nullable|array',
            'answers.*.question_id' => 'required|integer',
            'answers.*.day' => 'required|integer|between:0,7',
            'answers.*.value' => 'nullable|string|max:10',
            'answers.*.observation' => 'nullable|string',
            'signatures' => 'required|array|min:1',
            'signatures.*.role' => 'required|in:inspector,collaborator,reviewer',
            'signatures.*.name' => 'nullable|string|max:255',
            'signatures.*.id_number' => 'nullable|string|max:30',
        ]);

        $formato = InspectionFormatEquipment::with('sections.questions')->findOrFail($v['format_id']);

        // pregunta => su sección (para validar opciones y modo)
        $secciones = [];
        foreach ($formato->sections as $seccion) {
            foreach ($seccion->questions as $pregunta) {
                $secciones[$pregunta->id] = $seccion;
            }
        }

        $falla = fn (string $campo, string $mensaje) =>
            throw ValidationException::withMessages([$campo => [$mensaje]]);

        $filas = [];
        foreach ($v['answers'] ?? [] as $r) {
            $seccion = $secciones[$r['question_id']] ?? null;
            if (!$seccion) {
                $falla('answers', 'Hay una pregunta que no pertenece a este formato.');
            }

            $valor = $r['value'] ?? null;
            $obs = $r['observation'] ?? null;
            $dia = (int) $r['day'];

            if ($valor !== null) {
                if (!in_array($valor, $seccion->options, true)) {
                    $falla('answers', "La respuesta \"{$valor}\" no es válida en la sección \"{$seccion->title}\".");
                }
                $diaValido = $seccion->mode === 'weekly' ? $dia === 0 : ($dia >= 1 && $dia <= 7);
                if (!$diaValido) {
                    $falla('answers', "Día no válido en la sección \"{$seccion->title}\".");
                }
            }

            if ($obs !== null && !$seccion->has_observation) {
                $falla('answers', "La sección \"{$seccion->title}\" no admite observaciones por pregunta.");
            }

            if ($valor === null && $obs === null) {
                continue;
            }

            $filas["{$r['question_id']}-{$dia}"] = [
                'question_id' => $r['question_id'],
                'day' => $dia,
                'value' => $valor,
                'observation' => $obs,
            ];
        }

        $firmas = collect($v['signatures'])->unique('role')->values();
        $inspector = $firmas->firstWhere('role', 'inspector');
        if (!$inspector || empty($inspector['name'])) {
            $falla('signatures', 'Escribe el nombre de quien realiza la inspección.');
        }

        $lunes = Carbon::parse($v['week_start'])->startOfWeek(Carbon::MONDAY);

        $inspeccion = DB::transaction(function () use ($v, $lunes, $filas, $firmas) {
            $inspeccion = InspectionEquipment::updateOrCreate(
                ['format_id' => $v['format_id'], 'week_start' => $lunes->toDateString()],
                [
                    'week_end' => $lunes->copy()->addDays(6)->toDateString(),
                    'header_data' => $v['header_data'] ?? null,
                    'general_observations' => $v['general_observations'] ?? null,
                ]
            );

            // El frontend siempre envía la cuadrícula completa: se reemplaza todo
            $inspeccion->answers()->delete();
            $inspeccion->answers()->createMany(array_values($filas));
            $inspeccion->signatures()->delete();
            $inspeccion->signatures()->createMany($firmas->all());

            return $inspeccion;
        });

        return response()->json(
            $inspeccion->load(['answers', 'signatures']),
            $inspeccion->wasRecentlyCreated ? 201 : 200
        );
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $inspeccion = InspectionEquipment::with([
            'answers',
            'signatures',
            'format.equipment.ubicacion',
            'format.sections.questions',
        ])->findOrFail($id);

        return response()->json($inspeccion);
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
