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
        // ==========================================
        // CATEGORÍA 1: Electrobombas
        // ==========================================
        Machine::firstOrCreate(
        ['code' => 'PT2-EL01'],
        [
        'name' => 'Electrobomba 1',
        'model' => '2015',
        'serial_number'  => '63072000A2-15060129',
        'responsible' => 'Jefe de mina',
        'status' => 'Activo',
        'start_up_date'=> null,
        'brand'=>'IHM',
        'plate' => '08-DEZ2014 1026-537834  NBR - 17094-7',
        'fuel'=>null,
        'power'=> '15 HP',
        'voltage' => '220/440V',
        'location_id' => 5,
        'machine_category_id' => 1,
        'image'=> null,
        'characteristics' => 'CARACTERISTICAS TECNICAS DEL MOTOR : Marca: WEG, Potencia: 15HP, Frecuencia: 60 HERZ, Voltaje: 220/440 V, Velocidad: 3500 RPM'
        ]
    );

     Machine::firstOrCreate(
        ['code' => 'PT2-EL02'],
        [
        'name' => 'Electrobomba 2',
        'model' => '2015',
        'serial_number'  => '20AL2',
        'responsible' => 'Jefe de mina',
        'status' => 'Activo',
        'start_up_date'=> null,
        'brand'=>'IHM',
        'plate' => '1026184497  NBR-17094-7',
        'fuel'=> null,
        'power'=> null,
        'voltage'=> '220/440 V',
        'location_id' => 5,
        'machine_category_id' => 1,
        'image'=> null,
        'characteristics' => null
        ]
    );

     Machine::firstOrCreate(
        ['code' => 'PT2-EL03'],
        [
        'name' => 'Electrobomba 3',
        'model' => '2015',
        'serial_number'  => '63072000A-2',
        'responsible' => 'Jefe de mina',
        'status' => 'Activo',
        'start_up_date'=> null,
        'brand'=>'IHM',
        'plate' => "1026184497  NBR-17094-8",
        'fuel'=> null,
        'power'=> '15 HP',
        'voltage'=> '220/440 V',
        'location_id' => 5,
        'machine_category_id' => 1,
        'image'=> null,
        'characteristics' => null
        ]
    );
       
        
        // ==========================================
        // CATEGORÍA 2: COMPRESORES
        // ==========================================
         Machine::firstOrCreate(
        ['code' => 'PM-CO1'],
        [
        'name' => 'Compresor 1',
        'model' => 'CSD 75',
        'serial_number'  => '1109',
        'responsible' => 'Jefe de mina',
        'status' => 'Activo',
        'start_up_date'=> null,
        'brand'=>'KAESER DEUTZ',
        'plate' => 'PTG',
        'fuel'=> null,
        'power'=> null,
        'voltage'=> null,
        'location_id' => 3,
        'machine_category_id' => 2,
        'image'=> null,
        'characteristics' => null
        ]
    );
       
      Machine::firstOrCreate(
        ['code' => 'PM-CO2'],
        [
        'name' => 'Compresor 2',
        'model' => 'ASD 40',
        'serial_number'  => '1104',
        'responsible' => 'Jefe de mina',
        'status' => 'Activo',
        'start_up_date'=> null,
        'brand'=>'KAESER DEUTZ',
        'plate' => 'PTG',
        'fuel'=> null,
        'power'=> null,
        'voltage'=> '440',
        'location_id' => 3,
        'machine_category_id' => 2,
        'image'=> null,
        'characteristics' => null
        ]
    );
        
        // ==========================================
        // CATEGORÍA 3: VENTILADORES
        // ==========================================
        
      Machine::firstOrCreate(
        ['code' => 'PM-VE-06'],
        [
        'name' => 'Ventilador axial 1',
        'model' => 'B20',
        'serial_number'  => '91118682',
        'responsible' => 'Jefe de mina',
        'status' => 'Activo',
        'start_up_date'=> null,
        'brand'=>'OVERHAULED FAN',
        'plate' => 'B20 9300/692',
        'fuel'=> null,
        'power'=> null,
        'voltage'=> '500/550-1000/1100',
        'location_id' => 20,
        'machine_category_id' => 3,
        'image'=> null,
        'characteristics' => 'El producto está compuesto por un motor a prueba de explosiones, paletas, conductos de aire y otros; Está en bajo nivel de ruido, bajo consumo y rendimiento perfecto; 
                            Además de usarse para ventilación y extracción, también se puede instalar en serie para mejorar la presión en la tubería; 
                            El material de la carcasa puede ser una placa de blindaje o fibra reforzada con hilo de plástico;  Cableado con cable. '
        ]
    );


     Machine::firstOrCreate(
        ['code' => 'PM-VE-07'],
        [
        'name' => 'Ventilador axial 2',
        'model' => 'FBD-2X7.5',
        'serial_number'  => '1106249',
        'responsible' => 'Jefe de mina',
        'status' => 'Activo',
        'start_up_date'=> null,
        'brand'=>'FBD EXPLOSION PROOF FORCED ',
        'plate' => 'B20 9300/692',
        'fuel'=> null,
        'power'=> '2X7.5KW',
        'voltage'=> '500/550-1000/1100',
        'location_id' => 20,
        'machine_category_id' => 3,
        'image'=> null,
        'characteristics' => 'MODELO:FBD-2X7.5    FRECUENCIA: 60HZ  PODER:2X7.5KW  ACTUAL: 12.9A  PRESION: 340-3500 Pa  GRADO DE AISLAMIENTO: B  SERIAL N: 
                            1106249  CONEXIÓN DE CABLES : UN  TRIANGULO  A PRUEBA DE EXPLOSIONES: Exdl  VOLTAJE: 440V  VELOCIDAD: 3500 r/min  FASE: 3  CAPACIDAD: 260-180 m3/min   PESO: 315 KG  FECHA: 2011-11
 '
        ]
    );

       Machine::firstOrCreate(
        ['code' => 'PM-VE-08'],
        [
        'name' => 'Ventilador axial 3',
        'model' => 'B666',
        'serial_number'  => '91118682',
        'responsible' => 'Jefe de mina',
        'status' => 'Activo',
        'start_up_date'=> null,
        'brand'=>'SERIE 91118682',
        'plate' => '666666',
        'fuel'=> null,
        'power'=> null,
        'voltage'=> null,
        'location_id' => 20,
        'machine_category_id' => 3,
        'image'=> null,
        'characteristics' => null
        ]
    );
