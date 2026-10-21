<?php
// Sección de estados de cada card del dashboard
namespace App\Dashboard;

interface DashboardSection
{
    /** Clave en camelCase que lleva en el JSON (p. ej. "dailyUsage"). */
    public function key(): string;

    public function resolve(): SectionResult;
}