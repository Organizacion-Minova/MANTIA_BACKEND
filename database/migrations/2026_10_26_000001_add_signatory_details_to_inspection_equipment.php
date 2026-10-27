<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('inspection_equipment', function (Blueprint $table) {
            $table->string('responsible_position')->nullable();
            $table->string('warehouse_manager_name')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('inspection_equipment', function (Blueprint $table) {
            $table->dropColumn(['responsible_position', 'warehouse_manager_name']);
        });
    }
};