//VENTILADORES TUNEL 1 -2
        

    Machine::firstOrCreate(
        ['code' => 'T1-VE01'],
        [
        'name' => 'Ventilador axial soplante',
        'model' => '1992',
        'serial_number'  => 'FBD-2X5,5.1',
        'responsible' => 'Jefe de mina',
        'status' => 'Activo',
        'start_up_date'=> null,
        'brand'=>'AEREX',
        'plate' => '1247',
        'fuel'=> null,
        'power'=> '7.5 KW',
        'voltage'=> '440',
        'location_id' => 4,
        'machine_category_id' => 3,
        'image'=> null,
        'characteristics' => 'PESO:290Kg,   ALTURA: 58 CM, ANCHO:59CM, LARGO: 1.41 M, POTENCIA: 7.5 KW	FRECUENCIA:60 HZ'
        ]
    );

    Machine::firstOrCreate(
        ['code' => 'T2-VE02'],
        [
        'name' => 'Ventilador 1 TUNEL 2',
        'model' => '1992',
        'serial_number'  => 'FBD-2X5,5.2',
        'responsible' => 'Jefe de mina',
        'status' => 'Activo',
        'start_up_date'=> null,
        'brand'=>'ENGART',
        'plate' => '34277',
        'fuel'=> null,
        'power'=> '7.5 KW',
        'voltage'=> null,
        'location_id' => 5,
        'machine_category_id' => 3,
        'image'=> null,
        'characteristics' => null
        ]
    );

    Machine::firstOrCreate(
        ['code' => 'T2-VE03'],
        [
        'name' => 'Ventilador 2 TUNEL 2',
        'model' => '1992',
        'serial_number'  => 'FBY-11',
        'responsible' => 'Jefe de mina',
        'status' => 'Activo',
        'start_up_date'=> null,
        'brand'=>'ENGART',
        'plate' => '1246',
        'fuel'=> null,
        'power'=> '7.5 KW',
        'voltage'=> null,
        'location_id' => 5,
        'machine_category_id' => 3,
        'image'=> null,
        'characteristics' => null
        ]
    );
    //ventiladores tunel 3

    Machine::firstOrCreate(
            ['code' => 'T3-VE04'],
            [
            'name' => 'VENTILADOR  AXIAL N°1',
            'model' => "1992",
            'serial_number'  => 'FBD-2X5,5.3',
            'responsible' => 'Jefe de mina',
            'status' => 'Activo',
            'start_up_date'=> null,
            'brand'=>'AEREX',
            'plate' => "1245",
            'fuel'=> null,
            'power'=> "18,5-KW",
            'voltage'=> "500/650 A 1001/1100",
            'location_id' => 4,
            'machine_category_id' => 3,
            'image'=> null,
            'characteristics' => null
            ]
        );
        Machine::firstOrCreate(
            ['code' => 'T3-VE05'],
            [
            'name' => 'VENTILADOR  AXIAL N°2',
            'model' => "1992",
            'serial_number'  => 'FBD-2X5,5.4',
            'responsible' => 'Jefe de mina',
            'status' => 'Activo',
            'start_up_date'=> null,
            'brand'=>'AEREX',
            'plate' => "1246",
            'fuel'=> null,
            'power'=> "18,5-KW",
            'voltage'=> "500/650 A 1000/1100",
            'location_id' => 6,
            'machine_category_id' => 3,
            'image'=> null,
            'characteristics' => null
            ]
        );


        // ==========================================
        // CATEGORÍA 4: GENERADORES
        // ==========================================
        
        
        
        // ==========================================
        // CATEGORÍA 5: EQUIPOS DE VENTILACIÓN
        // ==========================================
        
        
        
        
        // ==========================================
        // CATEGORÍA 6: EQUIPOS DE PERFORACIÓN
        // ==========================================
        
       
        
        
        // ==========================================
        // CATEGORÍA 7: EQUIPOS DE TRITURACIÓN
        // ==========================================
        
       
        
       
        
        // ==========================================
        // CATEGORÍA 8: EQUIPOS DE TRANSPORTE
        // ==========================================
        
        
        
        // ==========================================
        // CATEGORÍA 9: EQUIPOS DE ELEVACIÓN
        // ==========================================
        
       
        
        
        // ==========================================
        // CATEGORÍA 10: EQUIPOS DE CONSTRUCCIÓN
        // ==========================================
        
       

}

}