<?php

declare(strict_types=1);

namespace App\Models;

use App\Helpers\Database;

/** Catálogo de grupos = agregación por ficha (+ programa) desde aprendices. */
class Grupo
{
    /**
     * @param array{q?: string, programa_id?: int|string} $filters
     * @return array{where: list<string>, params: array<string, int|string>}
     */
    private static function catalogoWhere(array $filters): array
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

        return ['where' => $where, 'params' => $params];
    }

    /**
     * @param array{q?: string, programa_id?: int|string} $filters
     */
    public static function countCatalogo(array $filters = []): int
    {
        ['where' => $where, 'params' => $params] = self::catalogoWhere($filters);
        $sql = '
            SELECT COUNT(*) FROM (
                SELECT TRIM(a.ficha) AS ficha, a.programa_id
                FROM aprendices a
                LEFT JOIN programas p ON p.id = a.programa_id
                WHERE ' . implode(' AND ', $where) . '
                GROUP BY TRIM(a.ficha), a.programa_id
            ) grouped
        ';
        $stmt = Database::connection()->prepare($sql);
        $stmt->execute($params);

        return (int) ($stmt->fetchColumn() ?: 0);
    }

    /**
     * @param array{q?: string, programa_id?: int|string} $filters
     * @return list<array{ficha: string, programa_id: int|null, programa_nombre: string, aprendices_count: int}>
     */
    public static function catalogo(array $filters = [], int $limit = 0, int $offset = 0): array
    {
        ['where' => $where, 'params' => $params] = self::catalogoWhere($filters);

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
        if ($limit > 0) {
            $sql .= ' LIMIT :limit OFFSET :offset';
        }

        $stmt = Database::connection()->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue(':' . $key, $value);
        }
        if ($limit > 0) {
            $stmt->bindValue(':limit', $limit, \PDO::PARAM_INT);
            $stmt->bindValue(':offset', max(0, $offset), \PDO::PARAM_INT);
        }
        $stmt->execute();
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
