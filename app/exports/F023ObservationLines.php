<?php

declare(strict_types=1);

namespace App\Exports;

/**
 * Texto de observaciones en F-023: renglones alineados con subrayado en plantilla Word (m2).
 */
final class F023ObservationLines
{
    /**
     * Párrafos opcionales inyectados en m2/EX (líneas 3+). Los límites de caracteres de
     * config/f023_limites.php (obs_instructor, etc.) son solo indicativos — no truncan el texto
     * ingresado (ver MomentoController::applyTextLimits() y public/js/app.js) — así que este
     * techo de líneas físicas es lo único que realmente acota cuánto texto entra en el documento.
     * Se fija generoso (muy por encima de lo que cualquier caso real necesitaría) para que en la
     * práctica no se pierda contenido, sin dejar de ser un límite técnico finito.
     */
    public const TEMPLATE_SLOT_COUNT = 24;

    public static function charsPerLine(): int
    {
        $limites = require base_path('config/f023_limites.php');

        return max(1, (int) ($limites['obs_two_line_chars'] ?? 132));
    }

    /**
     * Presupuesto de caracteres (sin espacios) que puede repartirse entre las
     * TEMPLATE_SLOT_COUNT líneas físicas disponibles. Ya NO se acorta por debajo de la capacidad
     * física real de líneas según ningún límite de campo — los límites de
     * config/f023_limites.php son indicativos (ver arriba), no deben usarse para descartar texto
     * que sí cabría en las líneas disponibles.
     *
     * $charsPerLine permite calcular el presupuesto total con un ancho de línea distinto al base
     * (ver splitLines()).
     */
    public static function maxTotalChars(?int $charsPerLine = null): int
    {
        return ($charsPerLine ?? self::charsPerLine()) * self::TEMPLATE_SLOT_COUNT;
    }

    /**
     * Respeta saltos de línea (Enter) del formulario; solo hace word-wrap si un renglón supera el ancho.
     *
     * $charsPerLine permite envolver a un ancho distinto del calibrado para el tamaño de letra
     * base (18 medios-puntos): cuando el caller ya sabe que la letra se va a reducir (ver
     * F023Generator::observationCharsPerLineOverrides()), pasar aquí el ancho proporcionalmente
     * mayor evita que el texto quede envuelto en más líneas de las que realmente hacen falta al
     * tamaño final (cada línea quedaría usando solo una fracción del ancho real disponible).
     *
     * @return list<string>
     */
    public static function splitLines(string $text, ?int $maxTotalChars = null, ?int $charsPerLine = null): array
    {
        $perLine = $charsPerLine ?? self::charsPerLine();
        $max = $maxTotalChars ?? self::maxTotalChars($perLine);
        $normalized = str_replace(["\r\n", "\r"], "\n", trim($text));
        if ($normalized === '') {
            return [];
        }

        $plainBudget = preg_replace('/\s+/u', '', $normalized) ?? '';
        $plainBudget = mb_substr($plainBudget, 0, $max);

        /** @var list<string> $explicitRows */
        $explicitRows = preg_split('/\n/u', $normalized) ?: [];

        /** @var list<string> $lines */
        $lines = [];
        $consumedPlain = 0;

        foreach ($explicitRows as $row) {
            $row = trim(preg_replace('/[ \t]+/u', ' ', $row) ?? '');
            if ($row === '') {
                continue;
            }

            foreach (self::wrapToLineWidth($row, $perLine) as $segment) {
                $segmentPlainLen = mb_strlen(preg_replace('/\s+/u', '', $segment) ?? '');
                if ($consumedPlain + $segmentPlainLen > mb_strlen($plainBudget)) {
                    break 2;
                }

                $lines[] = $segment;
                $consumedPlain += $segmentPlainLen;

                if (count($lines) >= self::TEMPLATE_SLOT_COUNT) {
                    break 2;
                }
            }
        }

        return $lines;
    }

    /**
     * @return list<string>
     */
    private static function wrapToLineWidth(string $text, int $perLine): array
    {
        if (mb_strlen($text) <= $perLine) {
            return [$text];
        }

        /** @var list<string> $wrapped */
        $wrapped = [];
        $remaining = $text;

        while ($remaining !== '') {
            if (mb_strlen($remaining) <= $perLine) {
                $wrapped[] = $remaining;
                break;
            }

            $chunk = mb_substr($remaining, 0, $perLine);
            $breakPos = mb_strrpos($chunk, ' ');
            if ($breakPos !== false && $breakPos >= (int) ($perLine * 0.4)) {
                $wrapped[] = mb_substr($remaining, 0, $breakPos);
                $remaining = trim(mb_substr($remaining, $breakPos));
            } else {
                $wrapped[] = $chunk;
                $remaining = trim(mb_substr($remaining, $perLine));
            }
        }

        return $wrapped;
    }

    /**
     * @return array{0: string, 1: string}
     */
    public static function split(string $text): array
    {
        $lines = self::splitLines($text);

        return [$lines[0] ?? '', $lines[1] ?? ''];
    }

    public static function combineTwoLines(string $line1, string $line2): string
    {
        $line1 = trim($line1);
        $line2 = trim($line2);
        if ($line1 === '') {
            return $line2;
        }
        if ($line2 === '') {
            return $line1;
        }

        return $line1 . "\n" . $line2;
    }

    /**
     * Línea vacía: espacio no separable sobre borde inferior (siempre línea horizontal).
     */
    public static function templateLineValue(string $line, int $lineIndex): string
    {
        if ($line !== '') {
            return $line;
        }

        return "\u{00A0}";
    }

    /**
     * @param list<string> $keys
     * @param array<string, string> $vars
     * @param array<string, int> $charsPerLineByKey Ancho de línea override por campo (ver splitLines()).
     * @return array<string, string>
     */
    public static function expandTemplateVars(
        array $vars,
        array $keys,
        int $minDisplayLines = 2,
        array $charsPerLineByKey = []
    ): array {
        $minDisplayLines = max(1, $minDisplayLines);

        foreach ($keys as $key) {
            $charsPerLine = $charsPerLineByKey[$key] ?? null;
            $lines = self::splitLines((string) ($vars[$key] ?? ''), self::maxTotalChars($charsPerLine), $charsPerLine);
            $usedLines = count($lines);
            $displayLines = max($minDisplayLines, $usedLines);

            for ($lineIndex = 1; $lineIndex <= self::TEMPLATE_SLOT_COUNT; $lineIndex++) {
                $macroKey = $lineIndex === 1 ? $key : $key . '_l' . $lineIndex;

                if ($lineIndex > $displayLines) {
                    $vars[$macroKey] = '';
                } elseif ($lineIndex <= $usedLines) {
                    $vars[$macroKey] = self::templateLineValue($lines[$lineIndex - 1], $lineIndex);
                } else {
                    $vars[$macroKey] = self::templateLineValue('', $lineIndex);
                }
            }
        }

        return $vars;
    }
}
