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
        foreach (['correo_personal', 'correo_institucional', 'correo_electronico_personal', 'correo_electronico_institucional'] as $emailKey) {
            if (!empty($row[$emailKey])) {
                $row[$emailKey] = strtolower((string) $row[$emailKey]);
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

    public static function normalizeProgramaNombre(string $value): string
    {
        $text = trim($value);
        $text = preg_replace('/\s+/', ' ', $text) ?? $text;

        // Separa prefijos de nivel cuando vienen incrustados en el nombre.
        $text = preg_replace('/^(tec\.?|tecnico|técnico|tgo|tecnologo|tecnólogo)\s+(en\s+)?/iu', '', $text) ?? $text;
        $text = trim($text);

        $text = mb_strtolower($text);
        $text = mb_convert_case($text, MB_CASE_TITLE, 'UTF-8');
        // Conserva siglas frecuentes en mayúsculas.
        $text = preg_replace('/\bTic\b/u', 'TIC', $text) ?? $text;
        $text = preg_replace('/\bTics\b/u', 'TIC', $text) ?? $text;

        return trim($text);
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

    public static function normalizeProgramComparableKey(string $value): string
    {
        $v = trim($value);
        $v = mb_strtolower($v);
        $v = str_replace(['á', 'é', 'í', 'ó', 'ú', 'ü'], ['a', 'e', 'i', 'o', 'u', 'u'], $v);
        $v = preg_replace('/\s+/', ' ', $v) ?? $v;
        return trim($v);
    }
}
