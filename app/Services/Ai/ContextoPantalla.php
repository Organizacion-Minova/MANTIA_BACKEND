<?php

namespace App\Services\Ai;

use App\Models\User;
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
 * También define las rutas del frontend a las que la IA puede enlazar y
 * QUIÉN puede entrar a cada una (mismas reglas que main.jsx y el menú lateral).
 */
class ContextoPantalla
{
    /**
     * Pantallas del frontend (main.jsx) a las que la IA puede poner enlaces.
     * Cada una lleva la misma regla de acceso que RutaConPermiso en main.jsx:
     *   'permiso' => se valida con $usuario->can(...)
     *   'roles'   => lista de roles que pueden entrar
     *   (sin regla) => cualquier usuario con sesión
     *
     * Si cambias una ruta o un permiso en main.jsx, cámbialo aquí y en
     * frontend/src/utils/sugerenciasIA.js (RUTAS_PERMITIDAS).
     */
    public const RUTAS = [
        '/dashboard' => ['nombre' => 'Panel de Control'],
        '/machines' => ['nombre' => 'Máquinas', 'permiso' => 'machine.view'],
        '/machines/categoriesmachines' => ['nombre' => 'Categorías de máquinas', 'permiso' => 'machine-category.update'],
        '/equipment' => ['nombre' => 'Equipos', 'permiso' => 'equipment.view'],
        '/consumables/tools' => ['nombre' => 'Herramientas consumibles', 'permiso' => 'tool.view'],
        '/noconsumables/tools' => ['nombre' => 'Herramientas no consumibles', 'permiso' => 'tool.view'],
        '/consumables/tools/types' => ['nombre' => 'Categorías de herramientas consumibles', 'permiso' => 'category-tools.view'],
        '/noconsumables/tools/types' => ['nombre' => 'Categorías de herramientas no consumibles', 'permiso' => 'category-tools.view'],
        '/ubications' => ['nombre' => 'Ubicaciones', 'permiso' => 'location.update'],
        '/ubications/categoriesubications' => ['nombre' => 'Categorías de ubicación', 'permiso' => 'location-category.update'],
        '/dailyuse' => ['nombre' => 'Uso diario', 'permiso' => 'daily-use.view'],
        '/gases' => ['nombre' => 'Mediciones de gases', 'permiso' => 'gas-measurements.view'],
        '/gases/compare_gases' => ['nombre' => 'Comparar mediciones de gases', 'permiso' => 'gas-measurements.view'],
        '/inspection' => ['nombre' => 'Inspecciones', 'permiso' => 'inspection.view'],
        '/admin' => ['nombre' => 'Panel de administración', 'roles' => ['Jefe de mina']],
        '/help' => ['nombre' => 'Ayuda'],
        '/about' => ['nombre' => 'Acerca de'],
    ];

    /** ¿Este usuario puede entrar a la pantalla? (misma lógica que RutaConPermiso). */
    private static function puedeEntrar(?User $usuario, array $regla): bool
    {
        if (! $usuario) {
            return false;
        }
        if (isset($regla['permiso'])) {
            return $usuario->can($regla['permiso']);
        }
        if (isset($regla['roles'])) {
            return $usuario->hasAnyRole($regla['roles']);
        }

        return true;
    }

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
        } catch (Throwable) {
            return 'No se pudo identificar la pantalla del usuario.';
        }

        foreach (self::RUTAS as $base => $info) {
            if (strcasecmp($ruta, $base) === 0) {
                return "El usuario está en la pantalla \"{$info['nombre']}\".";
            }
        }

        return 'El usuario está en otra pantalla de MANTIA.';
    }

    /**
     * Texto para las instrucciones: cómo y a dónde puede enlazar la IA.
     * Se arma POR USUARIO: solo lista las pantallas a las que su rol puede entrar
     * y aparte las que no, para que la IA explique la falta de acceso en vez de
     * mandarlo a una pantalla que le dice "No tienes permiso".
     */
    public static function guiaDeEnlaces(?User $usuario): string
    {
        $conAcceso = [];
        $sinAcceso = [];
        foreach (self::RUTAS as $ruta => $info) {
            if (self::puedeEntrar($usuario, $info)) {
                $conAcceso[] = "- {$ruta} → {$info['nombre']}";
            } else {
                $sinAcceso[] = "- {$info['nombre']}";
            }
        }

        $roles = $usuario ? $usuario->getRoleNames()->implode(', ') : '';
        $roles = $roles !== '' ? $roles : 'sin rol';
        $lista = implode("\n", $conAcceso);
        $bloqueadas = $sinAcceso ? implode("\n", $sinAcceso) : '- (ninguna)';

        return <<<TXT
ENLACES Y REDIRECCIÓN — para que el usuario llegue a una pantalla con un clic.
Rol del usuario: {$roles}.
- Si el usuario pide ir, abrir, que lo lleves o encontrar un módulo (máquinas, equipos, herramientas, ubicaciones, gases, uso diario, inspecciones...), responde en una frase corta y agrega el enlace: [Ir a Máquinas](/machines).
- Si pide "herramientas" sin decir cuáles, ofrece los dos enlaces: consumibles y no consumibles.
- Si menciona una máquina, equipo o medición de gases concreta y el dato de la herramienta trae el campo "enlace", agrégalo: [Ver Electrobomba 2](/machines/cardMachine/5).
- Usa SOLO las rutas de la lista "con acceso", escritas exactamente igual. Nunca inventes rutas ni números.
- Si el usuario pide una pantalla de la lista "SIN acceso", NO pongas enlace: dile en una frase que su rol no tiene acceso a ese módulo y que puede pedirlo al Jefe de mina.
- Máximo 3 enlaces por respuesta. El texto del enlace debe ser corto (2 a 4 palabras).
Pantallas CON acceso para este usuario:
{$lista}
Pantallas SIN acceso para este usuario (no enlazar):
{$bloqueadas}
TXT;
    }

    /** Acepta solo rutas internas simples: letras, números, guiones bajos y barras. */
    private static function limpiar(?string $pantalla): ?string
    {
        $ruta = rtrim(trim((string) $pantalla), '/');
        return preg_match('#^/[A-Za-z0-9_/]{1,100}$#', $ruta) ? $ruta : null;
    }
}