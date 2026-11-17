<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;

/**
 * Lee la estructura real de la base de datos (tablas y llaves foráneas).
 * Funciona en PostgreSQL (producción) y SQLite (pruebas automatizadas).
 */
class EsquemaBD
{
    private static ?array $llaves = null;

    /** @return string[] todas las tablas del esquema actual */
    public static function tablas(): array
    {
        if (DB::getDriverName() === 'pgsql') {
            return array_map(
                fn ($fila) => $fila->tablename,
                DB::select('select tablename from pg_tables where schemaname = current_schema() order by tablename')
            );
        }

        if (DB::getDriverName() === 'sqlite') {
            return array_map(
                fn ($fila) => $fila->name,
                DB::select("select name from sqlite_master where type = 'table' and name not like 'sqlite_%' order by name")
            );
        }

        return array_map(fn ($t) => $t['name'], \Illuminate\Support\Facades\Schema::getTables());
    }

    /**
     * Llaves foráneas de una sola columna.
     * @return array<int, array{hijo:string, columna:string, padre:string, columna_padre:string}>
     */
    public static function llavesForaneas(): array
    {
        if (self::$llaves !== null) {
            return self::$llaves;
        }

        $llaves = [];

        if (DB::getDriverName() === 'pgsql') {
            $filas = DB::select(
                "select c.relname as hijo, a.attname as columna, p.relname as padre, ap.attname as columna_padre
                 from pg_constraint k
                 join pg_class c on c.oid = k.conrelid
                 join pg_class p on p.oid = k.confrelid
                 join pg_attribute a on a.attrelid = k.conrelid and a.attnum = k.conkey[1]
                 join pg_attribute ap on ap.attrelid = k.confrelid and ap.attnum = k.confkey[1]
                 where k.contype = 'f'
                   and c.relnamespace = (select oid from pg_namespace where nspname = current_schema())"
            );

            foreach ($filas as $f) {
                $llaves[] = ['hijo' => $f->hijo, 'columna' => $f->columna, 'padre' => $f->padre, 'columna_padre' => $f->columna_padre];
            }
        } elseif (DB::getDriverName() === 'sqlite') {
            foreach (self::tablas() as $tabla) {
                foreach (DB::select('PRAGMA foreign_key_list("' . str_replace('"', '', $tabla) . '")') as $f) {
                    $llaves[] = ['hijo' => $tabla, 'columna' => $f->from, 'padre' => $f->table, 'columna_padre' => $f->to ?? 'id'];
                }
            }
        }

        return self::$llaves = $llaves;
    }

    /** @return array<string, string[]> tabla hija => tablas padre */
    public static function padres(): array
    {
        // model_has_roles y similares no tienen llave foránea hacia users, pero dependen de ellos
        $grafo = ['model_has_roles' => ['roles', 'users'], 'model_has_permissions' => ['permissions', 'users']];

        foreach (self::llavesForaneas() as $llave) {
            if ($llave['hijo'] !== $llave['padre']) {
                $grafo[$llave['hijo']][] = $llave['padre'];
            }
        }

        return array_map('array_values', array_map('array_unique', $grafo));
    }

    /** Ordena las tablas para insertar: primero los padres y después los hijos. */
    public static function ordenar(array $tablas): array
    {
        $padres = self::padres();
        $orden = [];
        $pendientes = array_values(array_unique($tablas));

        while ($pendientes) {
            $avanzo = false;

            foreach ($pendientes as $i => $tabla) {
                $suyos = array_intersect($padres[$tabla] ?? [], $tablas);

                if (! array_diff($suyos, $orden)) {
                    $orden[] = $tabla;
                    unset($pendientes[$i]);
                    $avanzo = true;
                }
            }

            if (! $avanzo) {
                return array_merge($orden, array_values($pendientes));
            }
        }

        return $orden;
    }

    /**
     * Filas que dependen de un registro (hijos, nietos...), para poder devolverlas si se revierte
     * su eliminación. Las columnas binarias se guardan en base64.
     *
     * @return array{filas: array<string, array<int, array>>, completo: bool}
     */
    public static function dependientes(string $tabla, mixed $id, int $limite = 2000): array
    {
        $resultado = [];
        $total = 0;
        $completo = true;
        $cola = [[$tabla, 'id', [$id]]];
        $visitadas = [];

        while ($cola) {
            [$padre, $columnaPadre, $valores] = array_shift($cola);

            foreach (self::llavesForaneas() as $llave) {
                if ($llave['padre'] !== $padre || $llave['columna_padre'] !== $columnaPadre || $llave['hijo'] === $padre) {
                    continue;
                }

                $clave = $llave['hijo'] . '.' . $llave['columna'];
                if (isset($visitadas[$clave])) {
                    continue;
                }
                $visitadas[$clave] = true;

                $filas = DB::table($llave['hijo'])->whereIn($llave['columna'], $valores)->limit($limite + 1)->get()
                    ->map(fn ($fila) => self::serializarFila((array) $fila))
                    ->all();

                if (! $filas) {
                    continue;
                }

                $total += count($filas);
                if ($total > $limite) {
                    $completo = false;
                    break 2;
                }

                $resultado[$llave['hijo']] = array_merge($resultado[$llave['hijo']] ?? [], $filas);

                $ids = array_values(array_filter(array_column($filas, 'id'), fn ($v) => $v !== null));
                if ($ids) {
                    $cola[] = [$llave['hijo'], 'id', $ids];
                }
            }
        }

        $orden = self::ordenar(array_keys($resultado));
        $ordenado = [];
        foreach ($orden as $t) {
            $ordenado[$t] = $resultado[$t];
        }

        return ['filas' => $ordenado, 'completo' => $completo];
    }

    /** Convierte valores binarios (firmas, imágenes) a base64 para poder guardarlos en JSON. */
    public static function serializarFila(array $fila): array
    {
        foreach ($fila as $columna => $valor) {
            if (is_resource($valor)) {
                $valor = stream_get_contents($valor);
                $fila[$columna] = ['__base64__' => base64_encode((string) $valor)];
            } elseif (is_string($valor) && ! mb_check_encoding($valor, 'UTF-8')) {
                $fila[$columna] = ['__base64__' => base64_encode($valor)];
            }
        }

        return $fila;
    }

    /** Inserta una fila guardada con serializarFila(), devolviendo los binarios a su forma original. */
    public static function insertarFila(string $tabla, array $fila): void
    {
        $columnas = array_keys($fila);
        $marcas = [];
        $valores = [];
        $pgsql = DB::getDriverName() === 'pgsql';

        foreach ($fila as $valor) {
            if (is_array($valor) && array_key_exists('__base64__', $valor)) {
                if ($pgsql) {
                    $marcas[] = "decode(?, 'base64')";
                    $valores[] = $valor['__base64__'];
                } else {
                    $marcas[] = '?';
                    $valores[] = base64_decode($valor['__base64__']);
                }
            } else {
                $marcas[] = '?';
                $valores[] = is_array($valor) ? json_encode($valor, JSON_UNESCAPED_UNICODE) : $valor;
            }
        }

        $q = fn ($n) => '"' . str_replace('"', '', $n) . '"';

        DB::insert(
            'insert into ' . $q($tabla) . ' (' . implode(', ', array_map($q, $columnas)) . ') values (' . implode(', ', $marcas) . ')',
            $valores
        );
    }

    public static function olvidarCache(): void
    {
        self::$llaves = null;
    }
}
