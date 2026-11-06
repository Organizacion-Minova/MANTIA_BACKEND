<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Equipment;
use App\Models\InspectionEquipment;
use App\Models\InspectionFormatEquipment;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
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
        if ($request->has('data')) {
            $contenido = $request->input('data');
            $datos = is_string($contenido) ? json_decode($contenido, true) : $contenido;
            if (!is_array($datos)) {
                throw ValidationException::withMessages([
                    'data' => ['Los datos de la inspección no tienen un formato válido.'],
                ]);
            }

            $request->merge($datos);
        }

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
            'signatures.*.name' => 'required|nullable|string|max:255',
            'signatures.*.id_number' => 'required|nullable|string|max:30',
            'signature_images' => 'required|nullable|array',
            'signature_images.*' => 'required|image|mimes:png,jpg,jpeg|max:2048',
        ], [
            'format_id.required' => 'El código es obligatorio.',
            'format_id.exists' => 'El código no existe.',
            'week_start.required' => 'La fecha es obligatoria.',
            'week_start.date' => 'La fecha no es válida.',
            'header_data.*.string' => 'Cada dato de la cabecera debe ser un texto.',
            'header_data.*.max' => 'Los textos de la cabecera no deben superar los 255 caracteres.',
            'general_observations.string' => 'Las observaciones generales deben ser un texto.',
            'answers.*.question_id.required' => 'El código de la pregunta es obligatorio.',
            'answers.*.question_id.integer' => 'El código de la pregunta debe ser un número entero.',
            'answers.*.day.required' => 'El día es obligatorio.',
            'answers.*.day.integer' => 'El día debe ser un número entero.',
            'answers.*.day.between' => 'El día debe estar entre 0 y 7.',
            'answers.*.value.max' => 'El valor de la respuesta no debe superar los 10 caracteres.',
            'answers.*.observation.string' => 'La observación de la respuesta debe ser un texto.',
            'signatures.required' => 'Las firmas son obligatorias.',
            'signatures.min' => 'Debe incluir al menos una firma.',
            'signatures.*.role.required' => 'El rol de la firma es obligatorio.',
            'signatures.*.role.in' => 'El rol seleccionado no es válido (debe ser inspector, colaborador o revisor).',
            'signatures.*.name.required' => 'El nombre de la firma es obligatorio.',
            'signatures.*.name.string' => 'El nombre de la firma debe ser un texto.',
            'signatures.*.name.max' => 'El nombre de la firma no debe superar los 255 caracteres.',
            'signatures.*.id_number.required' => 'El número de identificación es obligatorio.',
            'signatures.*.id_number.string' => 'El número de identificación debe ser un texto.',
            'signatures.*.id_number.max' => 'El número de identificación no debe superar los 30 caracteres.',
            'signature_images.required' => 'Las imágenes de las firmas son obligatorias.',
            'signature_images.*.required' => 'La imagen de la firma es obligatoria.',
            'signature_images.*.image' => 'El archivo debe ser una imagen.',
            'signature_images.*.mimes' => 'La imagen no es válida.',
            'signature_images.*.max' => 'La imagen es demasiado pesada (máx. 2 MB).',
        ]);

        $formato = InspectionFormatEquipment::with('sections.questions')->findOrFail($v['format_id']);

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

        $firmas = collect($v['signatures'])->unique('role');
        $inspector = $firmas->firstWhere('role', 'inspector');
        if (!$inspector || empty($inspector['name'])) {
            $falla('signatures', 'Escribe el nombre de quien realiza la inspección.');
        }

        $lunes = Carbon::parse($v['week_start'])->startOfWeek(Carbon::MONDAY);

        $imagenes = $request->file('signature_images', []);
        $imagenesPorRol = [];
        $imagenesNuevas = [];
        try {
            foreach ($firmas as $indice => $firma) {
                $imagen = $imagenes[$firma['role']] ?? $imagenes[$indice] ?? null;
                if (!$imagen) {
                    continue;
                }

                $ruta = $imagen->store('inspection-signatures', 'public');
                if (!$ruta) {
                    foreach ($imagenesNuevas as $rutaNueva) {
                        Storage::disk('public')->delete($rutaNueva);
                    }

                    return response()->json([
                        'message' => 'No fue posible guardar la imagen de la firma.',
                    ], 500);
                }

                $imagenesNuevas[] = $ruta;
                $imagenesPorRol[$firma['role']] = $ruta;
            }

            [$inspeccion, $firmasAnteriores] = DB::transaction(function () use ($v, $lunes, $filas, $firmas, $imagenesPorRol) {
                $inspeccion = InspectionEquipment::updateOrCreate(
                    ['format_id' => $v['format_id'], 'week_start' => $lunes->toDateString()],
                    [
                        'week_end' => $lunes->copy()->addDays(6)->toDateString(),
                        'header_data' => $v['header_data'] ?? null,
                        'general_observations' => $v['general_observations'] ?? null,
                    ]
                );

                $firmasAnteriores = $inspeccion->signatures()->get()->keyBy('role');

                // El frontend siempre envía la cuadrícula completa: se reemplaza todo
                $inspeccion->answers()->delete();
                $inspeccion->answers()->createMany(array_values($filas));
                $inspeccion->signatures()->delete();

                $datosFirmas = $firmas->map(function ($firma) use ($imagenesPorRol, $firmasAnteriores) {
                    $firma['signature'] = $imagenesPorRol[$firma['role']]
                        ?? $firmasAnteriores->get($firma['role'])?->signature;

                    return $firma;
                });
                $inspeccion->signatures()->createMany($datosFirmas->all());

                return [$inspeccion, $firmasAnteriores];
            });
        } catch (\Throwable $e) {
            foreach ($imagenesNuevas as $rutaNueva) {
                Storage::disk('public')->delete($rutaNueva);
            }

            throw $e;
        }

        foreach ($firmasAnteriores as $firmaAnterior) {
            $rutaAnterior = $firmaAnterior->signature;
            if (
                $rutaAnterior &&
                str_starts_with($rutaAnterior, 'inspection-signatures/') &&
                isset($imagenesPorRol[$firmaAnterior->role]) &&
                $rutaAnterior !== $imagenesPorRol[$firmaAnterior->role]
            ) {
                Storage::disk('public')->delete($rutaAnterior);
            }
        }

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
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
