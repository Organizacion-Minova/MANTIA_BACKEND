<?php

namespace App\Http\Controllers;

use App\Models\InspectionAnswerMachine;
use App\Models\InspectionFormatMachine;
use App\Models\Machine;
use App\Models\MachineCategory;
use App\Models\PreoperationalAnswerMachine;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class InspectionFormatMachineController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    
    public function index()
    {
        //
    }

    private function fallar(string $mensaje): never
    {
        throw ValidationException::withMessages(['sections' => [$mensaje]]);
    }

    private function validar(Request $request, bool $creando): array
    {
        $v = $request->validate([
            'category_id' => $creando ? 'required|exists:machine_category,id' : 'nullable',
            'code' => 'nullable|string|max:50',
            'version' => 'nullable|string|max:20',
            'valid_from' => 'nullable|date',
            'sections' => 'required|array|min:1',
            'sections.*.id' => 'nullable|integer',
            'sections.*.title' => 'required|string|max:255',
            'sections.*.questions' => 'required|array|min:1',
            'sections.*.questions.*.id' => 'nullable|integer',
            'sections.*.questions.*.type' => 'required|in:group,item',
            'sections.*.questions.*.text' => 'required|string|max:255',
        ]);

        foreach ($v['sections'] as $s) {
            if (!collect($s['questions'])->contains('type', 'item')) {
                $this->fallar("La sección \"{$s['title']}\" necesita al menos una pregunta.");
            }
        }

        return $v;
    }

    public function byMachine(string $machineId)
    {
        $maquina = Machine::findOrFail($machineId);

        $formato = InspectionFormatMachine::with([
            'sections.questions' => fn ($q) => $q->where('is_active', true),
        ])
            ->where('category_id', $maquina->machine_category_id)
            ->where('is_active', true)
            ->first();

        return response()->json([
            'machine' => $maquina,
            'category' => MachineCategory::find($maquina->machine_category_id),
            'format' => $formato,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $v = $this->validar($request, true);

        $yaTiene = InspectionFormatMachine::where('category_id', $v['category_id'])
            ->where('is_active', true)
            ->exists();

        if ($yaTiene) {
            throw ValidationException::withMessages([
                'category_id' => ['Esta categoría ya tiene un formato activo.'],
            ]);
        }

        $formato = DB::transaction(function () use ($v) {
            $formato = InspectionFormatMachine::create([
                'category_id' => $v['category_id'],
                'code' => $v['code'] ?? null,
                'version' => $v['version'] ?? null,
                'valid_from' => $v['valid_from'] ?? null,
                'is_active' => true,
            ]);

            foreach ($v['sections'] as $i => $s) {
                $seccion = $formato->sections()->create(['title' => $s['title'], 'position' => $i + 1]);

                foreach ($s['questions'] as $j => $q) {
                    $seccion->questions()->create([
                        'type' => $q['type'],
                        'text' => $q['text'],
                        'position' => $j + 1,
                        'is_active' => true,
                    ]);
                }
            }

            return $formato;
        });

        return response()->json($formato->load('sections.questions'), 201);
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
        $formato = InspectionFormatMachine::with('sections.questions')->findOrFail($id);
        $v = $this->validar($request, false);

        // Preguntas que ya tienen respuestas, en cualquiera de los dos registros
        $idsPreguntas = $formato->sections->flatMap(fn ($s) => $s->questions->pluck('id'));
        $respondidas = array_unique(array_merge(
            PreoperationalAnswerMachine::whereIn('question_id', $idsPreguntas)->distinct()->pluck('question_id')->all(),
            InspectionAnswerMachine::whereIn('question_id', $idsPreguntas)->distinct()->pluck('question_id')->all(),
        ));

        DB::transaction(function () use ($formato, $v, $respondidas) {
            $formato->update([
                'code' => $v['code'] ?? null,
                'version' => $v['version'] ?? null,
                'valid_from' => $v['valid_from'] ?? null,
            ]);

            $actuales = $formato->sections->keyBy('id');
            $conservadas = [];

            foreach ($v['sections'] as $i => $s) {
                $seccion = isset($s['id']) ? $actuales->get($s['id']) : null;

                if (isset($s['id']) && !$seccion) {
                    $this->fallar('Hay una sección que no pertenece a este formato.');
                }

                $datos = ['title' => $s['title'], 'position' => $i + 1];
                if ($seccion) {
                    $seccion->update($datos);
                } else {
                    $seccion = $formato->sections()->create($datos);
                }
                $conservadas[] = $seccion->id;

                $actualesQ = $seccion->questions->keyBy('id');
                $enviadas = [];

                foreach ($s['questions'] as $j => $q) {
                    $pregunta = isset($q['id']) ? $actualesQ->get($q['id']) : null;

                    if (isset($q['id']) && !$pregunta) {
                        $this->fallar('Hay una pregunta que no pertenece a esta sección.');
                    }
                    if ($pregunta && in_array($pregunta->id, $respondidas) && $q['type'] !== 'item') {
                        $this->fallar('Una pregunta que ya tiene respuestas no puede convertirse en grupo.');
                    }

                    $datosPregunta = [
                        'type' => $q['type'],
                        'text' => $q['text'],
                        'position' => $j + 1,
                        'is_active' => true,
                    ];

                    if ($pregunta) {
                        $pregunta->update($datosPregunta);
                    } else {
                        $pregunta = $seccion->questions()->create($datosPregunta);
                    }
                    $enviadas[] = $pregunta->id;
                }

                // Las que ya no vienen: se desactivan si tienen respuestas, o se borran
                $siguiente = count($s['questions']);
                foreach ($actualesQ as $pregunta) {
                    if (in_array($pregunta->id, $enviadas)) {
                        continue;
                    }
                    if (in_array($pregunta->id, $respondidas)) {
                        $siguiente++;
                        $pregunta->update(['is_active' => false, 'position' => $siguiente]);
                    } else {
                        $pregunta->delete();
                    }
                }
            }

            foreach ($actuales as $seccion) {
                if (in_array($seccion->id, $conservadas)) {
                    continue;
                }
                if ($seccion->questions->contains(fn ($q) => in_array($q->id, $respondidas))) {
                    $this->fallar("No se puede eliminar la sección \"{$seccion->title}\" porque tiene respuestas guardadas.");
                }
                $seccion->delete();
            }
        });

        return response()->json($formato->fresh('sections.questions'));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
