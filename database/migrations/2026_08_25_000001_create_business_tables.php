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
            $table->string('name', 100)->unique();
            $table->string('model', 20);
            $table->string('serial_number', 20)->unique();
            $table->string('responsible', 100);
            $table->enum('status', ['Activo', 'Inactivo', 'Mantenimiento'])->default('Activo');
            $table->date('start_up_date')->nullable();
            $table->string('brand', 100);
            $table->string('plate', 100);
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
            $table->string('name', 100)->unique();
            $table->string('model', 80)->nullable();
            $table->string('serial_number', 100)->nullable();
            $table->string('responsible', 45);
            $table->enum('status', ['Activo', 'Inactivo', 'Mantenimiento'])->default('Activo');
            $table->date('start_up_date')->nullable();
            $table->string('brand', 100);
            $table->string('plate', 100);
            $table->string('fuel', 50)->nullable();
            $table->string('power', 100)->nullable();
            $table->string('voltage', 50)->nullable();
            $table->foreignId('location_id')->constrained('location');
            $table->foreignId('machine_category_id')->constrained('machine_category');
            $table->string('image')->nullable();
            $table->text('characteristics')->nullable();
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
        Schema::create('inspection_format_machine', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->constrained('machine_category');
            $table->string('code')->nullable();
            $table->string('version')->nullable();
            $table->date('valid_from')->nullable();
            $table->boolean('is_active')->default(true);
        });

        Schema::create('inspection_section_machine', function (Blueprint $table) {
            $table->id();
            $table->foreignId('format_id')->constrained('inspection_format_machine')->cascadeOnDelete();
            $table->string('title');
            $table->unsignedSmallInteger('position')->default(1);
        });
        Schema::create('inspection_question_machine', function (Blueprint $table) {
            $table->id();
            $table->foreignId('section_id')->constrained('inspection_section_machine')->cascadeOnDelete();
            $table->string('type')->default('item'); 
            $table->string('text');
            $table->unsignedSmallInteger('position')->default(1); 
            $table->boolean('is_active')->default(true);
        });
        Schema::create('preoperational_machine', function (Blueprint $table) {
            $table->id();
            $table->foreignId('machine_id')->constrained('machine');
            $table->foreignId('format_id')->constrained('inspection_format_machine');
            $table->date('week_start');
            $table->date('week_end');
            $table->string('inspector');
            $table->string('area')->nullable();
            $table->unsignedSmallInteger('duration_minutes')->nullable();
            $table->string('reviewed_by')->nullable();
            $table->date('review_date')->nullable();
            $table->text('general_observations')->nullable();
            $table->string('signature')->nullable();

            $table->unique(['machine_id', 'week_start']);
        });
        Schema::create('preoperational_answer_machine', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inspection_id')->constrained('preoperational_machine')->cascadeOnDelete();
            $table->foreignId('question_id')->constrained('inspection_question_machine');
            $table->unsignedTinyInteger('day');           // 1 = lunes ... 7 = domingo
            $table->string('value', 5);                   // SI | NO | N/A

            $table->unique(['inspection_id', 'question_id', 'day'], 'preop_answer_machine_unique');
        });
        Schema::create('inspection_machine', function (Blueprint $table) {
            $table->id();
            $table->foreignId('machine_id')->constrained('machine');
            $table->foreignId('format_id')->constrained('inspection_format_machine');
            $table->date('inspection_date');
            $table->string('inspector');
            $table->string('area')->nullable();
            $table->unsignedSmallInteger('duration_minutes')->nullable();
            $table->string('reviewed_by')->nullable();
            $table->date('review_date')->nullable();
            $table->text('general_observations')->nullable();
            $table->string('signature')->nullable();

            $table->unique(['machine_id', 'inspection_date']);
        });
        Schema::create('inspection_answer_machine', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inspection_id')->constrained('inspection_machine')->cascadeOnDelete();
            $table->foreignId('question_id')->constrained('inspection_question_machine');
            $table->string('value', 5)->nullable();
            $table->text('observation')->nullable();

            $table->unique(['inspection_id', 'question_id']);
        });
        Schema::create('inspection_format_equipment', function (Blueprint $table) {
            $table->id();
            $table->foreignId('equipment_id')->constrained('equipment');
            $table->string('code')->nullable();
            $table->string('version')->nullable();
            $table->date('valid_from')->nullable(); 
            $table->boolean('numbered')->default(false);
            $table->boolean('has_general_observations')->default(false);
            $table->boolean('is_active')->default(true);
            $table->json('header_fields')->nullable();
            $table->json('signers')->nullable();
        });
        Schema::create('inspection_section_equipment', function (Blueprint $table) {
            $table->id();
            $table->foreignId('format_id')->constrained('inspection_format_equipment')->cascadeOnDelete();
            $table->string('title');
            $table->unsignedSmallInteger('position')->default(1);
            $table->string('mode')->default('daily');          // daily | weekly
            $table->json('options');                           // ["B","M"], ["C","NC","N/A"], ["SI","NO"]
            $table->string('instructions')->nullable();
            $table->boolean('has_observation')->default(false);
        });

        Schema::create('inspection_question_equipment', function (Blueprint $table) {
            $table->id();
            $table->foreignId('section_id')->constrained('inspection_section_equipment')->cascadeOnDelete();
            $table->string('text');
            $table->unsignedSmallInteger('position')->default(1);   // se reinicia en cada sección
            $table->boolean('is_active')->default(true);
        });
        Schema::create('inspection_equipment', function (Blueprint $table) {
            $table->id();
            $table->foreignId('format_id')->constrained('inspection_format_equipment');
            $table->date('week_start');
            $table->date('week_end');
            $table->json('header_data')->nullable();
            $table->text('general_observations')->nullable();

            $table->unique(['format_id', 'week_start']);
            });
        Schema::create('inspection_answer_equipment', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inspection_id')->constrained('inspection_equipment')->cascadeOnDelete();
            $table->foreignId('question_id')->constrained('inspection_question_equipment');
            $table->unsignedTinyInteger('day')->default(0);   // 0 = toda la semana, 1-7 = lun-dom
            $table->string('value', 10)->nullable();
            $table->text('observation')->nullable();

            $table->unique(['inspection_id', 'question_id', 'day']);
        });
        Schema::create('inspection_signature_equipment', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inspection_id')->constrained('inspection_equipment')->cascadeOnDelete();
            $table->string('role', 30);                       // inspector | collaborator | reviewer
            $table->string('name')->nullable();
            $table->string('id_number', 30)->nullable();
            $table->string('signature')->nullable();
            $table->unique(['inspection_id', 'role']);
        });
        Schema::create('tools_machine', function (Blueprint $table){
            $table->id();
            $table->foreignId('tool_id')->constrained('tool');
            $table->string('brand', 100)->nullable();
            $table->unsignedInteger('stock')->default(0);
            $table->text('observations')->nullable();
            $table->foreignId('machine_id')->constrained('machine')->cascadeOnDelete();
            $table->unique(['tool_id', 'machine_id']);
        });
    }


    public function down(): void
    {
        Schema::dropIfExists('tools_machine');
        Schema::dropIfExists('daily_use');
        Schema::dropIfExists('gas_measurement_location');
        Schema::dropIfExists('gas_measurement');
        Schema::dropIfExists('inspection');
        Schema::dropIfExists('inspection_question');
        Schema::dropIfExists('inspection_answer');
        Schema::dropIfExists('inspection_question_equipment');
        Schema::dropIfExists('inspection_section_equipment');
        Schema::dropIfExists('inspection_format_equipment');
        Schema::dropIfExists('inspection_equipment');
        Schema::dropIfExists('inspection_answer_equipment');
        Schema::dropIfExists('inspection_signature_equipment');
        Schema::dropIfExists('preoperational_answer_machine');
        Schema::dropIfExists('preoperational_machine');
        Schema::dropIfExists('inspection_answer_machine');
        Schema::dropIfExists('inspection_machine');
        Schema::dropIfExists('inspection_question_machine');
        Schema::dropIfExists('inspection_section_machine');
        Schema::dropIfExists('inspection_format_machine');
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
