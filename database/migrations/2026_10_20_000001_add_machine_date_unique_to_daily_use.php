<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('daily_use', function (Blueprint $table) {
            $table->unique(['date', 'machine_id'], 'daily_use_date_machine_unique');
        });
    }

    public function down(): void
    {
        Schema::table('daily_use', function (Blueprint $table) {
            $table->dropUnique('daily_use_date_machine_unique');
        });
    }
};
