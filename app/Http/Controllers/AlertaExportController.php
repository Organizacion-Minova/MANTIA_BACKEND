<?php

namespace App\Http\Controllers;

use App\Exports\AlertaExcelExport;
use App\Services\AlertaExportService;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Facades\Excel;

class AlertaExportController extends Controller
{
    private const MAX_DIAS = 31;

    protected $service;

    public function __construct(AlertaExportService $service)
    {
        $this->service = $service;
    }

    public function alertasExcel(Request $request)
    {
        $datos = $this->prepararDatos($request);
        if ($datos instanceof JsonResponse) {
            return $datos;
        }

        try {
            return Excel::download(
                new AlertaExcelExport($request->user(), $datos['alertas'], $datos['fecha_inicio'], $datos['fecha_fin'], $datos['nivel']),
                "Alertas_{$datos['fecha_inicio']}_al_{$datos['fecha_fin']}.xlsx"
            );
        } catch (\Exception $e) {
            return response()->json(['error' => 'Error al generar Excel de alertas', 'message' => $e->getMessage()], 500);
        }
    }

    public function alertasPdf(Request $request)
    {
        $datos = $this->prepararDatos($request);
        if ($datos instanceof JsonResponse) {
            return $datos;
        }

        try {
            $pdf = Pdf::loadView('exports.AlertaPdf', [
                'usuario' => $request->user(),
                'alertas' => $datos['alertas'],
                'fechaInicio' => $datos['fecha_inicio'],
                'fechaFin' => $datos['fecha_fin'],
                'nivel' => $datos['nivel'],
            ])->setPaper('letter', 'landscape');

            return $pdf->download("Alertas_{$datos['fecha_inicio']}_al_{$datos['fecha_fin']}.pdf");
        } catch (\Exception $e) {
            return response()->json(['error' => 'Error al generar PDF de alertas', 'message' => $e->getMessage()], 500);
        }
    }

    private function prepararDatos(Request $request): array|JsonResponse
    {
        $request->validate([
            'fecha_inicio' => 'required|date',
            'fecha_fin' => 'required|date|after_or_equal:fecha_inicio',
            'nivel' => ['nullable', Rule::in(array_keys(AlertaExportService::NIVELES))],
        ], [
            'fecha_inicio.required' => 'Debe seleccionar la fecha de inicio.',
            'fecha_fin.required' => 'Debe seleccionar la fecha final.',
            'fecha_inicio.date' => 'La fecha inicio no es válida.',
            'fecha_fin.date' => 'La fecha fin no es válida.',
            'fecha_fin.after_or_equal' => 'La fecha fin no puede ser antes de la fecha de inicio.',
            'nivel.in' => 'El nivel seleccionado no es válido.',
        ]);

        $fechaInicio = $request->query('fecha_inicio');
        $fechaFin = $request->query('fecha_fin');
        $nivel = $request->query('nivel') ?: null;

        $dias = Carbon::parse($fechaInicio)->diffInDays(Carbon::parse($fechaFin)) + 1;
        if ($dias > self::MAX_DIAS) {
            return response()->json(['message' => 'El rango máximo permitido es de ' . self::MAX_DIAS . ' días.'], 422);
        }

        $alertas = $this->service->obtenerDatosRango($request->user(), $fechaInicio, $fechaFin, $nivel);

        if ($alertas->isEmpty()) {
            return response()->json(['message' => 'No hay alertas en el rango seleccionado.'], 404);
        }

        $limite = (int) config('alertas.limite_exportacion', 2000);
        if ($alertas->count() > $limite) {
            return response()->json([
                'message' => "Hay más de {$limite} alertas en el rango seleccionado. Reduzca el rango de fechas o filtre por nivel.",
            ], 422);
        }

        return [
            'alertas' => $alertas,
            'fecha_inicio' => $fechaInicio,
            'fecha_fin' => $fechaFin,
            'nivel' => $nivel,
        ];
    }
}