<?php

declare(strict_types=1);

namespace App\Imports;

/**
 * Resuelve índices de columna a partir de la fila de encabezados del Excel.
 */
final class ImportColumnResolver
{
    /**
     * @param list<mixed> $headerRow
     * @param array<int, string> $defaultIndexToField
     * @return array<string, int> campo => índice de columna
     */
    public static function resolve(array $headerRow, array $defaultIndexToField): array
    {
        /** @var array<string, list<string>> $aliases */
        $aliases = require base_path('config/import_header_aliases.php');

        $defaultFieldToIndex = [];
        foreach ($defaultIndexToField as $index => $field) {
            if (!is_string($field) || $field === '') {
                continue;
            }
            $defaultFieldToIndex[$field] = (int) $index;
        }

        $resolved = $defaultFieldToIndex;
        $candidates = [];

        foreach ($defaultFieldToIndex as $field => $defaultIndex) {
            $needles = $aliases[$field] ?? [];
            foreach ($headerRow as $index => $cell) {
                $score = self::matchScore($field, (string) $cell, $needles);
                if ($score <= 0) {
                    continue;
                }
                $candidates[] = [
                    'field' => $field,
                    'index' => (int) $index,
                    'score' => $score,
                    'default' => $defaultIndex,
                ];
            }
        }

        usort($candidates, static function (array $a, array $b): int {
            if ($a['score'] !== $b['score']) {
                return $b['score'] <=> $a['score'];
            }

            $distA = abs($a['index'] - $a['default']);
            $distB = abs($b['index'] - $b['default']);
            if ($distA !== $distB) {
                return $distA <=> $distB;
            }

            return $a['index'] <=> $b['index'];
        });

        $usedFields = [];
        $usedColumns = [];
        foreach ($candidates as $candidate) {
            $field = $candidate['field'];
            $index = $candidate['index'];
            if (isset($usedFields[$field]) || isset($usedColumns[$index])) {
                continue;
            }
            $resolved[$field] = $index;
            $usedFields[$field] = true;
            $usedColumns[$index] = true;
        }

        return $resolved;
    }

    /**
     * @param list<string> $needles
     */
    private static function matchScore(string $field, string $headerCell, array $needles): int
    {
        $header = self::normalizeHeader($headerCell);
        if ($header === '' || !self::headerAllowedForField($field, $header)) {
            return 0;
        }

        $best = 0;
        foreach ($needles as $needle) {
            $n = self::normalizeHeader($needle);
            if ($n === '' || !str_contains($header, $n)) {
                continue;
            }
            $best = max($best, strlen($n));
        }

        return $best;
    }

    private static function headerAllowedForField(string $field, string $normalizedHeader): bool
    {
        $has = static fn (string $token): bool => str_contains($normalizedHeader, $token);

        return match ($field) {
            'nombre_instructor_seguimiento' => !$has('telefono') && !$has('celular') && !$has('correo'),
            'telefono_instructor_seguimiento' => ($has('telefono') || $has('celular')) && $has('instructor'),
            'correo_instructor_seguimiento' => $has('correo') && $has('instructor'),
            'correo_electronico_personal' => !$has('institucional') && !$has('instructor'),
            'correo_electronico_institucional' => $has('institucional') && !$has('instructor'),
            'nombre_jefe' => !$has('telefono') && !$has('correo') && !$has('cargo'),
            'cargo_jefe' => $has('cargo'),
            'correo_jefe' => $has('correo'),
            'telefono_jefe' => $has('telefono') || $has('celular'),
            'numero_celular' => ($has('celular') || $has('telefono')) && !$has('instructor') && !$has('jefe'),
            'ciudad_domicilio_aprendiz' => !$has('empresa') && !$has('practica') && !$has('coformadora'),
            'ciudad_empresa' => ($has('ciudad') || $has('municipio')) && !$has('domicilio'),
            'direccion_empresa' => $has('direccion') && ($has('empresa') || $has('coformadora')) && !$has('practica'),
            'direccion_realiza_practica' => $has('direccion') && $has('practica'),
            'jefe_grupo' => !$has('instructor de seguimiento') && !$has('tipo de asistencia'),
            'tipo_asistencia' => $has('asistencia') || $has('discapacidad'),
            default => true,
        };
    }

    private static function normalizeHeader(string $value): string
    {
        $v = mb_strtolower(trim($value));
        $v = str_replace(
            ['á', 'é', 'í', 'ó', 'ú', 'ü', 'ñ'],
            ['a', 'e', 'i', 'o', 'u', 'u', 'n'],
            $v
        );
        $v = preg_replace('/\s+/', ' ', $v) ?? $v;

        return trim($v);
    }
}
