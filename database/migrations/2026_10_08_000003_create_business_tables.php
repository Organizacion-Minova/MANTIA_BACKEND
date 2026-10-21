<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Tablas anteriores (versión de antigua migración)
        Schema::dropIfExists('gas_measurement_location');
        Schema::dropIfExists('gas_measurement');

        // Formato diario general
        Schema::create('gas_measurement', function (Blueprint $table) {
            $table->id();
            $table->date('measurement_date');
            $table->time('measurement_time', precision: 0)->nullable();
            $table->binary('chief_signature')->nullable();
            $table->binary('sst_signature')->nullable();  
            $table->timestamps();
        });

        // Detalle por cada ubicación
        Schema::create('gas_measurement_detail', function (Blueprint $table) {
            $table->id();
           
            $table->foreignId('gas_measurement_id')
                  ->constrained('gas_measurement')
                  ->onDelete('cascade');

            $table->foreignId('location_id')
                  ->constrained('location')
                  ->onDelete('restrict');

            $table->decimal('oxygen', 5, 2)->nullable();
            $table->decimal('methane', 5, 2)->nullable();
            $table->decimal('carbon_dioxide', 5, 2)->nullable();
            $table->decimal('hydrogen_sulfide', 5, 2)->nullable();
            $table->decimal('carbon_monoxide', 5, 2)->nullable();
            $table->decimal('nitrogen_dioxide', 5, 2)->nullable();
            $table->string('responsible_name', 100)->nullable();
            $table->string('observation', 100)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gas_measurement_detail');
        Schema::dropIfExists('gas_measurement');
    }
};
