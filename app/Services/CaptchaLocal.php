<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

/**
 * Captcha propio: funciona SIN INTERNET (no depende de Cloudflare ni de Google).
 *
 *  - Genera un código de 5 caracteres y lo dibuja como imagen SVG con trazos (no es texto:
 *    un robot no lo puede copiar del HTML), con giros, ruido y líneas.
 *  - El servidor guarda solo un HASH del código en la caché por 5 minutos.
 *  - Cada captcha sirve UNA sola vez: se borra al primer intento, acierte o no.
 *  - No necesita la extensión GD ni fuentes instaladas.
 */
class CaptchaLocal
{
    // Sin caracteres que se confunden (0/O, 1/I/L)
    private const ALFABETO = 'ABCDEFGHJKMNPQRSTUVWXYZ23456789';
    private const LARGO = 5;
    private const MINUTOS = 5;

    // Cada carácter como trazos en una cuadrícula de 10 x 14
    private const TRAZOS = [
        'A' => [[0, 14, 5, 0, 10, 14], [2, 8, 8, 8]],
        'B' => [[0, 0, 0, 14, 7, 14, 10, 11, 10, 9, 7, 7, 0, 7], [0, 0, 7, 0, 9, 2, 9, 5, 7, 7]],
        'C' => [[10, 2, 8, 0, 2, 0, 0, 2, 0, 12, 2, 14, 8, 14, 10, 12]],
        'D' => [[0, 0, 0, 14, 6, 14, 10, 10, 10, 4, 6, 0, 0, 0]],
        'E' => [[10, 0, 0, 0, 0, 14, 10, 14], [0, 7, 7, 7]],
        'F' => [[10, 0, 0, 0, 0, 14], [0, 7, 7, 7]],
        'G' => [[10, 2, 8, 0, 2, 0, 0, 2, 0, 12, 2, 14, 8, 14, 10, 12, 10, 8, 6, 8]],
        'H' => [[0, 0, 0, 14], [10, 0, 10, 14], [0, 7, 10, 7]],
        'J' => [[10, 0, 10, 12, 8, 14, 2, 14, 0, 12]],
        'K' => [[0, 0, 0, 14], [10, 0, 0, 8], [3, 6, 10, 14]],
        'M' => [[0, 14, 0, 0, 5, 8, 10, 0, 10, 14]],
        'N' => [[0, 14, 0, 0, 10, 14, 10, 0]],
        'P' => [[0, 14, 0, 0, 8, 0, 10, 2, 10, 5, 8, 7, 0, 7]],
        'Q' => [[2, 0, 8, 0, 10, 2, 10, 12, 8, 14, 2, 14, 0, 12, 0, 2, 2, 0], [6, 10, 10, 14]],
        'R' => [[0, 14, 0, 0, 8, 0, 10, 2, 10, 5, 8, 7, 0, 7], [5, 7, 10, 14]],
        'S' => [[10, 2, 8, 0, 2, 0, 0, 2, 0, 5, 2, 7, 8, 7, 10, 9, 10, 12, 8, 14, 2, 14, 0, 12]],
        'T' => [[0, 0, 10, 0], [5, 0, 5, 14]],
        'U' => [[0, 0, 0, 12, 2, 14, 8, 14, 10, 12, 10, 0]],
        'V' => [[0, 0, 5, 14, 10, 0]],
        'W' => [[0, 0, 2, 14, 5, 6, 8, 14, 10, 0]],
        'X' => [[0, 0, 10, 14], [10, 0, 0, 14]],
        'Y' => [[0, 0, 5, 7, 10, 0], [5, 7, 5, 14]],
        'Z' => [[0, 0, 10, 0, 0, 14, 10, 14]],
        '2' => [[0, 2, 2, 0, 8, 0, 10, 2, 10, 5, 0, 14, 10, 14]],
        '3' => [[0, 2, 2, 0, 8, 0, 10, 2, 10, 5, 8, 7, 4, 7], [8, 7, 10, 9, 10, 12, 8, 14, 2, 14, 0, 12]],
        '4' => [[8, 14, 8, 0, 0, 10, 10, 10]],
        '5' => [[10, 0, 0, 0, 0, 7, 8, 7, 10, 9, 10, 12, 8, 14, 2, 14, 0, 12]],
        '6' => [[9, 1, 7, 0, 2, 0, 0, 2, 0, 12, 2, 14, 8, 14, 10, 12, 10, 9, 8, 7, 2, 7, 0, 9]],
        '7' => [[0, 0, 10, 0, 4, 14]],
        '8' => [[2, 7, 0, 5, 0, 2, 2, 0, 8, 0, 10, 2, 10, 5, 8, 7, 2, 7, 0, 9, 0, 12, 2, 14, 8, 14, 10, 12, 10, 9, 8, 7]],
        '9' => [[10, 5, 8, 7, 2, 7, 0, 5, 0, 2, 2, 0, 8, 0, 10, 2, 10, 12, 8, 14, 2, 14, 0, 12]],
    ];

    private const COLORES = ['#1f3b57', '#2b4f2f', '#5a2a2a', '#3d2f5c', '#1d4d4f'];

