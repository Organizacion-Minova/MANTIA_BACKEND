<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('users', 'dev_letter')) {
            return;
        }

        DB::table('users')->whereNotNull('dev_letter')->update(['status' => 'inactive']);
        DB::table('users')->where('email', 'test@mantia.com')->update(['status' => 'inactive']);

        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique('users_dev_letter_unique');
            $table->dropColumn('dev_letter');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('dev_letter', 1)->nullable()->unique()->after('id');
        });
    }
};
