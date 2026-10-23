<?php

namespace App\Services\Ai;

use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * Traduce la pantalla en la que está el usuario (la ruta del frontend,
 * p. ej. "/machines/cardMachine/5") a una frase que la IA entiende:
 * "El usuario está viendo la ficha de la máquina Electrobomba 2 (código PT2-EL02)".
 *
 * Así, cuando el usuario pregunta "¿cuándo se usó esta máquina?",
 * la IA sabe de qué máquina habla sin que tenga que escribir el nombre.
 *
 * También define las rutas del frontend a las que la IA puede enlazar.
 */
class ContextoPantalla
{
    /** Rutas del frontend (main.jsx) a las que la IA puede poner enlaces. */
    public const RUTAS = [
        '/dashboard' => 'Panel de Control',
        '/machines' => 'Máquinas',
        '/machines/categoriesmachines' => 'Categorías de máquinas',
        '/equipment' => 'Equipos',
        '/types/consumables' => 'Herramientas consumibles',
        '/types/noconsumables' => 'Herramientas no consumibles',
        '/ubications' => 'Ubicaciones',
        '/ubications/categoriesubications' => 'Categorías de ubicación',
        '/companies' => 'Empresas',
        '/gases' => 'Mediciones de gases',
        '/gases/compare_gases' => 'Comparar mediciones de gases',
        '/alerts' => 'Alertas',
        '/profile' => 'Mi perfil (y solicitudes de cuenta para administradores)',
        '/help' => 'Ayuda',
        '/about' => 'Acerca de',
    ];

    public static function describir(?string $pantalla): string
    {
        $ruta = self::limpiar($pantalla);
        if ($ruta === null) {
            return 'No se sabe en qué pantalla está el usuario.';
        }

        try {
            if (preg_match('#^/machines/cardMachine/(\d+)$#i', $ruta, $m)) {
                $maquina = DB::table('machine')->where('id', (int) $m[1])->first(['name', 'code']);
                return $maquina
                    ? "El usuario está viendo la ficha de la máquina \"{$maquina->name}\" (código {$maquina->code}). Si dice \"esta máquina\", se refiere a ella: búscala por su código."
                    : 'El usuario está viendo la ficha de una máquina que ya no existe.';
            }

            if (preg_match('#^/equipment/cardequipment/(\d+)$#i', $ruta, $m)) {
                $equipo = DB::table('equipment')->where('id', (int) $m[1])->first(['name', 'code']);
                return $equipo
                    ? "El usuario está viendo la ficha del equipo \"{$equipo->name}\" (código {$equipo->code}). Si dice \"este equipo\", se refiere a él: búscalo por su código."
                    : 'El usuario está viendo la ficha de un equipo que ya no existe.';
            }

            if (preg_match('#^/gases/gas_format/(\d+)$#i', $ruta, $m)) {
                $medicion = DB::table('gas_measurement')->where('id', (int) $m[1])->value('measurement_date');
                return $medicion
                    ? "El usuario está viendo el formato de la medición de gases número {$m[1]} del {$medicion}. Si dice \"esta medición\", consulta las mediciones de esa fecha."
                    : 'El usuario está viendo una medición de gases que ya no existe.';
            }

            if (preg_match('#^/types/(consumables|noconsumables)/tools/(\d+)$#i', $ruta, $m)) {
                $categoria = DB::table('category')->where('id', (int) $m[2])->value('name');
                return $categoria
                    ? "El usuario está viendo las herramientas de la categoría \"{$categoria}\". Si dice \"estas herramientas\", se refiere a esa categoría."
                    : 'El usuario está viendo una categoría de herramientas.';
            }
        } catch (Throwable) {
            return 'No se pudo identificar la pantalla del usuario.';
        }

        foreach (self::RUTAS as $base => $nombre) {
            if (strcasecmp($ruta, $base) === 0) {
                return "El usuario está en la pantalla \"{$nombre}\".";
            }
        }

        return 'El usuario está en otra pantalla de MANTIA.';
    }

    /** Texto para las instrucciones: cómo y a dónde puede enlazar la IA. */
    public static function guiaDeEnlaces(): string
    {
        $lista = collect(self::RUTAS)
            ->map(fn ($nombre, $ruta) => "- {$ruta} → {$nombre}")
            ->implode("\n");

        return <<<TXT
ENLACES — para que el usuario llegue con un clic:
- Cuando menciones una máquina, equipo, medición de gases o categoría de herramientas concreta y el dato traiga el campo "enlace", agrega un enlace así: [Ver Electrobomba 2](/machines/cardMachine/5).
- Cuando expliques cómo hacer algo o recomiendes ir a un módulo, enlaza la pantalla: [Ir a Mediciones de gases](/gases).
- Usa SOLO el campo "enlace" de las herramientas o estas rutas, escritas exactamente igual. Nunca inventes rutas ni números.
- Máximo 3 enlaces por respuesta, al final de la línea correspondiente. El texto del enlace debe ser corto (2 a 4 palabras).
Pantallas a las que puedes enlazar:
{$lista}
TXT;
    }

    /** Acepta solo rutas internas simples: letras, números, guiones bajos y barras. */
    private static function limpiar(?string $pantalla): ?string
    {
        $ruta = rtrim(trim((string) $pantalla), '/');
        return preg_match('#^/[A-Za-z0-9_/]{1,100}$#', $ruta) ? $ruta : null;
    }
}