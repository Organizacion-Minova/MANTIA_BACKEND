<?php

namespace Database\Seeders;

use App\Models\InspectionFormatMachine;
use App\Models\MachineCategory;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class InspectionFormatMachineSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // ---------------------------------------------------------------- MALACATE
        $this->crearFormato('%malacate%', [
            $this->seccion('Verificación del estado de la máquina', [
                ...$this->grupo('¿Se encuentra en buen estado la base del malacate?', [
                    'Tornillería y tuercas',
                    'Revisión del chasis',
                ]),
                ...$this->grupo('¿Se encuentra en buen estado el kit de arrastre del malacate?', [
                    'Limpieza y lubricación de guaya',
                    'Revisión del tornillo de contacto',
                    'Tornillería',
                    'Tambor',
                    'Protector de tambor',
                    'Rodamientos lubricados',
                ]),
                ...$this->motor(),
                ...$this->grupo('¿Se encuentra en buen estado el compresor?', [
                    'Niveles de aceite del compresor',
                    'Mangueras',
                    'Racores',
                    'Válvula de presión',
                    'Empaques',
                    'Tanque del compresor',
                    'Registros de apertura y cierre',
                ]),
                ...$this->grupo('¿El sistema de frenos se encuentra en buen estado?', [
                    'Revisión del freno de aire',
                    'Revisión del freno de cinta',
                    'Revisión del freno de campana',
                ]),
                ...$this->grupo('¿El sistema eléctrico y automatización del sistema eléctrico se encuentra en buen estado?', [
                    'Mandos de encendido y apagado del malacate',
                    'Mando de encendido y apagado de los frenos',
                    'Cableado de alimentación',
                    'Conexiones en malacate',
                    'Variador de frecuencia',
                    'Protección principal del malacate',
                    'Protección principal del compresor',
                    'Tablero eléctrico',
                ]),
                ...$this->grupo('Vagonetas', [
                    'Estructura, chasis y carrocería',
                    'Puertas y bisagras',
                    'Ejes de ruedas',
                    'Ruedas',
                    'Rodamientos y lubricación',
                ]),
                ...$this->grupo('Turra', [
                    'Turra en buen estado',
                ]),
                ...$this->grupo('Ferrocarril', [
                    'Rieles y sujeciones (clavos y tornillería)',
                    'Polines de madera',
                    'Guías de la guaya',
                ]),
            ]),
            $this->seccion('Señalización', $this->senalizacion([
                '¿Se evidencia señalización de operación del timbre para subir y bajar el malacate?',
            ])),
            $this->seccion('Condiciones de utilización del equipo', $this->condiciones('del malacate')),
        ]);

        // -------------------------------------------------------------- VENTILADOR
        $this->crearFormato('%ventilador%', [
            $this->seccion('Verificación del estado de la máquina', [
                ...$this->grupo('¿Se encuentra en buen estado la base de sujeción del ventilador?', [
                    'Tornillería y tuercas',
                    'Guaya de sujeción y perros',
                    'Estructura de sujeción de la carcasa del ventilador',
                ]),
                ...$this->grupo('¿Se encuentra en buen estado el ducto del aire?', [
                    'Ducto',
                    'Guaya y accesorios de sujeción del ducto',
                    'Abrazaderas',
                ]),
                ...$this->motor(),
                ...$this->grupo('¿Se encuentra en buen estado el ventilador y ducto del mismo?', [
                    'Rejilla boca de ventilador',
                    'Ducto metálico',
                    'Turbina y aspas del ventilador',
                    'Tornillería',
                ]),
                ...$this->grupo('¿El cableado se encuentra en buen estado?', [
                    'Cableado desde tablero eléctrico hasta el ventilador',
                    'Soportería del cableado',
                ]),
                ...$this->grupo('¿El sistema eléctrico y automatización del sistema eléctrico se encuentra en buen estado?', [
                    'Mandos de marcha y parada del ventilador',
                    'Contactor del mando de marcha y parada',
                    'Variador de frecuencia',
                    'Conexiones en el ventilador y tablero eléctrico',
                    'Transformador 440V-220V',
                    'Protección principal del ventilador',
                    'Protección principal del tablero eléctrico',
                    'Tablero eléctrico principal',
                ]),
            ]),
            // El PDF original dice "malacate" en la tercera pregunta (parece copiado del otro formato)
            $this->seccion('Señalización', $this->senalizacion([
                '¿Se evidencia señalización de operación del timbre para subir y bajar el malacate?',
            ])),
            $this->seccion('Condiciones de utilización del equipo', $this->condiciones('del ventilador')),
        ]);

        // ------------------------------------------------------------ ELECTROBOMBA
        $this->crearFormato('%electrobomba%', [
            $this->seccion('Verificación del estado de la máquina', [
                ...$this->grupo('¿Se encuentra en buen estado la base de la electrobomba?', [
                    'Tornillería y tuercas',
                    'Revisión del chasis o estructura metálica',
                ]),
                ...$this->grupo('¿Se encuentra en buen estado el sistema de succión de agua?', [
                    'Manguera de succión de agua (guío)',
                    'Válvula check de apertura (poma)',
                    'Abrazaderas',
                    'Filtro de la poma',
                    'Accesorios (adaptador macho roscado con conexión a manguera)',
                    'Turbina',
                ]),
                ...$this->motor(),
                ...$this->grupo('¿Se encuentra en buen estado el sistema de bombeo de agua?', [
                    'Accesorios (niples de 2", Y de 2", tapón y adaptadores roscados y abrazaderas)',
                    'Válvulas check',
                    'Manguera',
                    'Racores',
                    'Bujes',
                    'Registros y empaques',
                    'Tubería galvanizada',
                    'Accesorios (uniones, universales)',
                    'Soportería y anclaje de tubería galvanizada',
                ]),
                ...$this->grupo('¿El cableado se encuentra en buen estado?', [
                    'Cableado desde tablero eléctrico hasta la electrobomba',
                    'Soportería del cableado',
                ]),
                ...$this->grupo('¿El sistema eléctrico y automatización del sistema eléctrico se encuentra en buen estado?', [
                    'Mandos de marcha y parada de la electrobomba',
                    'Contactor del mando de marcha y parada',
                    'Cableado de control',
                    'Conexiones en la electrobomba y tablero eléctrico',
                    'Transformador 440V-220V',
                    'Protección principal de la electrobomba',
                    'Protección principal del tablero eléctrico',
                    'Tablero eléctrico principal',
                    'Tablero eléctrico de marcha y parada',
                ]),
            ]),
            $this->seccion('Señalización', $this->senalizacion([
                '¿Se evidencia señalización de operación de la electrobomba?',
                '¿Se encuentra señalizado el pozo de almacenamiento de agua y sus riesgos?',
            ])),
            $this->seccion('Condiciones de utilización del equipo', $this->condiciones('de la electrobomba', [
                '¿Se hace limpieza de lodos a desarenadero y a pozo periódicamente?',
            ])),
        ]);

        // --------------------------------------------------------------- COMPRESOR
        $this->crearFormato('%compresor%', [
            $this->seccion('Verificación del estado de la máquina', [
                ...$this->grupo('¿Se encuentra en buen estado la base del compresor?', [
                    'Tornillería y tuercas',
                    'Revisión de la carcasa o estructura metálica',
                ]),
                ...$this->grupo('¿Se encuentra en buen estado el sistema de succión de aire?', [
                    'Sistema de succión',
                    'Válvula check de apertura',
                    'Abrazaderas',
                    'Filtro',
                    'Accesorios y racores',
                    'Ventilador',
                ]),
                ...$this->motor(),
                // El PDF original dice "sistema de bombeo de agua" (parece copiado del formato de la electrobomba)
                ...$this->grupo('¿Se encuentra en buen estado el sistema de bombeo de agua?', [
                    'Accesorios (niples, tapón, adaptadores roscados y abrazaderas)',
                    'Válvulas de presión',
                    'Manguera',
                    'Racores',
                    'Bujes',
                    'Registros y empaques',
                    'Tubería galvanizada',
                    'Accesorios (uniones, universales)',
                    'Soportería y anclaje de tubería galvanizada',
                ]),
                ...$this->grupo('¿El cableado se encuentra en buen estado?', [
                    'Cableado desde tablero eléctrico hasta el compresor',
                    'Ductos de cableado',
                ]),
                ...$this->grupo('¿El sistema eléctrico y automatización del sistema eléctrico se encuentra en buen estado?', [
                    'Mandos de marcha y parada del compresor',
                    'Contactor del mando de marcha y parada',
                    'Tarjeta de control del compresor',
                    'Conexiones en el compresor y tablero eléctrico',
                    'Transformador 440V-220V',
                    'Protección principal del compresor',
                    'Protección principal del tablero eléctrico',
                    'Tablero eléctrico principal',
                ]),
                ...$this->grupo('¿Las partes internas de operación del compresor se encuentran en buen estado?', [
                    'Nivel y calidad de aceite',
                    'Empalmes y conexiones',
                    'Sensor de temperatura',
                    'Sensor de mantenimiento',
                    'Tubería a secador',
                    'Empaques',
                ]),
            ]),
            $this->seccion('Señalización', $this->senalizacion([
                '¿Se evidencia señalización de operación?',
                '¿Se encuentra señalizado con sus riesgos?',
            ])),
            $this->seccion('Condiciones de utilización del equipo', $this->condiciones('del compresor')),
        ]);
    }

    // ------------------------------------------------------------------ ayudas

    private function grupo(string $titulo, array $preguntas): array
    {
        return [['type' => 'group', 'text' => $titulo], ...$this->items($preguntas)];
    }

    private function items(array $textos): array
    {
        return array_map(fn ($t) => ['type' => 'item', 'text' => $t], $textos);
    }

    private function seccion(string $titulo, array $filas): array
    {
        return ['title' => $titulo, 'rows' => $filas];
    }

    // El grupo del motor es idéntico en los cuatro formatos
    private function motor(): array
    {
        return $this->grupo('¿Se evidencia que el motor está en buen estado?', [
            'Voltaje del motor (440V ± 2%)',
            'Eje de motor',
            'Carcasa de motor',
            'Caja de bornera de motor',
            'Conexiones en buen estado',
        ]);
    }

    private function senalizacion(array $extras): array
    {
        return $this->items([
            '¿Se encuentra con señalización adecuada?',
            '¿Se encuentran señaladas las áreas de riesgo eléctrico?',
            ...$extras,
        ]);
    }

    private function condiciones(string $equipo, array $extras = []): array
    {
        return $this->items([
            "¿Se cuenta con todos los EPP para la operación {$equipo}?",
            '¿El área de trabajo se encuentra despejada y limpia?',
            "¿Se avisa a personas diferentes a la actividad la operación {$equipo}?",
            '¿La operación del equipo se hace por personal capacitado o bajo la supervisión de este?',
            ...$extras,
        ]);
    }

    // ---------------------------------------------------------- creación

    private function crearFormato(string $patronCategoria, array $secciones): void
    {
        $categoria = MachineCategory::where('name', 'ilike', $patronCategoria)->first();

        if (!$categoria) {
            $this->command->warn("No existe una categoría como {$patronCategoria}. Créala y vuelve a correr el seeder.");
            return;
        }

        $yaTiene = InspectionFormatMachine::where('category_id', $categoria->id)
            ->where('is_active', true)
            ->exists();

        if ($yaTiene) {
            $this->command->info("La categoría {$categoria->name} ya tiene formato: no se toca.");
            return;
        }

        DB::transaction(function () use ($categoria, $secciones) {
            $formato = InspectionFormatMachine::create([
                'category_id' => $categoria->id,
                'code' => 'SST-FT-003',
                'version' => '0.1',
                'valid_from' => '2023-03-18',
                'is_active' => true,
            ]);

            foreach ($secciones as $i => $s) {
                $seccion = $formato->sections()->create([
                    'title' => $s['title'],
                    'position' => $i + 1,
                ]);

                foreach ($s['rows'] as $j => $fila) {
                    $seccion->questions()->create([
                        'type' => $fila['type'],
                        'text' => $fila['text'],
                        'position' => $j + 1,
                        'is_active' => true,
                    ]);
                }
            }
        });
    }
}
