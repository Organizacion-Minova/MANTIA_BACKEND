<?php

namespace Database\Seeders;

use App\Models\Equipment;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class EquipmentsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Equipment::firstOrCreate(
            ['code'=>'T1-BT02'],
            [
                'name'=>'PLANTA ELECTRICA  ',
                'model'=>'6BT5. 9-62',
                'serial_number'=>'78223950',
                'responsible'=>'Técnicos mina',
                'status'=>'active',
                'start_up_date'=>'22/08/2023',
                'brand'=>'Dongfeng Cummins',
                'plate'=>'9999',
                'fuel'=>'Diesel',
                'power'=>'86 a 1500  r/min, 100 a 1800 r/ min, 92 a 1500 r/min, 110 a 1800 r/min ',
                'voltage'=>'nn',
                'location_id'=>1,
                'image'=>'codes/Equipments/T1-BT02.jpg',
                'description'=>'DESPLAZAMIENTO: 5.9 L
                                PESO: 411 KG
                                LIMITE DE ALTITUD: M
                                CLP:  3076
                                RELANTI: 800 R/ MIN  
                                TASA DE COMBUSTIBLE : 85 mm/',
                
            ]

        );
        Equipment::firstOrCreate(
            ['code' => 'T1-SO01',],
            [
                'name' => 'Equipo de soldadura',
                'model' => 'POR_DEFINIR',
                'serial_number' => '22222222',
                'responsible' => 'Técnicos mina',
                'status' => 'active',
                'start_up_date' => '22/08/2023',
                'brand' => 'Minova',
                'plate' => '5565895',
                'fuel' => Null,
                'power' => Null,
                'voltage' => Null,
                'location_id' => 1,
                'image' => 'codes/Equipments/T1-SO01.jpg',
                'description' => 'Equipo de soldadura utilizado para realizar trabajos de mantenimiento y reparación de maquinaria minera.',
            ]
        );
        Equipment::firstOrCreate(
            ['code' => 'T1-TA01'],
            [
                'name' => 'Taladro de mano',
                'model' => 'POR_DEFINIR',
                'serial_number' => '222222',
                'responsible' => 'Técnicos mina',
                'status' => 'active',
                'start_up_date' => '22/08/2023',
                'brand' => 'POR_DEFINIR',
                'plate' => 'POR_DEFINIR',
                'fuel' => null,
                'power' => null,
                'voltage' => null,
                'location_id' => 1,
                'image' => 'codes/Equipments/T1-TA01.jpg',
                'description' => 'Taladro utilizado para perforación y trabajos de mantenimiento mecánico en las instalaciones de la mina.',
            ]
        );
        Equipment::firstOrCreate(
            ['code' => 'T1-PU01'],
            [
                'name' => 'Pulidora de mano',
                'model' => 'POR_DEFINIR',
                'serial_number' => '111111',
                'responsible' => 'Técnicos mina',
                'status' => 'active',
                'start_up_date' => '22/08/2023',
                'brand' => 'POR_DEFINIR',
                'plate' => 'POR_DEFINIR',
                'fuel' => null,
                'power' => null,
                'voltage' => null,
                'location_id' => 1,
                'image' => 'codes/Equipments/T1-PU01.jpg',
                'description' => 'Pulidora angular utilizada para corte y desbaste de metales en labores de mantenimiento.',
            ]
        );
        Equipment::firstOrCreate(
            ['code' => 'T1-MN01'],
            [
                'name' => 'Martillo neumático',
                'model' => 'POR_DEFINIR',
                'serial_number' => '333333333',
                'responsible' => 'Técnicos mina',
                'status' => 'active',
                'start_up_date' => '22/08/2023',
                'brand' => 'POR_DEFINIR',
                'plate' => 'POR_DEFINIR',
                'fuel' => null,
                'power' => null,
                'voltage' => null,
                'location_id' => 1,
                'image' => 'codes/Equipments/T1-MN01.jpg',
                'description' => 'Martillo neumático utilizado para demolición, rompimiento de roca y labores de excavación en la mina.',
            ]
        );

        Equipment::firstOrCreate(
            ['code'=>'T1-BT03'],
            [
                'name'=>'COMPRESOR DE AIRE',
                'model'=>'XAS 185',
                'serial_number'=>'78223951',
                'responsible'=>'Técnicos mina',
                'status'=>'active',
                'start_up_date'=>'15/03/2023',
                'brand'=>'Atlas Copco',
                'plate'=>'1001',
                'fuel'=>'Diesel',
                'power'=>'49 kW',
                'voltage'=>'220 V',
                'location_id'=>1,
                'image'=>'codes/Equipments/T1-BT03.jpg',
                'description'=>'PRESION DE TRABAJO: 7 bar
                            CAUDAL DE AIRE: 5.2 m3/min
                            MOTOR: Diesel
                            CAPACIDAD DEL TANQUE: 80 L',
            ]
        );

        Equipment::firstOrCreate(
            ['code'=>'T1-BT04'],
            [
                'name'=>'MOTOBOMBA DE AGUA',
                'model'=>'WB30XT',
                'serial_number'=>'78223952',
                'responsible'=>'Técnicos mina',
                'status'=>'active',
                'start_up_date'=>'10/04/2023',
                'brand'=>'Honda',
                'plate'=>'1002',
                'fuel'=>'Gasolina',
                'power'=>'5.5 HP',
                'voltage'=>'110 V',
                'location_id'=>1,
                'image'=>'codes/Equipments/T1-BT04.jpg',
                'description'=>'DIAMETRO DE SUCCION: 3 pulgadas
                            DIAMETRO DE DESCARGA: 3 pulgadas
                            CAUDAL MAXIMO: 1100 L/min
                            ALTURA MAXIMA: 23 m',
            ]
        );

            Equipment::firstOrCreate(
                ['code'=>'T1-BT05'],
                [
                    'name'=>'SOLDADORA ELECTRICA',
                    'model'=>'ARC 250',
                    'serial_number'=>'78223953',
                    'responsible'=>'Técnicos mina',
                    'status'=>'active',
                    'start_up_date'=>'05/05/2023',
                    'brand'=>'Lincoln Electric',
                    'plate'=>'1003',
                    'fuel'=>'Eléctrica',
                    'power'=>'250 A',
                    'voltage'=>'220 V',
                    'location_id'=>1,
                    'image'=>'codes/Equipments/T1-BT05.jpg',
                    'description'=>'CORRIENTE DE SALIDA: 250 A
                                CICLO DE TRABAJO: 60%
                                VOLTAJE EN VACIO: 65 V
                                TIPO DE SOLDADURA: MMA',
                ]
            );
            
            Equipment::firstOrCreate(
                ['code'=>'T1-BT06'],
                [
                    'name'=>'TALADRO DE BANCO',
                    'model'=>'DP16SL',
                    'serial_number'=>'78223954',
                    'responsible'=>'Técnicos mina',
                    'status'=>'active',
                    'start_up_date'=>'12/06/2023',
                    'brand'=>'Truper',
                    'plate'=>'1004',
                    'fuel'=>'Eléctrica',
                    'power'=>'550 W',
                    'voltage'=>'110 V',
                    'location_id'=>1,
                    'image'=>'codes/Equipments/T1-BT06.jpg',
                    'description'=>'CAPACIDAD DE PERFORACION: 16 mm
                                VELOCIDADES: 5
                                ALTURA: 840 mm
                                TIPO: Banco',
                ]
            );
            
            Equipment::firstOrCreate(
                ['code'=>'T1-BT07'],
                [
                    'name'=>'MOTOCOMPRESOR',
                    'model'=>'P185WJD',
                    'serial_number'=>'78223955',
                    'responsible'=>'Técnicos mina',
                    'status'=>'active',
                    'start_up_date'=>'20/07/2023',
                    'brand'=>'Ingersoll Rand',
                    'plate'=>'1005',
                    'fuel'=>'Diesel',
                    'power'=>'49 kW',
                    'voltage'=>'12 V',
                    'location_id'=>1,
                    'image'=>'codes/Equipments/T1-BT07.jpg',
                    'description'=>'PRESION MAXIMA: 7 bar
                                CAUDAL: 5.2 m3/min
                                MOTOR: Diesel
                                SISTEMA DE REFRIGERACION: Aire',
                ]
            );
            
            Equipment::firstOrCreate(
                ['code'=>'T1-BT08'],
                [
                    'name'=>'GENERADOR ELECTRICO',
                    'model'=>'DG6500E',
                    'serial_number'=>'78223956',
                    'responsible'=>'Técnicos mina',
                    'status'=>'active',
                    'start_up_date'=>'18/08/2023',
                    'brand'=>'Hyundai',
                    'plate'=>'1006',
                    'fuel'=>'Diesel',
                    'power'=>'5.0 kW',
                    'voltage'=>'220 V',
                    'location_id'=>1,
                    'image'=>'codes/Equipments/T1-BT08.jpg',
                    'description'=>'POTENCIA MAXIMA: 5.5 kW
                                POTENCIA NOMINAL: 5.0 kW
                                FRECUENCIA: 60 Hz
                                ARRANQUE: Electrico',
                ]
            );
            
            Equipment::firstOrCreate(
                ['code'=>'T1-BT09'],
                [
                    'name'=>'ESMERIL DE BANCO',
                    'model'=>'GB602',
                    'serial_number'=>'78223957',
                    'responsible'=>'Técnicos mina',
                    'status'=>'active',
                    'start_up_date'=>'25/08/2023',
                    'brand'=>'Makita',
                    'plate'=>'1007',
                    'fuel'=>'Eléctrica',
                    'power'=>'250 W',
                    'voltage'=>'110 V',
                    'location_id'=>1,
                    'image'=>'codes/Equipments/T1-BT09.jpg',
                    'description'=>'DIAMETRO DE DISCO: 150 mm
                                VELOCIDAD: 2850 RPM
                                POTENCIA: 250 W
                                USO: Desbaste y afilado',
                ]
            );
            
            Equipment::firstOrCreate(
                ['code'=>'T1-BT10'],
                [
                    'name'=>'MEZCLADORA DE CONCRETO',
                    'model'=>'CM14',
                    'serial_number'=>'78223958',
                    'responsible'=>'Técnicos mina',
                    'status'=>'active',
                    'start_up_date'=>'14/09/2023',
                    'brand'=>'Menegotti',
                    'plate'=>'1008',
                    'fuel'=>'Gasolina',
                    'power'=>'6.5 HP',
                    'voltage'=>'N/A',
                    'location_id'=>1,
                    'image'=>'codes/Equipments/T1-BT10.jpg',
                    'description'=>'CAPACIDAD DEL TAMBOR: 400 L
                                CAPACIDAD DE MEZCLA: 280 L
                                MOTOR: Gasolina
                                USO: Mezcla de concreto',
                ]
            );
            
            Equipment::firstOrCreate(
                ['code'=>'T1-BT11'],
                [
                    'name'=>'MARTILLO NEUMATICO',
                    'model'=>'TEX 230',
                    'serial_number'=>'78223959',
                    'responsible'=>'Técnicos mina',
                    'status'=>'active',
                    'start_up_date'=>'30/09/2023',
                    'brand'=>'Atlas Copco',
                    'plate'=>'1009',
                    'fuel'=>'Aire comprimido',
                    'power'=>'Energía neumática',
                    'voltage'=>'N/A',
                    'location_id'=>1,
                    'image'=>'codes/Equipments/T1-BT11.jpg',
                    'description'=>'PRESION DE TRABAJO: 6 bar
                                FRECUENCIA DE IMPACTO: 1500 golpes/min
                                TIPO: Neumático
                                USO: Demolición y excavación',
                ]
            );
            
            Equipment::firstOrCreate(
                ['code'=>'T1-BT12'],
                [
                    'name'=>'MONTACARGAS',
                    'model'=>'FD25T',
                    'serial_number'=>'78223960',
                    'responsible'=>'Técnicos mina',
                    'status'=>'active',
                    'start_up_date'=>'10/10/2023',
                    'brand'=>'Komatsu',
                    'plate'=>'1010',
                    'fuel'=>'Diesel',
                    'power'=>'36.5 kW',
                    'voltage'=>'12 V',
                    'location_id'=>1,
                    'image'=>'codes/Equipments/T1-BT12.jpg',
                    'description'=>'CAPACIDAD DE CARGA: 2500 kg
                                ALTURA DE ELEVACION: 3000 mm
                                TIPO DE TRANSMISION: Automática
                                TIPO DE MOTOR: Diesel',
                ]
            );

    }
}
