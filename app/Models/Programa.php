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
            $where[] = '(codigo LIKE :q_codigo OR nombre LIKE :q_nombre)';
            $likeQuery = '%' . $query . '%';
            $params['q_codigo'] = $likeQuery;
            $params['q_nombre'] = $likeQuery;
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

    public static function findById(int $id): ?array
    {
        $stmt = Database::connection()->prepare('SELECT * FROM programas WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        return $stmt->fetch() ?: null;
    }

    public static function countPendientesEnlace(): int
    {
        try {
            $stmt = Database::connection()->query('SELECT COUNT(*) FROM programa_enlaces_pendientes WHERE estado = "pendiente"');
            return (int) $stmt->fetchColumn();
        } catch (\Throwable) {
            // Compatibilidad temporal: si la migración 017 aún no fue ejecutada, no bloquea la vista.
            return 0;
        }
    }

    public static function findOrCreateFromImport(array $meta): int
    {
        $codigo = trim((string) ($meta['codigo'] ?? ''));
        $nombre = trim((string) ($meta['nombre'] ?? ''));
        $nivel = trim((string) ($meta['nivel'] ?? ''));
        $modalidad = trim((string) ($meta['modalidad'] ?? ''));

        $pdo = Database::connection();
        if ($codigo !== '') {
            $stmt = $pdo->prepare('SELECT id FROM programas WHERE codigo = :codigo LIMIT 1');
            $stmt->execute(['codigo' => $codigo]);
            $row = $stmt->fetch();
            if ($row) {
                return (int) $row['id'];
            }
        }
        if ($nombre !== '' && $nivel !== '') {
            $stmt = $pdo->prepare('SELECT id FROM programas WHERE nombre = :nombre AND nivel = :nivel LIMIT 1');
            $stmt->execute(['nombre' => $nombre, 'nivel' => $nivel]);
            $row = $stmt->fetch();
            if ($row) {
                return (int) $row['id'];
            }
        }

        $params = [
            'codigo' => $codigo !== '' ? $codigo : null,
            'nombre' => $nombre !== '' ? $nombre : 'Programa pendiente de nombre',
            'nivel' => $nivel,
            'modalidad' => $modalidad,
        ];
        $sql = 'INSERT INTO programas (codigo, nombre, nivel, modalidad, created_at, updated_at)
                VALUES (:codigo, :nombre, :nivel, :modalidad, NOW(), NOW())';
        try {
            $pdo->prepare($sql)->execute($params);
        } catch (\PDOException $e) {
            // Compatibilidad con esquemas antiguos que no tienen columna updated_at.
            if ((int) $e->getCode() !== 42 || stripos($e->getMessage(), 'updated_at') === false) {
                throw $e;
            }
            $sqlLegacy = 'INSERT INTO programas (codigo, nombre, nivel, modalidad, created_at)
                          VALUES (:codigo, :nombre, :nivel, :modalidad, NOW())';
            $pdo->prepare($sqlLegacy)->execute($params);
        }
        return (int) $pdo->lastInsertId();
    }
}
