<?php

namespace Database\Seeders;
use App\Models\Machine;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class MachinesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Machine::firstOrCreate(
            ['code' => 'PT2-EL01'],
            [
                'name' => 'Electrobomba 1',
                'model' => '2015',
                'serial_number' => 'SN-001',
                'acquisition_date' => '2022-01-15',
                'acquisition_cost' => 15000.00,
                'status' => 'active',
                'warranty' => 12,
                'image' => null,
                'location_id' => 1,
                'machine_category_id' => 1,
                'brand'=>'Minova',
                'responsible' => 'Jefe de mina',
                'machine_usage' => 'Desague',
                'in_operation' => true,
                'characteristics' => 'Potencia: 15HP, Frecuencia: 60HERZ, Voltaje: 220/440 V, Velocidad: 3500 RPM',
            ]
        );

        // ==========================================
        // CATEGORÍA 1: MOTORES
        // ==========================================
        
        Machine::firstOrCreate(
            ['code' => 'PT2-EL12'],
            [
                'name' => 'Motor eléctrico trifásico',
                'model' => '2020',
                'serial_number' => 'SN-012',
                'acquisition_date' => '2022-02-10',
                'acquisition_cost' => 12500.00,
                'status' => 'active',
                'warranty' => 12,
                'image' => null,
                'location_id' => 3,
                'machine_category_id' => 1,
                'brand' => 'Siemens',
                'responsible' => 'Técnicos mina',
                'machine_usage' => 'Accionamiento de maquinaria',
                'in_operation' => true,
                'characteristics' => 'Potencia: 10HP, Frecuencia: 60Hz, Voltaje: 220/440 V, Velocidad: 1750 RPM',
            ]
        );
        
        Machine::firstOrCreate(
            ['code' => 'PT2-EL13'],
            [
                'name' => 'Motor industrial monofásico',
                'model' => '2019',
                'serial_number' => 'SN-013',
                'acquisition_date' => '2022-04-15',
                'acquisition_cost' => 7800.00,
                'status' => 'active',
                'warranty' => 12,
                'image' => null,
                'location_id' => 2,
                'machine_category_id' => 1,
                'brand' => 'WEG',
                'responsible' => 'Técnicos mina',
                'machine_usage' => 'Accionamiento de equipos auxiliares',
                'in_operation' => true,
                'characteristics' => 'Potencia: 5HP, Frecuencia: 60Hz, Voltaje: 220 V, Velocidad: 1750 RPM',
            ]
        );
        
        Machine::firstOrCreate(
            ['code' => 'PT2-EL14'],
            [
                'name' => 'Motor eléctrico de alta potencia',
                'model' => '2021',
                'serial_number' => 'SN-014',
                'acquisition_date' => '2023-01-20',
                'acquisition_cost' => 18500.00,
                'status' => 'active',
                'warranty' => 24,
                'image' => null,
                'location_id' => 5,
                'machine_category_id' => 1,
                'brand' => 'ABB',
                'responsible' => 'Jefe de mina',
                'machine_usage' => 'Accionamiento de sistemas industriales',
                'in_operation' => true,
                'characteristics' => 'Potencia: 20HP, Frecuencia: 60Hz, Voltaje: 440 V, Velocidad: 1800 RPM',
            ]
        );
        
        
        // ==========================================
        // CATEGORÍA 2: BOMBAS
        // ==========================================
        
        Machine::firstOrCreate(
            ['code' => 'PT2-EL15'],
            [
                'name' => 'Electrobomba sumergible',
                'model' => '2020',
                'serial_number' => 'SN-015',
                'acquisition_date' => '2022-03-12',
                'acquisition_cost' => 16500.00,
                'status' => 'active',
                'warranty' => 12,
                'image' => null,
                'location_id' => 6,
                'machine_category_id' => 2,
                'brand' => 'Pedrollo',
                'responsible' => 'Jefe de mina',
                'machine_usage' => 'Desague de zonas subterraneas',
                'in_operation' => true,
                'characteristics' => 'Potencia: 7.5HP, Voltaje: 220 V, Caudal: 300 L/min, Altura: 35 m',
            ]
        );
        
        Machine::firstOrCreate(
            ['code' => 'PT2-EL16'],
            [
                'name' => 'Bomba centrifuga industrial',
                'model' => '2019',
                'serial_number' => 'SN-016',
                'acquisition_date' => '2022-06-18',
                'acquisition_cost' => 12000.00,
                'status' => 'active',
                'warranty' => 12,
                'image' => null,
                'location_id' => 8,
                'machine_category_id' => 2,
                'brand' => 'Grundfos',
                'responsible' => 'Técnicos mina',
                'machine_usage' => 'Bombeo de agua',
                'in_operation' => true,
                'characteristics' => 'Potencia: 5HP, Voltaje: 220/440 V, Caudal: 200 L/min, Velocidad: 3500 RPM',
            ]
        );
        
        Machine::firstOrCreate(
            ['code' => 'PT2-EL17'],
            [
                'name' => 'Motobomba de drenaje',
                'model' => '2022',
                'serial_number' => 'SN-017',
                'acquisition_date' => '2023-02-14',
                'acquisition_cost' => 9500.00,
                'status' => 'active',
                'warranty' => 18,
                'image' => null,
                'location_id' => 4,
                'machine_category_id' => 2,
                'brand' => 'Honda',
                'responsible' => 'Operador de maquinaria',
                'machine_usage' => 'Evacuacion de aguas',
                'in_operation' => true,
                'characteristics' => 'Potencia: 6.5HP, Combustible: Gasolina, Caudal: 1000 L/min, Succion: 3 pulgadas',
            ]
        );
        
        
        // ==========================================
        // CATEGORÍA 3: COMPRESORES
        // ==========================================
        
        Machine::firstOrCreate(
            ['code' => 'PT2-EL18'],
            [
                'name' => 'Compresor de aire industrial',
                'model' => '2020',
                'serial_number' => 'SN-018',
                'acquisition_date' => '2022-05-10',
                'acquisition_cost' => 22000.00,
                'status' => 'active',
                'warranty' => 24,
                'image' => null,
                'location_id' => 10,
                'machine_category_id' => 3,
                'brand' => 'Atlas Copco',
                'responsible' => 'Técnicos mina',
                'machine_usage' => 'Suministro de aire comprimido',
                'in_operation' => true,
                'characteristics' => 'Potencia: 15HP, Presion: 8 BAR, Capacidad: 500 L, Voltaje: 220 V',
            ]
        );
        
        Machine::firstOrCreate(
            ['code' => 'PT2-EL19'],
            [
                'name' => 'Compresor de piston',
                'model' => '2018',
                'serial_number' => 'SN-019',
                'acquisition_date' => '2022-08-22',
                'acquisition_cost' => 11500.00,
                'status' => 'active',
                'warranty' => 12,
                'image' => null,
                'location_id' => 2,
                'machine_category_id' => 3,
                'brand' => 'Ingersoll Rand',
                'responsible' => 'Jefe de mina',
                'machine_usage' => 'Alimentacion de herramientas neumaticas',
                'in_operation' => true,
                'characteristics' => 'Potencia: 7.5HP, Presion: 10 BAR, Capacidad: 300 L, Voltaje: 220 V',
            ]
        );
        
        Machine::firstOrCreate(
            ['code' => 'PT2-EL20'],
            [
                'name' => 'Compresor portatil',
                'model' => '2021',
                'serial_number' => 'SN-020',
                'acquisition_date' => '2023-03-15',
                'acquisition_cost' => 18000.00,
                'status' => 'active',
                'warranty' => 18,
                'image' => null,
                'location_id' => 9,
                'machine_category_id' => 3,
                'brand' => 'Kaeser',
                'responsible' => 'Operador de maquinaria',
                'machine_usage' => 'Suministro movil de aire comprimido',
                'in_operation' => true,
                'characteristics' => 'Potencia: 20HP, Presion: 7 BAR, Caudal: 3.5 m3/min, Combustible: Diesel',
            ]
        );
        
        
        // ==========================================
        // CATEGORÍA 4: GENERADORES
        // ==========================================
        
        Machine::firstOrCreate(
            ['code' => 'PT2-EL21'],
            [
                'name' => 'Generador diesel industrial',
                'model' => '2020',
                'serial_number' => 'SN-021',
                'acquisition_date' => '2022-04-20',
                'acquisition_cost' => 35000.00,
                'status' => 'active',
                'warranty' => 24,
                'image' => null,
                'location_id' => 5,
                'machine_category_id' => 4,
                'brand' => 'Cummins',
                'responsible' => 'Técnicos mina',
                'machine_usage' => 'Generacion de energia electrica',
                'in_operation' => true,
                'characteristics' => 'Potencia: 60KVA, Frecuencia: 60Hz, Voltaje: 220/440 V, Combustible: Diesel',
            ]
        );
        
        Machine::firstOrCreate(
            ['code' => 'PT2-EL22'],
            [
                'name' => 'Generador electrico portatil',
                'model' => '2021',
                'serial_number' => 'SN-022',
                'acquisition_date' => '2022-09-12',
                'acquisition_cost' => 8500.00,
                'status' => 'active',
                'warranty' => 12,
                'image' => null,
                'location_id' => 6,
                'machine_category_id' => 4,
                'brand' => 'Hyundai',
                'responsible' => 'Técnicos mina',
                'machine_usage' => 'Alimentacion electrica auxiliar',
                'in_operation' => true,
                'characteristics' => 'Potencia: 5.5KW, Frecuencia: 60Hz, Voltaje: 220 V, Combustible: Gasolina',
            ]
        );
        
        Machine::firstOrCreate(
            ['code' => 'PT2-EL23'],
            [
                'name' => 'Planta electrica trifasica',
                'model' => '2022',
                'serial_number' => 'SN-023',
                'acquisition_date' => '2023-04-10',
                'acquisition_cost' => 42000.00,
                'status' => 'active',
                'warranty' => 24,
                'image' => null,
                'location_id' => 7,
                'machine_category_id' => 4,
                'brand' => 'Kohler',
                'responsible' => 'Jefe de mina',
                'machine_usage' => 'Respaldo energetico',
                'in_operation' => true,
                'characteristics' => 'Potencia: 75KVA, Frecuencia: 60Hz, Voltaje: 220/440 V, Combustible: Diesel',
            ]
        );
        
        
        // ==========================================
        // CATEGORÍA 5: EQUIPOS DE VENTILACIÓN
        // ==========================================
        
        Machine::firstOrCreate(
            ['code' => 'PT2-EL24'],
            [
                'name' => 'Ventilador axial minero',
                'model' => '2020',
                'serial_number' => 'SN-024',
                'acquisition_date' => '2022-05-15',
                'acquisition_cost' => 18500.00,
                'status' => 'active',
                'warranty' => 18,
                'image' => null,
                'location_id' => 8,
                'machine_category_id' => 5,
                'brand' => 'Sodeca',
                'responsible' => 'Jefe de mina',
                'machine_usage' => 'Ventilacion subterranea',
                'in_operation' => true,
                'characteristics' => 'Potencia: 10HP, Voltaje: 220/440 V, Caudal: 12000 m3/h, Velocidad: 1750 RPM',
            ]
        );
        
        Machine::firstOrCreate(
            ['code' => 'PT2-EL25'],
            [
                'name' => 'Extractor de aire industrial',
                'model' => '2019',
                'serial_number' => 'SN-025',
                'acquisition_date' => '2022-08-17',
                'acquisition_cost' => 9500.00,
                'status' => 'active',
                'warranty' => 12,
                'image' => null,
                'location_id' => 9,
                'machine_category_id' => 5,
                'brand' => 'Soler & Palau',
                'responsible' => 'Técnicos mina',
                'machine_usage' => 'Extraccion de aire contaminado',
                'in_operation' => true,
                'characteristics' => 'Potencia: 5HP, Voltaje: 220 V, Caudal: 8000 m3/h, Velocidad: 1800 RPM',
            ]
        );
        
        Machine::firstOrCreate(
            ['code' => 'PT2-EL26'],
            [
                'name' => 'Ventilador centrifugo',
                'model' => '2021',
                'serial_number' => 'SN-026',
                'acquisition_date' => '2023-02-25',
                'acquisition_cost' => 14500.00,
                'status' => 'active',
                'warranty' => 18,
                'image' => null,
                'location_id' => 3,
                'machine_category_id' => 5,
                'brand' => 'Greenheck',
                'responsible' => 'Técnicos mina',
                'machine_usage' => 'Circulacion de aire industrial',
                'in_operation' => true,
                'characteristics' => 'Potencia: 7.5HP, Voltaje: 220/440 V, Caudal: 10000 m3/h, Velocidad: 1750 RPM',
            ]
        );
        
        
        // ==========================================
        // CATEGORÍA 6: EQUIPOS DE PERFORACIÓN
        // ==========================================
        
        Machine::firstOrCreate(
            ['code' => 'PT2-EL27'],
            [
                'name' => 'Perforadora neumatica',
                'model' => '2020',
                'serial_number' => 'SN-027',
                'acquisition_date' => '2022-06-10',
                'acquisition_cost' => 28000.00,
                'status' => 'active',
                'warranty' => 18,
                'image' => null,
                'location_id' => 7,
                'machine_category_id' => 6,
                'brand' => 'Atlas Copco',
                'responsible' => 'Operador de maquinaria',
                'machine_usage' => 'Perforacion de roca',
                'in_operation' => true,
                'characteristics' => 'Presion: 6 BAR, Tipo: Neumatica, Peso: 25 KG, Uso: Perforacion minera',
            ]
        );
        
        Machine::firstOrCreate(
            ['code' => 'PT2-EL28'],
            [
                'name' => 'Taladro industrial de columna',
                'model' => '2019',
                'serial_number' => 'SN-028',
                'acquisition_date' => '2022-10-05',
                'acquisition_cost' => 12500.00,
                'status' => 'active',
                'warranty' => 12,
                'image' => null,
                'location_id' => 9,
                'machine_category_id' => 6,
                'brand' => 'Bosch',
                'responsible' => 'Técnicos mina',
                'machine_usage' => 'Perforacion de materiales',
                'in_operation' => true,
                'characteristics' => 'Potencia: 1500W, Voltaje: 220 V, Velocidad: 900 RPM, Capacidad: 25 mm',
            ]
        );
        
        Machine::firstOrCreate(
            ['code' => 'PT2-EL29'],
            [
                'name' => 'Perforadora electrica',
                'model' => '2022',
                'serial_number' => 'SN-029',
                'acquisition_date' => '2023-05-20',
                'acquisition_cost' => 19500.00,
                'status' => 'active',
                'warranty' => 24,
                'image' => null,
                'location_id' => 5,
                'machine_category_id' => 6,
                'brand' => 'Makita',
                'responsible' => 'Operador de maquinaria',
                'machine_usage' => 'Perforacion de roca y concreto',
                'in_operation' => true,
                'characteristics' => 'Potencia: 1800W, Voltaje: 220 V, Frecuencia: 60Hz, Sistema: Electrico',
            ]
        );
        
        
        // ==========================================
        // CATEGORÍA 7: EQUIPOS DE TRITURACIÓN
        // ==========================================
        
        Machine::firstOrCreate(
            ['code' => 'PT2-EL30'],
            [
                'name' => 'Trituradora de mandibulas',
                'model' => '2020',
                'serial_number' => 'SN-030',
                'acquisition_date' => '2022-07-14',
                'acquisition_cost' => 65000.00,
                'status' => 'active',
                'warranty' => 24,
                'image' => null,
                'location_id' => 9,
                'machine_category_id' => 7,
                'brand' => 'Metso',
                'responsible' => 'Operador de maquinaria',
                'machine_usage' => 'Trituracion primaria de minerales',
                'in_operation' => true,
                'characteristics' => 'Potencia: 40HP, Capacidad: 10 Ton/h, Alimentacion: 200 mm, Tipo: Mandibulas',
            ]
        );
        
        Machine::firstOrCreate(
            ['code' => 'PT2-EL31'],
            [
                'name' => 'Molino de martillos',
                'model' => '2019',
                'serial_number' => 'SN-031',
                'acquisition_date' => '2022-11-18',
                'acquisition_cost' => 32000.00,
                'status' => 'active',
                'warranty' => 18,
                'image' => null,
                'location_id' => 4,
                'machine_category_id' => 7,
                'brand' => 'Eirich',
                'responsible' => 'Jefe de mina',
                'machine_usage' => 'Reduccion de tamaño de minerales',
                'in_operation' => true,
                'characteristics' => 'Potencia: 25HP, Capacidad: 5 Ton/h, Voltaje: 440 V, Sistema: Martillos',
            ]
        );
        
        Machine::firstOrCreate(
            ['code' => 'PT2-EL32'],
            [
                'name' => 'Trituradora de impacto',
                'model' => '2021',
                'serial_number' => 'SN-032',
                'acquisition_date' => '2023-06-12',
                'acquisition_cost' => 58000.00,
                'status' => 'active',
                'warranty' => 24,
                'image' => null,
                'location_id' => 9,
                'machine_category_id' => 7,
                'brand' => 'Sandvik',
                'responsible' => 'Operador de maquinaria',
                'machine_usage' => 'Trituracion secundaria de minerales',
                'in_operation' => true,
                'characteristics' => 'Potencia: 35HP, Capacidad: 8 Ton/h, Voltaje: 440 V, Sistema: Impacto',
            ]
        );
        
        
        // ==========================================
        // CATEGORÍA 8: EQUIPOS DE TRANSPORTE
        // ==========================================
        
        Machine::firstOrCreate(
            ['code' => 'PT2-EL33'],
            [
                'name' => 'Banda transportadora industrial',
                'model' => '2020',
                'serial_number' => 'SN-033',
                'acquisition_date' => '2022-08-20',
                'acquisition_cost' => 25000.00,
                'status' => 'active',
                'warranty' => 18,
                'image' => null,
                'location_id' => 8,
                'machine_category_id' => 8,
                'brand' => 'Dunlop',
                'responsible' => 'Operador de maquinaria',
                'machine_usage' => 'Transporte de minerales',
                'in_operation' => true,
                'characteristics' => 'Potencia: 10HP, Longitud: 15m, Ancho: 60cm, Voltaje: 440 V',
            ]
        );
        
        Machine::firstOrCreate(
            ['code' => 'PT2-EL34'],
            [
                'name' => 'Carretilla electrica industrial',
                'model' => '2021',
                'serial_number' => 'SN-034',
                'acquisition_date' => '2023-01-15',
                'acquisition_cost' => 14500.00,
                'status' => 'active',
                'warranty' => 12,
                'image' => null,
                'location_id' => 7,
                'machine_category_id' => 8,
                'brand' => 'Toyota',
                'responsible' => 'Operador de maquinaria',
                'machine_usage' => 'Transporte de materiales',
                'in_operation' => true,
                'characteristics' => 'Capacidad: 1000 KG, Voltaje: 48 V, Motor: Electrico, Velocidad: 6 KM/h',
            ]
        );
        
        Machine::firstOrCreate(
            ['code' => 'PT2-EL35'],
            [
                'name' => 'Volqueta minera',
                'model' => '2019',
                'serial_number' => 'SN-035',
                'acquisition_date' => '2023-04-22',
                'acquisition_cost' => 95000.00,
                'status' => 'active',
                'warranty' => 24,
                'image' => null,
                'location_id' => 8,
                'machine_category_id' => 8,
                'brand' => 'Caterpillar',
                'responsible' => 'Operador de maquinaria',
                'machine_usage' => 'Transporte de material extraido',
                'in_operation' => true,
                'characteristics' => 'Potencia: 150HP, Capacidad: 10 Ton, Combustible: Diesel, Transmision: Mecanica',
            ]
        );
        
        
        // ==========================================
        // CATEGORÍA 9: EQUIPOS DE ELEVACIÓN
        // ==========================================
        
        Machine::firstOrCreate(
            ['code' => 'PT2-EL36'],
            [
                'name' => 'Polipasto electrico',
                'model' => '2020',
                'serial_number' => 'SN-036',
                'acquisition_date' => '2022-09-10',
                'acquisition_cost' => 18000.00,
                'status' => 'active',
                'warranty' => 18,
                'image' => null,
                'location_id' => 11,
                'machine_category_id' => 9,
                'brand' => 'Kito',
                'responsible' => 'Técnicos mina',
                'machine_usage' => 'Elevacion de cargas',
                'in_operation' => true,
                'characteristics' => 'Potencia: 3HP, Capacidad: 2 Ton, Voltaje: 220 V, Velocidad: 8 m/min',
            ]
        );
        
        Machine::firstOrCreate(
            ['code' => 'PT2-EL37'],
            [
                'name' => 'Malacate industrial',
                'model' => '2019',
                'serial_number' => 'SN-037',
                'acquisition_date' => '2022-12-05',
                'acquisition_cost' => 28500.00,
                'status' => 'active',
                'warranty' => 12,
                'image' => null,
                'location_id' => 10,
                'machine_category_id' => 9,
                'brand' => 'Ingersoll Rand',
                'responsible' => 'Jefe de mina',
                'machine_usage' => 'Elevacion de materiales',
                'in_operation' => true,
                'characteristics' => 'Potencia: 10HP, Capacidad: 3 Ton, Voltaje: 440 V, Sistema: Electrico',
            ]
        );
        
        Machine::firstOrCreate(
            ['code' => 'PT2-EL38'],
            [
                'name' => 'Gato hidraulico industrial',
                'model' => '2022',
                'serial_number' => 'SN-038',
                'acquisition_date' => '2023-07-10',
                'acquisition_cost' => 6500.00,
                'status' => 'active',
                'warranty' => 12,
                'image' => null,
                'location_id' => 17,
                'machine_category_id' => 9,
                'brand' => 'Enerpac',
                'responsible' => 'Técnicos mina',
                'machine_usage' => 'Elevacion de maquinaria',
                'in_operation' => true,
                'characteristics' => 'Capacidad: 20 Ton, Presion: 700 BAR, Sistema: Hidraulico, Accionamiento: Manual',
            ]
        );
        
        
        // ==========================================
        // CATEGORÍA 10: EQUIPOS DE CONSTRUCCIÓN
        // ==========================================
        
        Machine::firstOrCreate(
            ['code' => 'PT2-EL39'],
            [
                'name' => 'Mezcladora de concreto',
                'model' => '2020',
                'serial_number' => 'SN-039',
                'acquisition_date' => '2022-10-15',
                'acquisition_cost' => 12500.00,
                'status' => 'active',
                'warranty' => 12,
                'image' => null,
                'location_id' => 1,
                'machine_category_id' => 10,
                'brand' => 'Menegotti',
                'responsible' => 'Técnicos mina',
                'machine_usage' => 'Preparacion de concreto',
                'in_operation' => true,
                'characteristics' => 'Potencia: 6.5HP, Capacidad: 400 L, Combustible: Gasolina, Velocidad: 28 RPM',
            ]
        );
        
        Machine::firstOrCreate(
            ['code' => 'PT2-EL40'],
            [
                'name' => 'Vibrocompactador',
                'model' => '2021',
                'serial_number' => 'SN-040',
                'acquisition_date' => '2023-03-20',
                'acquisition_cost' => 38000.00,
                'status' => 'active',
                'warranty' => 24,
                'image' => null,
                'location_id' => 1,
                'machine_category_id' => 10,
                'brand' => 'Bomag',
                'responsible' => 'Operador de maquinaria',
                'machine_usage' => 'Compactacion de terrenos',
                'in_operation' => true,
                'characteristics' => 'Potencia: 25HP, Peso: 1500 KG, Combustible: Diesel, Ancho: 90 cm',
            ]
        );
        
        Machine::firstOrCreate(
            ['code' => 'PT2-EL41'],
            [
                'name' => 'Retroexcavadora',
                'model' => '2019',
                'serial_number' => 'SN-041',
                'acquisition_date' => '2023-08-15',
                'acquisition_cost' => 125000.00,
                'status' => 'active',
                'warranty' => 24,
                'image' => null,
                'location_id' => 1,
                'machine_category_id' => 10,
                'brand' => 'JCB',
                'responsible' => 'Operador de maquinaria',
                'machine_usage' => 'Excavacion y movimiento de tierras',
                'in_operation' => true,
                'characteristics' => 'Potencia: 90HP, Profundidad de excavacion: 4.5 m, Combustible: Diesel, Capacidad: 1 m3',
            ]
        );


}

}