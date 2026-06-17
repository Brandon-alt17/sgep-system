<?php

declare(strict_types=1);

namespace App\Helpers;

class Normalizer
{
    public static function normalizeRow(array $row): array
    {
        foreach ($row as $key => $value) {
            $row[$key] = is_string($value) ? trim($value) : $value;
        }
        if (!empty($row['nombre_completo'])) {
            $row['nombre_completo'] = ucwords(strtolower((string) $row['nombre_completo']));
        }
        foreach (['correo_personal', 'correo_electronico_personal'] as $emailKey) {
            if (!empty($row[$emailKey])) {
                $row[$emailKey] = strtolower((string) $row[$emailKey]);
            }
        }
        foreach (['correo_institucional', 'correo_electronico_institucional'] as $textKey) {
            if (isset($row[$textKey]) && is_string($row[$textKey])) {
                $row[$textKey] = trim($row[$textKey]);
            }
        }
        if (!empty($row['nit_empresa'])) {
            $row['nit_empresa'] = preg_replace('/\.\d+$/', '', (string) $row['nit_empresa']);
        }
        if (!empty($row['programa_formacion'])) {
            $row['programa_formacion'] = self::normalizeProgramaNombre((string) $row['programa_formacion']);
        }
        return $row;
    }

    /**
     * Campo libre: no todos los aprendices tienen correo @sena.edu.co.
     * Se conserva por compatibilidad con importación y pruebas.
     */
    public static function isCorreoInstitucionalSenaValid(?string $value): bool
    {
        return true;
    }

    public static function normalizeProgramaNombre(string $value): string
    {
        $text = trim($value);
        $text = preg_replace('/\s+/', ' ', $text) ?? $text;
        return $text;
    }

    public static function extractProgramaNivel(string $value): ?string
    {
        $text = trim($value);
        if ($text === '') {
            return null;
        }

        if (preg_match('/^(tec\.?|tecnico|técnico)\b/iu', $text) === 1) {
            return 'Técnico';
        }
        if (preg_match('/^(tgo|tecnologo|tecnólogo)\b/iu', $text) === 1) {
            return 'Tecnólogo';
        }

        return null;
    }

    /** Quita prefijos de nivel del nombre para comparar con el catálogo (ej. "Tecnólogo en …"). */
    public static function stripProgramaNivelPrefix(string $value): string
    {
        $text = trim($value);
        if ($text === '') {
            return '';
        }

        $patterns = [
            '/^(?:tec\.?|t[eé]cnico)\s+(?:en\s+)?/iu',
            '/^(?:tgo|tecn[oó]logo)\s+(?:en\s+)?/iu',
        ];
        foreach ($patterns as $pattern) {
            $stripped = preg_replace($pattern, '', $text);
            if (is_string($stripped) && trim($stripped) !== '' && $stripped !== $text) {
                return trim($stripped);
            }
        }

        return $text;
    }

    public static function normalizeProgramComparableKey(string $value): string
    {
        $v = trim($value);
        $v = mb_strtolower($v);
        $v = str_replace(['á', 'é', 'í', 'ó', 'ú', 'ü'], ['a', 'e', 'i', 'o', 'u', 'u'], $v);
        $v = preg_replace('/\s+/', ' ', $v) ?? $v;
        $v = trim($v);
        $v = rtrim($v, '.,;:-');

        return trim($v);
    }

    /** Minúsculas con mayúscula inicial de cada oración (. ! ?). */
    public static function normalizeSentenceCase(string $text): string
    {
        $text = trim(preg_replace('/\s+/u', ' ', $text) ?? $text);
        if ($text === '') {
            return '';
        }

        $lower = mb_strtolower($text, 'UTF-8');
        $result = preg_replace_callback(
            '/(^|[.!?]+\s+)(\p{L})/u',
            static function (array $m): string {
                return $m[1] . mb_strtoupper($m[2], 'UTF-8');
            },
            $lower
        );

        return is_string($result) ? $result : $lower;
    }

    /** Lista separada por comas (competencias / RAEs en M1). */
    public static function normalizeCommaListSentenceCase(string $text): string
    {
        $text = trim($text);
        if ($text === '') {
            return '';
        }

        $parts = preg_split('/\s*,\s*/u', $text) ?: [];
        $normalized = [];
        foreach ($parts as $part) {
            $piece = self::normalizeSentenceCase($part);
            if ($piece !== '') {
                $normalized[] = $piece;
            }
        }

        return implode(', ', $normalized);
    }
}
