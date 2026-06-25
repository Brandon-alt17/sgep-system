<?php

declare(strict_types=1);

namespace App\Imports;

/**
 * Valores escalares de importación que deben tratarse como vacíos (N/A, No, etc.).
 */
final class ImportScalar
{
    public static function isIgnorable(mixed $value): bool
    {
        if ($value === null) {
            return true;
        }
        if (!is_string($value) && !is_numeric($value)) {
            return true;
        }

        $normalized = mb_strtolower(trim((string) $value));
        if ($normalized === '') {
            return true;
        }

        static $placeholders = [
            'n/a',
            'na',
            'no',
            'no aplica',
            'sin asignar',
            'sin dato',
            'sin datos',
            'ninguno',
            'ninguna',
            '-',
            '--',
            '—',
        ];

        return in_array($normalized, $placeholders, true);
    }

    public static function clean(mixed $value): string
    {
        if (self::isIgnorable($value)) {
            return '';
        }

        return trim((string) $value);
    }
}
