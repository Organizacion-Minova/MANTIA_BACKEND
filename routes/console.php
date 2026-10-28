<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Alertas preventivas de la medición de gases (hora de Colombia).
// Solo se envían si la medición del día todavía no se ha hecho.
Schedule::command('gases:recordar 1')->dailyAt('08:00')->timezone('America/Bogota');
Schedule::command('gases:recordar 2')->dailyAt('11:00')->timezone('America/Bogota');
Schedule::command('gases:recordar 3')->dailyAt('14:00')->timezone('America/Bogota');