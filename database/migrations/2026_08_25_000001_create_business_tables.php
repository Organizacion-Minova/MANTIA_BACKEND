<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('company', function (Blueprint $table) {
            $table->integer('tax_id')->primary();
            $table->string('name', 55);
            $table->string('phone', 20);
            $table->string('email', 100);
            $table->string('address', 100);
        });

        Schema::create('category_group', function (Blueprint $table) {
            $table->id();
            $table->string('name', 45);
        });

        Schema::create('category', function (Blueprint $table) {
            $table->id();
            $table->string('name', 50)->unique();
            $table->string('status', 45);
            $table->foreignId('category_group_id')->constrained('category_group');
        });

        Schema::create('machine_category', function (Blueprint $table) {
            $table->id();
            $table->string('name', 45);
            $table->text('description');
            $table->enum('status', ['active', 'inactive'])->default('active');
        });

        Schema::create('location_category', function (Blueprint $table) {
            $table->id();
            $table->string('name', 45);
            $table->text('description');
        });

        Schema::create('location', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->text('description')->nullable();
            $table->string('status', 20)->default('active');
            $table->foreignId('location_category_id')->constrained('location_category');
        });

        Schema::create('equipment', function (Blueprint $table) {
            $table->id();
            $table->string('code', 20)->unique();
            $table->string('name', 100);
            $table->text('description');
            $table->string('serial_number', 20)->unique();
            $table->string('responsible', 100);
            $table->enum('status', ['active', 'inactive', 'maintenance'])->default('active');
            $table->date('registration_date');
            $table->integer('company_id');
            $table->foreignId('location_id')->constrained('location');
            $table->binary('image');
            $table->string('model', 20);

            $table->foreign('company_id')->references('tax_id')->on('company');
        });

        Schema::create('machine', function (Blueprint $table) {
            $table->id();
            $table->string('code', 20)->unique();
            $table->string('name', 100);
            $table->string('model', 80)->nullable();
            $table->string('serial_number', 100)->nullable();
            $table->date('acquisition_date')->nullable();
            $table->decimal('acquisition_cost', 15, 2)->nullable();
            $table->enum('status', ['active', 'inactive', 'maintenance'])->default('active');
            $table->integer('warranty')->nullable();
            $table->binary('image')->nullable();
            $table->foreignId('location_id')->constrained('location');
            $table->foreignId('machine_category_id')->constrained('machine_category');
            $table->integer('company_id');
            $table->string('responsible', 45);
            $table->string('machine_usage', 45);
            $table->boolean('in_operation')->default(true);
            $table->text('characteristics');

            $table->foreign('company_id')->references('tax_id')->on('company');
        });

        Schema::create('tool', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->enum('condition', ['good', 'fair', 'poor']);
            $table->integer('stock')->default(0);
            $table->string('status', 20);
            $table->foreignId('location_id')->constrained('location');
            $table->foreignId('category_id')->constrained('category');
        });

        Schema::create('inspection', function (Blueprint $table) {
            $table->id();
            $table->dateTime('inspection_date');
            $table->time('duration');
            $table->string('area', 50);
            $table->string('equipment_name', 50);
            $table->string('reviewed_by', 50);
            $table->dateTime('review_date');
            $table->text('review_table');
            $table->text('observations');
            $table->binary('signature')->nullable();
            $table->foreignId('machine_id')->constrained('machine');
        });

        Schema::create('gas_measurement', function (Blueprint $table) {
            $table->id();
            $table->date('measurement_date');
            $table->time('measurement_time');
            $table->string('observation', 100)->nullable();
            $table->string('location', 100)->nullable();
            $table->decimal('oxygen', 5, 2)->nullable();
            $table->decimal('methane', 5, 2)->nullable();
            $table->decimal('carbon_dioxide', 5, 2)->nullable();
            $table->decimal('hydrogen_sulfide', 5, 2)->nullable();
            $table->decimal('carbon_monoxide', 5, 2)->nullable();
            $table->decimal('nitrogen_dioxide', 5, 2)->nullable();
            $table->binary('responsible_signature')->nullable();
        });

        Schema::create('gas_measurement_location', function (Blueprint $table) {
            $table->foreignId('gas_measurement_id')->constrained('gas_measurement');
            $table->foreignId('location_id')->constrained('location');
            $table->primary(['gas_measurement_id', 'location_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gas_measurement_location');
        Schema::dropIfExists('gas_measurement');
        Schema::dropIfExists('inspection');
        Schema::dropIfExists('tool');
        Schema::dropIfExists('machine');
        Schema::dropIfExists('equipment');
        Schema::dropIfExists('location');
        Schema::dropIfExists('location_category');
        Schema::dropIfExists('machine_category');
        Schema::dropIfExists('category');
        Schema::dropIfExists('category_group');
        Schema::dropIfExists('company');
    }
};
