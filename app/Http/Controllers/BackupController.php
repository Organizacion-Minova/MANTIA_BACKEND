<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class BackupController extends Controller
{
    private const DISCO = 'local';
    private const CARPETA = 'backups';

    // Tablas que el administrador puede elegir respaldar, con su nombre en español.
    private const TABLAS_DISPONIBLES = [
        'company' => 'Empresas',
        'location' => 'Ubicaciones',
        'location_category' => 'Categorías de ubicación',
        'machine' => 'Máquinas',
        'machine_category' => 'Categorías de máquina',
        'equipment' => 'Equipos',
        'tool' => 'Herramientas',
        'category' => 'Categorías de herramientas',
        'inspection' => 'Inspecciones',
        'gas_measurement' => 'Mediciones de gases',
        'users' => 'Usuarios',
        'roles' => 'Roles',
    ];

    public function options()
    {
        $opciones = [];
        foreach (self::TABLAS_DISPONIBLES as $tabla => $etiqueta) {
            if (DB::getSchemaBuilder()->hasTable($tabla)) {
                $opciones[] = [
                    'tabla' => $tabla,
                    'etiqueta' => $etiqueta,
                    'registros' => DB::table($tabla)->count(),
                ];
            }
        }
        return response()->json(['opciones' => $opciones]);
    }

    public function index()
    {
        $backups = collect(Storage::disk(self::DISCO)->files(self::CARPETA))
            ->filter(fn ($ruta) => str_ends_with($ruta, '.meta.json'))
            ->map(fn ($ruta) => json_decode(Storage::disk(self::DISCO)->get($ruta), true))
            ->sortByDesc('creado_en')
            ->values();

        return response()->json(['backups' => $backups]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'tablas' => 'required|array|min:1',
            'tablas.*' => 'string|in:' . implode(',', array_keys(self::TABLAS_DISPONIBLES)),
        ]);

        $contenido = [];
        foreach ($request->tablas as $tabla) {
            $contenido[$tabla] = DB::table($tabla)->get();
        }

        $sello = now()->format('Y-m-d_His');
        $nombreArchivo = "backup_{$sello}.json";
        $rutaArchivo = self::CARPETA . '/' . $nombreArchivo;

        Storage::disk(self::DISCO)->put($rutaArchivo, json_encode($contenido, JSON_PRETTY_PRINT));

        $meta = [
            'archivo' => $nombreArchivo,
            'tablas' => $request->tablas,
            'creado_en' => now()->toDateTimeString(),
            'creado_por' => $request->user()->name,
            'peso_kb' => round(Storage::disk(self::DISCO)->size($rutaArchivo) / 1024, 1),
        ];

        Storage::disk(self::DISCO)->put(self::CARPETA . "/{$nombreArchivo}.meta.json", json_encode($meta, JSON_PRETTY_PRINT));

        return response()->json(['message' => 'Backup generado correctamente.', 'backup' => $meta], 201);
    }

    public function download($archivo)
    {
        $nombreSeguro = basename($archivo); // evita que alguien pida salir de la carpeta de backups
        $ruta = self::CARPETA . '/' . $nombreSeguro;

        if (! Storage::disk(self::DISCO)->exists($ruta)) {
            return response()->json(['message' => 'El backup no existe.'], 404);
        }

        return Storage::disk(self::DISCO)->download($ruta);
    }

    public function destroy($archivo)
    {
        $nombreSeguro = basename($archivo);
        $ruta = self::CARPETA . '/' . $nombreSeguro;

        if (! Storage::disk(self::DISCO)->exists($ruta)) {
            return response()->json(['message' => 'El backup no existe.'], 404);
        }

        Storage::disk(self::DISCO)->delete([$ruta, $ruta . '.meta.json']);

        return response()->json(['message' => 'Backup eliminado correctamente.']);
    }
}