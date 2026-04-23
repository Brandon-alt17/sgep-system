<?php

declare(strict_types=1);

namespace App\Models;

use App\Helpers\Database;

class Programa
{
    public static function all(): array
    {
        return Database::connection()->query('SELECT * FROM programas ORDER BY nombre ASC')->fetchAll();
    }

    public static function catalogo(array $filters = []): array
    {
        $where = [];
        $params = [];

        $query = trim((string) ($filters['q'] ?? ''));
        if ($query !== '') {
            $where[] = '(codigo LIKE :q OR nombre LIKE :q)';
            $params['q'] = '%' . $query . '%';
        }

        $nivel = trim((string) ($filters['nivel'] ?? ''));
        if ($nivel !== '') {
            $where[] = 'nivel = :nivel';
            $params['nivel'] = $nivel;
        }

        $modalidad = trim((string) ($filters['modalidad'] ?? ''));
        if ($modalidad !== '') {
            $where[] = 'modalidad = :modalidad';
            $params['modalidad'] = $modalidad;
        }

        $sql = 'SELECT id, codigo, nombre, nivel, modalidad FROM programas';
        if ($where !== []) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }
        $sql .= ' ORDER BY nombre ASC';

        $stmt = Database::connection()->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll();
    }
}
