<?php

namespace App\Http\Controllers;

use App\Models\InspectionAnswerMachine;
use App\Models\InspectionFormatMachine;
use App\Models\InspectionMachine;
use App\Models\Machine;
use App\Models\PreoperationalAnswerMachine;
use App\Models\PreoperationalMachine;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class MachineRecordController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(string $machineId, string $kind)
    {
        $conf = $this->conf($kind);
        $clave = $this->clave($conf);
        $modelo = $conf['model'];
        $maquina = Machine::findOrFail($machineId);

        $registros = $modelo::where('machine_id', $maquina->id)
            ->orderByDesc($clave)
            ->get()
            ->map(fn ($r) => [
                'id' => $r->id,
                'date' => $r->{$clave},
                'week_end' => $conf['daily'] ? $r->week_end : null,
                'inspector' => $r->inspector,
            ]);

        return response()->json(['machine' => $maquina, 'records' => $registros]);
    }

    private function conf(string $kind): array
    {
        return match ($kind) {
            'preoperational' => ['model' => PreoperationalMachine::class, 'daily' => true],
            'inspection' => ['model' => InspectionMachine::class, 'daily' => false],
            default => abort(404),
        };
    }

    private function clave(array $conf): string
    {
        return $conf['daily'] ? 'week_start' : 'inspection_date';
    }

    private function fallar(string $mensaje): never
    {
        throw ValidationException::withMessages(['answers' => [$mensaje]]);
    }

    public function period(Request $request, string $machineId, string $kind)
    {
        $conf = $this->conf($kind);
        $modelo = $conf['model'];
        $request->validate(['date' => 'required|date']);

        if ($conf['daily']) {
            $lunes = Carbon::parse($request->date)->startOfWeek(Carbon::MONDAY);
            $inicio = $lunes->toDateString();
            $fin = $lunes->copy()->addDays(6)->toDateString();
        } else {
            $inicio = $fin = Carbon::parse($request->date)->toDateString();
        }

        $registro = $modelo::with('answers')
            ->where('machine_id', $machineId)
            ->where($this->clave($conf), $inicio)
            ->first();

        return response()->json(['start' => $inicio, 'end' => $fin, 'record' => $registro]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request, string $kind)
    {
        $conf = $this->conf($kind);
        $modelo = $conf['model'];
        $clave = $this->clave($conf);

        // El contenido viaja como JSON dentro de "data"; la firma, como archivo
        if ($request->has('data')) {
            $request->merge(json_decode($request->input('data'), true) ?? []);
        }

        $reglas = [
            'machine_id' => 'required|exists:machine,id',
            $clave => 'required|date',
            'inspector' => 'required|string|max:255',
            'area' => 'nullable|string|max:255',
            'duration_minutes' => 'nullable|integer|min:0|max:65535',
            'reviewed_by' => 'nullable|string|max:255',
            'review_date' => 'nullable|date',
            'general_observations' => 'nullable|string',
            'answers' => 'nullable|array',
            'answers.*.question_id' => 'required|integer',
            'answers.*.value' => 'nullable|in:SI,NO,N/A',
            'signature_image' => 'required|image|mimes:png,jpg,jpeg|max:2048',
        ];
        if ($conf['daily']) {
            $reglas['answers.*.day'] = 'required|integer|between:1,7';
        } else {
            $reglas['answers.*.observation'] = 'nullable|string';
        }
        $v = $request->validate($reglas);

        // El formato sale de la categoría de la máquina
        $maquina = Machine::findOrFail($v['machine_id']);
        $formato = InspectionFormatMachine::with('sections.questions')
            ->where('category_id', $maquina->machine_category_id)
            ->where('is_active', true)
            ->first();

        if (!$formato) {
            $this->fallar('La categoría de esta máquina todavía no tiene formato.');
        }

        $activos = $formato->sections
            ->flatMap(fn ($s) => $s->questions)
            ->where('is_active', true)
            ->where('type', 'item')
            ->pluck('id')
            ->all();

        if ($conf['daily']) {
            $lunes = Carbon::parse($v['week_start'])->startOfWeek(Carbon::MONDAY);
            $inicio = $lunes->toDateString();
            $extra = ['week_end' => $lunes->copy()->addDays(6)->toDateString()];
        } else {
            $inicio = Carbon::parse($v['inspection_date'])->toDateString();
            $extra = [];
        }

        $filas = [];
        foreach ($v['answers'] ?? [] as $r) {
            if (!in_array($r['question_id'], $activos)) {
                $this->fallar('Hay una pregunta que no pertenece al formato de esta máquina.');
            }

            $valor = $r['value'] ?? null;
            $obs = $conf['daily'] ? null : ($r['observation'] ?? null);

            if ($valor === null && $obs === null) {
                continue;
            }

            $fila = ['question_id' => $r['question_id'], 'value' => $valor];
            if ($conf['daily']) {
                $fila['day'] = (int) $r['day'];
            } else {
                $fila['observation'] = $obs;
            }

            $filas[$r['question_id'] . '-' . ($fila['day'] ?? 0)] = $fila;
        }

        // La firma es obligatoria, salvo que el registro ya tenga una guardada
        $existente = $modelo::where('machine_id', $maquina->id)->where($clave, $inicio)->first();
        if (!$request->hasFile('signature_image') && empty($existente?->signature)) {
            $this->fallar('Adjunta la imagen de la firma.');
        }

        $aBorrar = [];

        $registro = DB::transaction(function () use ($modelo, $maquina, $formato, $clave, $inicio, $extra, $v, $request, $filas, $activos, &$aBorrar) {
            $registro = $modelo::updateOrCreate(
                ['machine_id' => $maquina->id, $clave => $inicio],
                array_merge($extra, [
                    'format_id' => $formato->id,
                    'inspector' => $v['inspector'],
                    'area' => $v['area'] ?? null,
                    'duration_minutes' => $v['duration_minutes'] ?? null,
                    'reviewed_by' => $v['reviewed_by'] ?? null,
                    'review_date' => $v['review_date'] ?? null,
                    'general_observations' => $v['general_observations'] ?? null,
                ])
            );

            if ($request->hasFile('signature_image')) {
                if ($registro->signature) {
                    $aBorrar[] = $registro->signature;
                }
                $registro->signature = $request->file('signature_image')->store('firmas', 'public');
                $registro->save();
            }

            $registro->answers()->whereIn('question_id', $activos)->delete();
            $registro->answers()->createMany(array_values($filas));

            return $registro;
        });

        Storage::disk('public')->delete($aBorrar);

        return response()->json($registro->load('answers'), $existente ? 200 : 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $kind, string $id)
    {
        $modelo = $this->conf($kind)['model'];

        $registro = $modelo::with([
            'answers',
            'machine',
            'format.category',
            'format.sections.questions',
        ])->findOrFail($id);

        return response()->json($registro);
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
