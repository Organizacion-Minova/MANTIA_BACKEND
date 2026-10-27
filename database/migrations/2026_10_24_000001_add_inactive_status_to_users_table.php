<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Agrega el estado "inactive": la cuenta sigue existiendo pero no puede iniciar sesión.
     * El administrador lo usa para desactivar usuarios sin borrarlos (desde MANTIA IA).
     */
    public function up(): void
    {
        $this->permitirEstados(['pending', 'approved', 'rejected', 'inactive']);
    }

    public function down(): void
    {
        // Las cuentas desactivadas vuelven a quedar aprobadas para no perder usuarios
        DB::table('users')->where('status', 'inactive')->update(['status' => 'approved']);

        $this->permitirEstados(['pending', 'approved', 'rejected']);
    }

    /**
     * Cada base de datos guarda el enum distinto: PostgreSQL con una restricción CHECK
     * (users_status_check), MySQL con un tipo ENUM. SQLite (pruebas) lo resuelve Laravel.
     */
    private function permitirEstados(array $estados): void
    {
        $lista = "'" . implode("', '", $estados) . "'";

        switch (DB::getDriverName()) {
            case 'pgsql':
                DB::statement('ALTER TABLE users DROP CONSTRAINT IF EXISTS users_status_check');
                DB::statement("ALTER TABLE users ADD CONSTRAINT users_status_check CHECK (status IN ({$lista}))");
                break;

            case 'mysql':
            case 'mariadb':
                DB::statement("ALTER TABLE users MODIFY status ENUM({$lista}) NOT NULL DEFAULT 'pending'");
                break;

            default:
                Schema::table('users', function (Blueprint $table) use ($estados) {
                    $table->enum('status', $estados)->default('pending')->change();
                });
        }
    }
};