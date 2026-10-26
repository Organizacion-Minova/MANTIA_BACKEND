<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class CleanupUnverifiedUsers extends Command
{
    protected $signature = 'users:cleanup-unverified';
    protected $description = 'Borra cuentas del registro que no verificaron el correo en 24 horas';

    public function handle(): int
    {
        $borrados = User::whereNull('email_verified_at')
            ->whereNotNull('verification_expires_at')
            ->where('verification_expires_at', '<', now())
            ->delete();

        $this->info("Cuentas sin verificar borradas: {$borrados}");

        return self::SUCCESS;
    }
}