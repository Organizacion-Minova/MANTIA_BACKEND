<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('last_name')->nullable()->after('name');
            $table->string('document_type', 5)->nullable()->after('last_name');
            $table->string('document_number', 20)->nullable()->unique()->after('document_type');
            $table->string('requested_role', 30)->nullable()->after('status');
            $table->string('technician_type', 30)->nullable()->after('requested_role');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['last_name', 'document_type', 'document_number', 'requested_role', 'technician_type']);
        });
    }
};