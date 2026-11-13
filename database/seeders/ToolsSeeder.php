<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Tools;

class ToolsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Tools::firstOrCreate(
            ['code' => '4ft-p', 'name' => 'Disco de corte 4.5"'],
            [
                'location_id' => 1,
                'category_id' => 1,
                'observations' => 'Consumible abrasivo utilizado para realizar cortes en piezas metálicas y otros materiales compatibles mediante esmeril angular.'
            ]
        );

        Tools::firstOrCreate(
            ['code' => 'lij-80', 'name' => 'Lija de banda grano 80'],
            [
                'location_id' => 1,
                'category_id' => 1,
                'observations' => 'Abrasivo de grano medio utilizado para desbastar, nivelar y preparar superficies antes de procesos de acabado.'
            ]
        );

        // CATEGORÍA 2: Lubricantes
        Tools::firstOrCreate(
            ['code' => 'lub-1', 'name' => 'Grasa multipropósito industrial'],
            [
                'location_id' => 1,
                'category_id' => 2,
                'observations' => 'Lubricante utilizado para reducir la fricción y el desgaste en rodamientos, articulaciones y componentes mecánicos.'
            ]
        );

        Tools::firstOrCreate(
            ['code' => 'lub-2', 'name' => 'Aceite lubricante para cadenas'],
            [
                'location_id' => 1,
                'category_id' => 2,
                'observations' => 'Producto destinado a lubricar cadenas de transmisión, reducir el desgaste y facilitar el movimiento de sus eslabones.'
            ]
        );

        // CATEGORÍA 3: Adhesivos
        Tools::firstOrCreate(
            ['code' => 'adh-1', 'name' => 'Pegante epóxico de dos componentes'],
            [
                'location_id' => 1,
                'category_id' => 3,
                'observations' => 'Adhesivo compuesto por resina y endurecedor, utilizado para unir y reparar diferentes materiales mediante una unión resistente.'
            ]
        );

        Tools::firstOrCreate(
            ['code' => 'adh-2', 'name' => 'Adhesivo instantáneo industrial'],
            [
                'location_id' => 1,
                'category_id' => 3,
                'observations' => 'Adhesivo de secado rápido empleado para unir pequeñas superficies y componentes compatibles.'
            ]
        );

        // CATEGORÍA 4: Sellantes
        Tools::firstOrCreate(
            ['code' => 'sel-1', 'name' => 'Silicona sellante industrial'],
            [
                'location_id' => 1,
                'category_id' => 4,
                'observations' => 'Material sellante utilizado para evitar filtraciones y proteger uniones frente al ingreso de humedad y otros agentes externos.'
            ]
        );

        Tools::firstOrCreate(
            ['code' => 'sel-2', 'name' => 'Cinta selladora de teflón'],
            [
                'location_id' => 1,
                'category_id' => 4,
                'observations' => 'Cinta de PTFE utilizada para mejorar el sellado de conexiones roscadas y prevenir fugas en sistemas compatibles.'
            ]
        );

        // CATEGORÍA 5: Soldadura
        Tools::firstOrCreate(
            ['code' => 'sol-1', 'name' => 'Electrodo revestido E6013'],
            [
                'location_id' => 1,
                'category_id' => 5,
                'observations' => 'Consumible de soldadura utilizado en procesos SMAW para realizar uniones de acero al carbono.'
            ]
        );

        Tools::firstOrCreate(
            ['code' => 'sol-2', 'name' => 'Alambre para soldadura MIG'],
            [
                'location_id' => 1,
                'category_id' => 5,
                'observations' => 'Material de aporte utilizado en procesos de soldadura MIG para unir piezas metálicas mediante alimentación continua.'
            ]
        );

        // CATEGORÍA 6: Fijaciones
        Tools::firstOrCreate(
            ['code' => 'fij-1', 'name' => 'Pernos hexagonales galvanizados'],
            [
                'location_id' => 1,
                'category_id' => 6,
                'observations' => 'Elementos de fijación utilizados para unir estructuras y componentes mecánicos, con recubrimiento galvanizado para mejorar su resistencia a la corrosión.'
            ]
        );

        Tools::firstOrCreate(
            ['code' => 'fij-2', 'name' => 'Tuercas de seguridad industriales'],
            [
                'location_id' => 1,
                'category_id' => 6,
                'observations' => 'Elementos de fijación diseñados para mantener uniones roscadas y reducir el riesgo de aflojamiento por vibraciones.'
            ]
        );

        // CATEGORÍA 7: Filtros
        Tools::firstOrCreate(
            ['code' => 'fil-1', 'name' => 'Filtro de aceite para motor'],
            [
                'location_id' => 1,
                'category_id' => 7,
                'observations' => 'Componente encargado de retener partículas e impurezas presentes en el aceite del motor para proteger sus componentes internos.'
            ]
        );

        Tools::firstOrCreate(
            ['code' => 'fil-2', 'name' => 'Filtro de aire industrial'],
            [
                'location_id' => 1,
                'category_id' => 7,
                'observations' => 'Elemento filtrante encargado de retener polvo y partículas contaminantes del aire que ingresa a los equipos.'
            ]
        );

        // CATEGORÍA 8: Material eléctrico
        Tools::firstOrCreate(
            ['code' => 'ele-1', 'name' => 'Cable eléctrico calibre 12 AWG'],
            [
                'location_id' => 1,
                'category_id' => 8,
                'observations' => 'Conductor eléctrico utilizado en instalaciones y conexiones de equipos, de acuerdo con su capacidad de corriente y condiciones de instalación.'
            ]
        );

        Tools::firstOrCreate(
            ['code' => 'ele-2', 'name' => 'Terminales eléctricos de cobre'],
            [
                'location_id' => 1,
                'category_id' => 8,
                'observations' => 'Accesorios conductores utilizados para realizar conexiones eléctricas seguras entre cables y terminales de equipos.'
            ]
        );

        // CATEGORÍA 9: Refrigerantes
        Tools::firstOrCreate(
            ['code' => 'ref-1', 'name' => 'Refrigerante para motor'],
            [
                'location_id' => 1,
                'category_id' => 9,
                'observations' => 'Fluido encargado de regular la temperatura del motor y proteger el sistema de refrigeración frente a la corrosión.'
            ]
        );

        Tools::firstOrCreate(
            ['code' => 'ref-2', 'name' => 'Líquido refrigerante concentrado'],
            [
                'location_id' => 1,
                'category_id' => 9,
                'observations' => 'Producto concentrado que requiere preparación según las indicaciones del fabricante para su utilización en sistemas de refrigeración.'
            ]
        );

        // CATEGORÍA 10: Productos químicos
        Tools::firstOrCreate(
            ['code' => 'quim-1', 'name' => 'Desengrasante industrial'],
            [
                'location_id' => 1,
                'category_id' => 10,
                'observations' => 'Producto químico utilizado para eliminar grasas, aceites y residuos acumulados en superficies y componentes de maquinaria.'
            ]
        );

        Tools::firstOrCreate(
            ['code' => 'quim-2', 'name' => 'Limpiador de contactos eléctricos'],
            [
                'location_id' => 1,
                'category_id' => 10,
                'observations' => 'Producto especializado para limpiar contactos y componentes eléctricos, eliminando residuos sin dejar depósitos conductores cuando así lo especifica el fabricante.'
            ]
        );

        // CATEGORÍA 12: Eléctricas
        Tools::firstOrCreate(
            ['code' => 'ele-3', 'name' => 'Taladro eléctrico industrial'],
            [
                'location_id' => 1,
                'category_id' => 12,
                'observations' => 'Herramienta eléctrica utilizada para realizar perforaciones en diferentes materiales mediante brocas compatibles.'
            ]
        );

        Tools::firstOrCreate(
            ['code' => 'ele-4', 'name' => 'Esmeril angular eléctrico'],
            [
                'location_id' => 1,
                'category_id' => 12,
                'observations' => 'Herramienta eléctrica empleada para cortar, desbastar y pulir materiales mediante discos y accesorios compatibles.'
            ]
        );

        // CATEGORÍA 13: Neumáticas
        Tools::firstOrCreate(
            ['code' => 'neu-1', 'name' => 'Llave de impacto neumática'],
            [
                'location_id' => 1,
                'category_id' => 13,
                'observations' => 'Herramienta accionada por aire comprimido utilizada para apretar y aflojar elementos de fijación.'
            ]
        );

        Tools::firstOrCreate(
            ['code' => 'neu-2', 'name' => 'Martillo neumático'],
            [
                'location_id' => 1,
                'category_id' => 13,
                'observations' => 'Herramienta accionada mediante aire comprimido utilizada en trabajos de demolición, perforación y fractura de materiales.'
            ]
        );

        // CATEGORÍA 14: De medición
        Tools::firstOrCreate(
            ['code' => 'med-1', 'name' => 'Calibrador Vernier'],
            [
                'location_id' => 1,
                'category_id' => 14,
                'observations' => 'Instrumento de precisión utilizado para medir dimensiones exteriores, interiores y profundidades de diferentes piezas.'
            ]
        );

        Tools::firstOrCreate(
            ['code' => 'med-2', 'name' => 'Multímetro digital'],
            [
                'location_id' => 1,
                'category_id' => 14,
                'observations' => 'Instrumento de medición utilizado para comprobar magnitudes eléctricas como voltaje, corriente y resistencia, según sus especificaciones.'
            ]
        );

        // CATEGORÍA 15: De corte
        Tools::firstOrCreate(
            ['code' => 'cort-1', 'name' => 'Sierra circular portátil'],
            [
                'location_id' => 1,
                'category_id' => 15,
                'observations' => 'Herramienta de corte utilizada para realizar cortes rectos en materiales compatibles mediante un disco circular.'
            ]
        );

        Tools::firstOrCreate(
            ['code' => 'cort-2', 'name' => 'Cizalla manual para metal'],
            [
                'location_id' => 1,
                'category_id' => 15,
                'observations' => 'Herramienta manual utilizada para cortar láminas y piezas metálicas de acuerdo con su capacidad de corte.'
            ]
        );

        // CATEGORÍA 16: De perforación
        Tools::firstOrCreate(
            ['code' => 'perf-1', 'name' => 'Rotomartillo industrial'],
            [
                'location_id' => 1,
                'category_id' => 16,
                'observations' => 'Herramienta de perforación con función de percusión utilizada para trabajar sobre concreto, mampostería y materiales compatibles.'
            ]
        );

        Tools::firstOrCreate(
            ['code' => 'perf-2', 'name' => 'Perforadora manual para roca'],
            [
                'location_id' => 1,
                'category_id' => 16,
                'observations' => 'Herramienta destinada a realizar perforaciones en roca mediante un mecanismo de accionamiento manual.'
            ]
        );

        // CATEGORÍA 17: De sujeción
        Tools::firstOrCreate(
            ['code' => 'suj-1', 'name' => 'Prensa de banco'],
            [
                'location_id' => 1,
                'category_id' => 17,
                'observations' => 'Herramienta de sujeción fijada a un banco de trabajo, utilizada para inmovilizar piezas durante operaciones de mantenimiento.'
            ]
        );

        Tools::firstOrCreate(
            ['code' => 'suj-2', 'name' => 'Pinza de presión'],
            [
                'location_id' => 1,
                'category_id' => 17,
                'observations' => 'Herramienta manual de bloqueo utilizada para sujetar firmemente piezas y componentes durante trabajos mecánicos.'
            ]
        );

        // CATEGORÍA 18: De mantenimiento
        Tools::firstOrCreate(
            ['code' => 'mant-1', 'name' => 'Extractor de rodamientos'],
            [
                'location_id' => 1,
                'category_id' => 18,
                'observations' => 'Herramienta especializada utilizada para desmontar rodamientos y otros componentes ajustados sin aplicar golpes directos.'
            ]
        );

        Tools::firstOrCreate(
            ['code' => 'mant-2', 'name' => 'Llave dinamométrica'],
            [
                'location_id' => 1,
                'category_id' => 18,
                'observations' => 'Herramienta de precisión utilizada para aplicar un torque específico al ajustar pernos y tuercas.'
            ]
        );

        // CATEGORÍA 19: De excavación
        Tools::firstOrCreate(
            ['code' => 'exc-1', 'name' => 'Pico minero'],
            [
                'location_id' => 1,
                'category_id' => 19,
                'observations' => 'Herramienta manual utilizada para fragmentar terrenos compactos y desprender materiales durante labores de excavación.'
            ]
        );

        Tools::firstOrCreate(
            ['code' => 'exc-2', 'name' => 'Pala de punta reforzada'],
            [
                'location_id' => 1,
                'category_id' => 19,
                'observations' => 'Herramienta manual empleada para excavar, remover y trasladar tierra, minerales y otros materiales.'
            ]
        );

        // CATEGORÍA 20: De limpieza
        Tools::firstOrCreate(
            ['code' => 'lim-1', 'name' => 'Cepillo de alambre industrial'],
            [
                'location_id' => 1,
                'category_id' => 20,
                'observations' => 'Herramienta utilizada para retirar óxido, residuos, suciedad y restos de material adheridos a superficies metálicas.'
            ]
        );

        Tools::firstOrCreate(
            ['code' => 'lim-2', 'name' => 'Aspiradora industrial'],
            [
                'location_id' => 1,
                'category_id' => 20,
                'observations' => 'Equipo de limpieza utilizado para recolectar polvo, partículas y residuos generados durante las actividades de mantenimiento.'
            ]
        );
}
}
