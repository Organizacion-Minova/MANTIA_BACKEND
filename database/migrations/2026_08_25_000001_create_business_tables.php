<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

use function Laravel\Prompts\table;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('category_group', function (Blueprint $table) {
            $table->id();
            $table->string('name', 45);
        });

        Schema::create('category', function (Blueprint $table) {
            $table->id();
            $table->string('name', 50)->unique();
            $table->text('description');
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
            $table->enum('status', ['active', 'inactive'])->default('active');
        });

        Schema::create('location', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->text('description');
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->foreignId('location_category_id')->constrained('location_category');
        });

        Schema::create('equipment', function (Blueprint $table) {
            $table->id();
            $table->string('code', 20)->unique();
            $table->string('name', 100);
            $table->string('model', 20);
            $table->string('serial_number', 20)->unique();
            $table->string('responsible', 100);
            $table->enum('status', ['active', 'inactive', 'maintenance'])->default('active');
            $table->date('start_up_date');
            $table->string('brand', 100);
            $table->string('plate', 30)->nullable();
            $table->string('fuel', 50)->nullable();
            $table->string('power', 100)->nullable();
            $table->string('voltage', 50)->nullable();
            $table->foreignId('location_id')->constrained('location');
            $table->string('image')->nullable();
            $table->text('description');
           
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
            $table->string('image')->nullable();
            $table->foreignId('location_id')->constrained('location');
            $table->foreignId('machine_category_id')->constrained('machine_category');
            $table->string('brand', 100);
            $table->string('responsible', 45);
            $table->string('machine_usage', 45);
            $table->boolean('in_operation')->default(true);
            $table->text('characteristics');
        });

        Schema::create('tool', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->foreignId('location_id')->constrained('location');
            $table->foreignId('category_id')->constrained('category');
        });

        Schema::create('tool_stock_condition', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tool_id')->constrained('tool')->onDelete('cascade');
            $table->string('condition', 20);
            $table->integer('stock')->default(0);
            $table->unique(['tool_id', 'condition']);
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

        Schema::create('daily_use', function (Blueprint $table) {
            $table->id();
            $table->date('date');
            $table->string('operating_status', 50);
            $table->string('operation_start', 50)->nullable();
            $table->string('operation_end', 50)->nullable();
            $table->string('responsible', 100)->nullable();
            $table->string('observations', 255)->nullable();
            $table->foreignId('machine_id')->constrained('machine');
        });
        Schema::create('inspection_question', function (Blueprint $table) {
            $table->id();
            $table->foreignId('machine_id')->constrained('machine');
            $table->string('text');
            $table->enum('type', ['yes_no_na'])->default('yes_no_na');
            $table->unsignedSmallInteger('position')->default(0);
            $table->boolean('is_active')->default(true);
        });
        Schema::create('inspection', function (Blueprint $table) {
            $table->id();
            $table->foreignId('machine_id')->constrained('machine');
            $table->date('inspection_date');
            $table->unsignedSmallInteger('duration_minutes')->nullable();
            $table->string('inspector');
            $table->string('reviewed_by')->nullable();
            $table->date('review_date')->nullable();
            $table->text('general_observations')->nullable();
            $table->string('signature')->nullable();

        });
        Schema::create('inspection_answer', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inspection_id')->constrained('inspection')->cascadeOnDelete();
            $table->foreignId('question_id')->constrained('inspection_question');
            $table->enum('answer', ['yes', 'no', 'na'])->nullable();
            $table->text('observation')->nullable();

            $table->unique(['inspection_id', 'question_id']);
        });
        Schema::create('inspection_format', function (Blueprint $table) {
            $table->id();
            $table->foreignId('equipment_id')->constrained('equipment');                // "martillo neumático"
            $table->string('code')->nullable();                // ej. HSE-F-035
            $table->string('version')->nullable();
            $table->date('valid_from')->nullable();            // "vigente desde" / "fecha de emisión"
            $table->boolean('numbered')->default(false);
            $table->boolean('has_general_observations')->default(false);
            $table->boolean('is_active')->default(true);
        });
        Schema::create('inspection_section', function (Blueprint $table) {
            $table->id();
            $table->foreignId('format_id')->constrained('inspection_format')->cascadeOnDelete();
            $table->string('title');
            $table->unsignedSmallInteger('position')->default(1);
            $table->string('mode')->default('daily');          // daily | weekly
            $table->json('options');                           // ["B","M"], ["C","NC","N/A"], ["SI","NO"]
            $table->string('instructions')->nullable();
            $table->boolean('has_observation')->default(false);
        });

        Schema::create('inspection_question', function (Blueprint $table) {
            $table->id();
            $table->foreignId('section_id')->constrained('inspection_section')->cascadeOnDelete();
            $table->string('text');
            $table->unsignedSmallInteger('position')->default(1);   // se reinicia en cada sección
            $table->boolean('is_active')->default(true);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('daily_use');
        Schema::dropIfExists('gas_measurement_location');
        Schema::dropIfExists('gas_measurement');
        Schema::dropIfExists('inspection');
        Schema::dropIfExists('inspection_question');
        Schema::dropIfExists('inspection_answer');
        Schema::dropIfExists('inspection_question');
        Schema::dropIfExists('inspection_section');
        Schema::dropIfExists('inspection_format');
        Schema::dropIfExists('tool');
        Schema::dropIfExists('tool_stock_condition');
        Schema::dropIfExists('machine');
        Schema::dropIfExists('equipment');
        Schema::dropIfExists('location');
        Schema::dropIfExists('location_category');
        Schema::dropIfExists('machine_category');
        Schema::dropIfExists('category');
        Schema::dropIfExists('category_group');
    }
};
