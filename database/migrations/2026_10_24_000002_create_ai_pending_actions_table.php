<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Acciones que MANTIA IA le propone a un administrador (crear rol, desactivar usuario...).
     * Solo se ejecutan cuando el administrador pulsa "Confirmar" en el chat.
     * La tabla también sirve de historial: quién pidió qué y cuándo se hizo o se canceló.
     */
    public function up(): void
    {
        Schema::create('ai_pending_actions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('type', 50);
            $table->json('payload');
            $table->string('summary', 255);
            $table->timestamp('expires_at');
            $table->timestamp('executed_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_pending_actions');
    }
};