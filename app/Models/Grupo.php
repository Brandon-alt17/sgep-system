<?php

declare(strict_types=1);

namespace App\Models;

use App\Helpers\Database;

/** Catálogo de grupos = agregación por ficha (+ programa) desde aprendices. */
class Grupo
{
    /**
     * @param array{q?: string, programa_id?: int|string} $filters
     * @return list<array{ficha: string, programa_id: int|null, programa_nombre: string, aprendices_count: int}>
     */
    public static function catalogo(array $filters = []): array
    {
        $q = trim((string) ($filters['q'] ?? ''));
        $programaId = (int) ($filters['programa_id'] ?? 0);

        $where = ["TRIM(COALESCE(a.ficha, '')) <> ''"];
        $params = [];

        if ($q !== '') {
            $where[] = '(TRIM(a.ficha) LIKE :q_ficha OR COALESCE(p.nombre, \'\') LIKE :q_programa)';
            $likeQ = '%' . $q . '%';
            $params['q_ficha'] = $likeQ;
            $params['q_programa'] = $likeQ;
        }
        if ($programaId > 0) {
            $where[] = 'a.programa_id = :programa_id';
            $params['programa_id'] = $programaId;
        }

        $sql = '
            SELECT
                TRIM(a.ficha) AS ficha,
                a.programa_id,
                MAX(COALESCE(p.nombre, \'\')) AS programa_nombre,
                COUNT(*) AS aprendices_count
            FROM aprendices a
            LEFT JOIN programas p ON p.id = a.programa_id
            WHERE ' . implode(' AND ', $where) . '
            GROUP BY TRIM(a.ficha), a.programa_id
            ORDER BY TRIM(a.ficha) ASC, programa_nombre ASC
        ';

        $stmt = Database::connection()->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll();

        $out = [];
        foreach ($rows as $row) {
            $out[] = [
                'ficha' => (string) ($row['ficha'] ?? ''),
                'programa_id' => isset($row['programa_id']) && $row['programa_id'] !== null && $row['programa_id'] !== ''
                    ? (int) $row['programa_id']
                    : null,
                'programa_nombre' => (string) ($row['programa_nombre'] ?? ''),
                'aprendices_count' => (int) ($row['aprendices_count'] ?? 0),
            ];
        }

        return $out;
    }
}
