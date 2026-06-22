<?php

declare(strict_types=1);

namespace App\Exports;

/**
 * Texto de observaciones en F-023: máximo 2 renglones alineados con la plantilla Word.
 */
final class F023ObservationLines
{
    public static function charsPerLine(): int
    {
        $limites = require base_path('config/f023_limites.php');

        return max(1, (int) ($limites['obs_two_line_chars'] ?? 120));
    }

    public static function maxTotalChars(): int
    {
        return self::charsPerLine() * 2;
    }

    /**
     * @return array{0: string, 1: string}
     */
    public static function split(string $text): array
    {
        $text = trim(preg_replace('/[\r\n]+/u', ' ', $text) ?? '');
        $text = preg_replace('/\s+/u', ' ', $text) ?? '';
        if ($text === '') {
            return ['', ''];
        }

        $max = self::charsPerLine();
        $text = mb_substr($text, 0, $max * 2);
        if (mb_strlen($text) <= $max) {
            return [$text, ''];
        }

        $line1 = mb_substr($text, 0, $max);
        $breakPos = mb_strrpos($line1, ' ');
        if ($breakPos !== false && $breakPos >= (int) ($max * 0.4)) {
            $line1 = mb_substr($text, 0, $breakPos);
            $rest = trim(mb_substr($text, $breakPos));
        } else {
            $line1 = mb_substr($text, 0, $max);
            $rest = trim(mb_substr($text, $max));
        }

        return [$line1, mb_substr($rest, 0, $max)];
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
     * Línea 1 vacía: espacio + borde inferior. Línea 2 vacía: guiones como en plantilla m2.
     */
    public static function templateLineValue(string $line, int $lineIndex): string
    {
        if ($line !== '') {
            return $line;
        }

        if ($lineIndex === 2) {
            return str_repeat('_', self::charsPerLine());
        }

        return "\u{00A0}";
    }

    /**
     * @param list<string> $keys
     * @param array<string, string> $vars
     * @return array<string, string>
     */
    public static function expandTemplateVars(array $vars, array $keys): array
    {
        foreach ($keys as $key) {
            [$line1, $line2] = self::split((string) ($vars[$key] ?? ''));
            $vars[$key] = self::templateLineValue($line1, 1);
            $vars[$key . '_l2'] = self::templateLineValue($line2, 2);
        }

        return $vars;
    }
}
