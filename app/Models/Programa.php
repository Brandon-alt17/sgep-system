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

        $sql = 'SELECT id, codigo, nombre, nivel FROM programas';
        if ($where !== []) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }
        $sql .= ' ORDER BY nombre ASC';

        $stmt = Database::connection()->prepare($sql);
        $stmt->execute($params);
        $programas = $stmt->fetchAll();
        if ($programas === []) {
            return [];
        }

        foreach ($programas as &$programa) {
            $programa['horas_total'] = '';
        }
        unset($programa);

        $programaIds = array_values(array_filter(array_map(
            static fn (array $row): int => (int) ($row['id'] ?? 0),
            $programas
        )));
        if ($programaIds === []) {
            return $programas;
        }

        try {
            $placeholders = implode(',', array_fill(0, count($programaIds), '?'));
            $hoursSql = "SELECT i.programa_id,
                                TRIM(COALESCE(JSON_UNQUOTE(JSON_EXTRACT(i.resumen_json, '$.meta.horas_total')), '')) AS horas_total
                         FROM programa_importaciones_pdf i
                         INNER JOIN (
                           SELECT programa_id, MAX(id) AS max_id
                           FROM programa_importaciones_pdf
                           WHERE programa_id IN ($placeholders)
                           GROUP BY programa_id
                         ) latest ON latest.max_id = i.id";
            $hoursStmt = Database::connection()->prepare($hoursSql);
            $hoursStmt->execute($programaIds);
            $hoursRows = $hoursStmt->fetchAll();

            $hoursByProgramaId = [];
            foreach ($hoursRows as $row) {
                $pid = (int) ($row['programa_id'] ?? 0);
                $hours = trim((string) ($row['horas_total'] ?? ''));
                if ($pid > 0 && $hours !== '') {
                    $hoursByProgramaId[$pid] = $hours;
                }
            }

            foreach ($programas as &$programa) {
                $pid = (int) ($programa['id'] ?? 0);
                $programa['horas_total'] = $hoursByProgramaId[$pid] ?? '';
            }
            unset($programa);
        } catch (\Throwable) {
            // Compatibilidad: si no existe tabla de importaciones o no soporta funciones JSON/ventana.
        }

        return $programas;
    }

    public static function findById(int $id): ?array
    {
        $stmt = Database::connection()->prepare('SELECT * FROM programas WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        return $stmt->fetch() ?: null;
    }

    /** Coincidencia exacta de nombre + nivel (índice único en BD). */
    public static function findIdByNombreNivel(string $nombre, string $nivel, int $excludeId = 0): ?int
    {
        $nombre = trim($nombre);
        $nivel = trim($nivel);
        if ($nombre === '' || $nivel === '') {
            return null;
        }
        $sql = 'SELECT id FROM programas WHERE nombre = :nombre AND nivel = :nivel';
        $params = ['nombre' => $nombre, 'nivel' => $nivel];
        if ($excludeId > 0) {
            $sql .= ' AND id <> :exclude_id';
            $params['exclude_id'] = $excludeId;
        }
        $sql .= ' LIMIT 1';
        $stmt = Database::connection()->prepare($sql);
        $stmt->execute($params);
        $row = $stmt->fetch();
        return $row ? (int) ($row['id'] ?? 0) : null;
    }

    public static function create(array $data): int
    {
        $pdo = Database::connection();
        $stmt = $pdo->prepare(
            'INSERT INTO programas (codigo, nombre, nivel, modalidad, created_at)
             VALUES (:codigo, :nombre, :nivel, :modalidad, NOW())'
        );
        $stmt->execute([
            'codigo' => self::nullableTrim($data['codigo'] ?? ''),
            'nombre' => trim((string) ($data['nombre'] ?? '')),
            'nivel' => self::nullableTrim($data['nivel'] ?? ''),
            'modalidad' => self::nullableTrim($data['modalidad'] ?? ''),
        ]);
        return (int) $pdo->lastInsertId();
    }

    public static function update(int $id, array $data): void
    {
        $stmt = Database::connection()->prepare(
            'UPDATE programas
             SET codigo = :codigo,
                 nombre = :nombre,
                 nivel = :nivel,
                 modalidad = :modalidad
             WHERE id = :id'
        );
        $stmt->execute([
            'id' => $id,
            'codigo' => self::nullableTrim($data['codigo'] ?? ''),
            'nombre' => trim((string) ($data['nombre'] ?? '')),
            'nivel' => self::nullableTrim($data['nivel'] ?? ''),
            'modalidad' => self::nullableTrim($data['modalidad'] ?? ''),
        ]);
    }

    public static function deleteById(int $id): void
    {
        $stmt = Database::connection()->prepare('DELETE FROM programas WHERE id = :id');
        $stmt->execute(['id' => $id]);
    }

    public static function countPendientesEnlace(): int
    {
        try {
            ProgramaEnlacePendiente::syncAprendicesSinVinculoValido();

            return ProgramaEnlacePendiente::countAbiertos();
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

    private static function nullableTrim(mixed $value): ?string
    {
        $trimmed = trim((string) $value);
        return $trimmed === '' ? null : $trimmed;
    }
}
