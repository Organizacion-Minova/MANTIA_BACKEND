<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB; 

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('company', function (Blueprint $table) {
            $table->bigInteger('tax_id')->change();
            $table->string('nit', 20)->unique();
        });

        DB::statement('ALTER TABLE public.company ALTER COLUMN tax_id ADD GENERATED ALWAYS AS IDENTITY;');
    }

    public function down(): void
    {
        Schema::table('company', function (Blueprint $table) {
            $table->integer('tax_id')->change();
            $table->dropColumn('nit');
        });

        DB::statement('ALTER TABLE public.company ALTER COLUMN tax_id DROP IDENTITY IF EXISTS;');
    }
};