    /** @return array{id: string, imagen: string, expira_en: int} */
    public static function crear(?string $codigo = null): array
    {
        $codigo ??= self::codigoAleatorio();
        $id = Str::random(40);

        Cache::put(self::clave($id), self::hash($codigo), now()->addMinutes(self::MINUTOS));

        return [
            'id' => $id,
            'imagen' => 'data:image/svg+xml;base64,' . base64_encode(self::dibujar($codigo)),
            'expira_en' => self::MINUTOS * 60,
        ];
    }

    /** Revisa la respuesta. El captcha se borra siempre: no se puede volver a intentar con el mismo. */
    public static function verificar(?string $id, ?string $respuesta): bool
    {
        if (! is_string($id) || ! preg_match('/^[A-Za-z0-9]{40}$/', $id) || ! is_string($respuesta)) {
            return false;
        }

        $guardado = Cache::pull(self::clave($id));

        if (! is_string($guardado)) {
            return false;
        }

        $respuesta = strtoupper(preg_replace('/\s+/', '', $respuesta));

        return hash_equals($guardado, self::hash($respuesta));
    }

    private static function clave(string $id): string
    {
        return 'captcha:' . $id;
    }

    private static function hash(string $codigo): string
    {
        return hash_hmac('sha256', strtoupper($codigo), (string) config('app.key'));
    }

    private static function codigoAleatorio(): string
    {
        $codigo = '';
        for ($i = 0; $i < self::LARGO; $i++) {
            $codigo .= self::ALFABETO[random_int(0, strlen(self::ALFABETO) - 1)];
        }

        return $codigo;
    }

    private static function aleatorio(float $min, float $max): float
    {
        return $min + (random_int(0, 10000) / 10000) * ($max - $min);
    }

    private static function dibujar(string $codigo): string
    {
        $ancho = 200;
        $alto = 64;
        $f = fn ($n) => number_format($n, 1, '.', '');

        $svg = "<svg xmlns=\"http://www.w3.org/2000/svg\" width=\"{$ancho}\" height=\"{$alto}\" viewBox=\"0 0 {$ancho} {$alto}\">";
        $svg .= "<rect width=\"{$ancho}\" height=\"{$alto}\" rx=\"8\" fill=\"#eef3f1\"/>";

        // Ruido de fondo: puntos
        for ($i = 0; $i < 45; $i++) {
            $color = self::COLORES[random_int(0, count(self::COLORES) - 1)];
            $svg .= '<circle cx="' . $f(self::aleatorio(0, $ancho)) . '" cy="' . $f(self::aleatorio(0, $alto)) . '" r="' . $f(self::aleatorio(0.6, 1.8)) . "\" fill=\"{$color}\" opacity=\"0.35\"/>";
        }

        // Los caracteres, cada uno girado, movido y con sus trazos un poco deformados
        $escala = 2.6;
        foreach (str_split($codigo) as $i => $letra) {
            $color = self::COLORES[random_int(0, count(self::COLORES) - 1)];
            $x = 18 + $i * 35 + self::aleatorio(-3, 3);
            $y = 13 + self::aleatorio(-5, 5);
            $giro = self::aleatorio(-22, 22);
            $cx = 5 * $escala;
            $cy = 7 * $escala;

            $svg .= '<g transform="translate(' . $f($x) . ' ' . $f($y) . ') rotate(' . $f($giro) . " {$f($cx)} {$f($cy)})\" fill=\"none\" stroke=\"{$color}\" stroke-width=\"" . $f(self::aleatorio(2.6, 3.6)) . '" stroke-linecap="round" stroke-linejoin="round">';

            foreach (self::TRAZOS[$letra] as $trazo) {
                $puntos = [];
                for ($p = 0; $p < count($trazo); $p += 2) {
                    $puntos[] = $f(($trazo[$p] + self::aleatorio(-0.5, 0.5)) * $escala) . ',' . $f(($trazo[$p + 1] + self::aleatorio(-0.5, 0.5)) * $escala);
                }
                $svg .= '<polyline points="' . implode(' ', $puntos) . '"/>';
            }

            $svg .= '</g>';
        }

        // Líneas curvas que cruzan el código
        for ($i = 0; $i < 4; $i++) {
            $color = self::COLORES[random_int(0, count(self::COLORES) - 1)];
            $svg .= '<path d="M' . $f(self::aleatorio(0, 30)) . ' ' . $f(self::aleatorio(8, $alto - 8))
                . ' C' . $f(self::aleatorio(40, 90)) . ' ' . $f(self::aleatorio(0, $alto))
                . ' ' . $f(self::aleatorio(110, 160)) . ' ' . $f(self::aleatorio(0, $alto))
                . ' ' . $f(self::aleatorio(170, $ancho)) . ' ' . $f(self::aleatorio(8, $alto - 8))
                . "\" fill=\"none\" stroke=\"{$color}\" stroke-width=\"" . $f(self::aleatorio(1, 1.8)) . '" opacity="0.7"/>';
        }

        return $svg . '</svg>';
    }
}
