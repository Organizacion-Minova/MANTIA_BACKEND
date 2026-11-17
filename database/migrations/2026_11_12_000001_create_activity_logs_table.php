<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Historial de cambios (auditoría): quién cambió qué, cuándo, y los valores antes/después.
 * El Superadministrador puede revertir desde aquí.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('activity_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->string('user_name', 120)->nullable();
            $table->string('user_role', 60)->nullable()->index();
            // created | updated | deleted | reverted | role_changed | password_reset | backup_* ...
            $table->string('action', 40)->index();
            $table->string('module', 60)->index();
            $table->string('auditable_type', 150)->nullable();
            $table->string('auditable_id', 60)->nullable();
            $table->string('description', 500);
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();
            // Filas que dependían del registro eliminado (para poder devolverlas al revertir)
            $table->json('extra')->nullable();
            $table->boolean('revertible')->default(false);
            $table->timestamp('reverted_at')->nullable();
            $table->unsignedBigInteger('reverted_by')->nullable();
            $table->unsignedBigInteger('revert_of')->nullable()->index();
            $table->string('ip', 45)->nullable();
            $table->timestamp('created_at')->useCurrent()->index();

            $table->index(['auditable_type', 'auditable_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_logs');
    }
};
