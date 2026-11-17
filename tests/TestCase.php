<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use RuntimeException;

abstract class TestCase extends BaseTestCase
{
    /**
     * Barrera de seguridad: las pruebas borran y recrean tablas (RefreshDatabase).
     * Si por error apuntaran a la base de datos real (PostgreSQL), se detienen antes de tocar nada.
     */
    public function createApplication()
    {
        $app = parent::createApplication();

        $conexion = $app['config']->get('database.default');
        $base = $app['config']->get("database.connections.{$conexion}.database");

        if ($conexion !== 'sqlite' || $base !== ':memory:') {
            throw new RuntimeException(
                "Pruebas detenidas: deben usar SQLite en memoria y están apuntando a '{$conexion}' ({$base})."
            );
        }

        return $app;
    }
}
