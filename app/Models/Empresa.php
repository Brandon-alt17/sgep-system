<?php

declare(strict_types=1);

namespace App\Models;

use App\Helpers\Database;

class Empresa
{
    public static function findByAprendiz(int $aprendizId): ?array
    {
        $sql = 'SELECT e.* FROM empresas e INNER JOIN aprendices a ON a.empresa_id = e.id WHERE a.id = :id';
        $stmt = Database::connection()->prepare($sql);
        $stmt->execute(['id' => $aprendizId]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public static function findById(int $id): ?array
    {
        if ($id <= 0) {
            return null;
        }
        $stmt = Database::connection()->prepare('SELECT * FROM empresas WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    /**
     * @param array{q?: string} $filters
     * @return list<array<string, mixed>>
     */
    public static function catalogo(array $filters = []): array
    {
        $q = trim((string) ($filters['q'] ?? ''));
        $where = [];
        $params = [];
        if ($q !== '') {
            $where[] = '(e.nombre LIKE :q_nombre OR COALESCE(e.nit, \'\') LIKE :q_nit OR COALESCE(e.ciudad, \'\') LIKE :q_ciudad)';
            $likeQ = '%' . $q . '%';
            $params['q_nombre'] = $likeQ;
            $params['q_nit'] = $likeQ;
            $params['q_ciudad'] = $likeQ;
        }
        $sql = 'SELECT e.*,
                (SELECT COUNT(*) FROM aprendices a WHERE a.empresa_id = e.id) AS aprendices_count
                FROM empresas e';
        if ($where !== []) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }
        $sql .= ' ORDER BY e.nombre ASC';
        $stmt = Database::connection()->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll() ?: [];
    }

    public static function countAprendices(int $empresaId): int
    {
        if ($empresaId <= 0) {
            return 0;
        }
        $stmt = Database::connection()->prepare('SELECT COUNT(*) FROM aprendices WHERE empresa_id = :id');
        $stmt->execute(['id' => $empresaId]);
        return (int) $stmt->fetchColumn();
    }

    /** @param array<string, mixed> $data */
    public static function create(array $data): int
    {
        $pdo = Database::connection();
        $sql = 'INSERT INTO empresas (
            nombre, nit, direccion, ciudad, correo_org,
            nombre_contacto2, correo_contacto2, direccion_practica,
            created_at, updated_at
        ) VALUES (
            :nombre, :nit, :direccion, :ciudad, :correo_org,
            :nombre_contacto2, :correo_contacto2, :direccion_practica,
            NOW(), NOW()
        )';
        $stmt = $pdo->prepare($sql);
        $stmt->execute(self::bindableRow($data));
        return (int) $pdo->lastInsertId();
    }

    /** @param array<string, mixed> $data */
    public static function update(int $id, array $data): void
    {
        if ($id <= 0) {
            return;
        }
        $existing = self::findById($id);
        if ($existing === null) {
            return;
        }
        $merged = array_replace($existing, $data);
        $sql = 'UPDATE empresas SET
            nombre = :nombre,
            nit = :nit,
            direccion = :direccion,
            ciudad = :ciudad,
            correo_org = :correo_org,
            nombre_contacto2 = :nombre_contacto2,
            correo_contacto2 = :correo_contacto2,
            direccion_practica = :direccion_practica,
            nombre_jefe = :nombre_jefe,
            cargo_jefe = :cargo_jefe,
            telefono_jefe = :telefono_jefe,
            correo_jefe = :correo_jefe,
            updated_at = NOW()
            WHERE id = :id';
        $row = self::bindableRow($merged);
        $row['id'] = $id;
        $stmt = Database::connection()->prepare($sql);
        $stmt->execute($row);
    }

    public static function deleteById(int $id): bool
    {
        if ($id <= 0) {
            return false;
        }
        if (self::countAprendices($id) > 0) {
            return false;
        }
        $stmt = Database::connection()->prepare('DELETE FROM empresas WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        return $stmt->rowCount() > 0;
    }

    /**
     * @param array<string, mixed> $data
     * @return array<string, string|null>
     */
    private static function bindableRow(array $data): array
    {
        $nullIfEmpty = static function (mixed $v): ?string {
            $s = trim((string) $v);
            return $s === '' ? null : $s;
        };

        return [
            'nombre' => trim((string) ($data['nombre'] ?? '')),
            'nit' => $nullIfEmpty($data['nit'] ?? null),
            'direccion' => $nullIfEmpty($data['direccion'] ?? null),
            'ciudad' => $nullIfEmpty($data['ciudad'] ?? null),
            'correo_org' => $nullIfEmpty($data['correo_org'] ?? null),
            'nombre_contacto2' => $nullIfEmpty($data['nombre_contacto2'] ?? null),
            'correo_contacto2' => $nullIfEmpty($data['correo_contacto2'] ?? null),
            'direccion_practica' => $nullIfEmpty($data['direccion_practica'] ?? null),
            'nombre_jefe' => $nullIfEmpty($data['nombre_jefe'] ?? null),
            'cargo_jefe' => $nullIfEmpty($data['cargo_jefe'] ?? null),
            'telefono_jefe' => $nullIfEmpty($data['telefono_jefe'] ?? null),
            'correo_jefe' => $nullIfEmpty($data['correo_jefe'] ?? null),
        ];
    }
}
