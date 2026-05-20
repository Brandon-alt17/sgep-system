<?php

declare(strict_types=1);

namespace App\Services;

use App\Helpers\Database;
use App\Helpers\Normalizer;

/**
 * Empareja texto de programa (importación / Excel) con registros del catálogo.
 */
class ProgramaCatalogMatcher
{
    /**
     * @return array{id: ?int, ambiguous: bool, normalized_name: string, detected_level: ?string, candidates: array<int, string>}
     */
    public static function resolve(mixed $nombrePrograma, mixed $modalidad = null): array
    {
        $nombreRaw = self::stringOrNull($nombrePrograma);
        if ($nombreRaw === null) {
            return [
                'id' => null,
                'ambiguous' => false,
                'normalized_name' => '',
                'detected_level' => null,
                'candidates' => [],
            ];
        }

        $nombre = Normalizer::normalizeProgramaNombre(
            Normalizer::stripProgramaNivelPrefix($nombreRaw)
        );
        $nivel = Normalizer::extractProgramaNivel($nombreRaw);
        $nombreKey = Normalizer::normalizeProgramComparableKey($nombre);
        $nivelKey = Normalizer::normalizeProgramComparableKey((string) ($nivel ?? ''));

        $rows = Database::connection()->query('SELECT id, nombre, nivel, modalidad FROM programas')->fetchAll();
        $matched = [];
        foreach ($rows as $row) {
            $rowNombre = Normalizer::normalizeProgramaNombre(
                Normalizer::stripProgramaNivelPrefix((string) ($row['nombre'] ?? ''))
            );
            if (Normalizer::normalizeProgramComparableKey($rowNombre) !== $nombreKey) {
                continue;
            }
            $matched[] = $row;
        }

        $selected = null;
        if ($nivelKey !== '') {
            foreach ($matched as $row) {
                $rowNivelKey = Normalizer::normalizeProgramComparableKey((string) ($row['nivel'] ?? ''));
                if ($rowNivelKey === $nivelKey || $rowNivelKey === '') {
                    $selected = $row;
                    break;
                }
            }
        } elseif (count($matched) === 1) {
            $selected = $matched[0];
        }

        if ($selected !== null) {
            return [
                'id' => (int) $selected['id'],
                'ambiguous' => false,
                'normalized_name' => $nombre,
                'detected_level' => $nivel,
                'candidates' => [],
            ];
        }

        if ($nivelKey === '' && count($matched) > 1) {
            $candidates = [];
            foreach ($matched as $m) {
                $candidateNivel = trim((string) ($m['nivel'] ?? ''));
                $candidates[] = $candidateNivel !== '' ? $candidateNivel : 'Sin nivel';
            }

            return [
                'id' => null,
                'ambiguous' => true,
                'normalized_name' => $nombre,
                'detected_level' => null,
                'candidates' => array_values(array_unique($candidates)),
            ];
        }

        return [
            'id' => null,
            'ambiguous' => false,
            'normalized_name' => $nombre,
            'detected_level' => $nivel,
            'candidates' => [],
        ];
    }

    private static function stringOrNull(mixed $v): ?string
    {
        if ($v === null) {
            return null;
        }
        if (is_string($v)) {
            $s = trim($v);

            return $s === '' ? null : $s;
        }
        if (is_numeric($v)) {
            return trim((string) $v);
        }

        return trim((string) $v);
    }
}
