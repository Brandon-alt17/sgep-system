<?php

declare(strict_types=1);

namespace App\Models;

use App\Helpers\Database;

class EmpresaJefe
{
    /**
     * @param array{nombre?: mixed, cargo?: mixed, correo?: mixed, telefono?: mixed, nombre_contacto2?: mixed, correo_contacto2?: mixed} $data
     */
    public static function updateByIdForEmpresa(int $empresaId, int $jefeId, array $data): void
    {
        if ($empresaId <= 0 || $jefeId <= 0) {
            return;
        }

        $nombre = trim((string) ($data['nombre'] ?? ''));
        if ($nombre === '') {
            return;
        }

        $sql = 'UPDATE empresa_jefes
                SET nombre = :nombre,
                    cargo = :cargo,
                    correo = :correo,
                    telefono = :telefono,
                    nombre_contacto2 = :nombre_contacto2,
                    correo_contacto2 = :correo_contacto2,
                    updated_at = NOW()
                WHERE id = :id AND empresa_id = :empresa_id';

        Database::connection()->prepare($sql)->execute([
            'id' => $jefeId,
            'empresa_id' => $empresaId,
            'nombre' => $nombre,
            'cargo' => self::nullIfEmptyString($data['cargo'] ?? null),
            'correo' => self::nullIfEmptyString($data['correo'] ?? null),
            'telefono' => self::nullIfEmptyString($data['telefono'] ?? null),
            'nombre_contacto2' => self::nullIfEmptyString($data['nombre_contacto2'] ?? null),
            'correo_contacto2' => self::nullIfEmptyString($data['correo_contacto2'] ?? null),
        ]);
    }

    public static function patchColumnForEmpresa(int $empresaId, int $jefeId, string $column, ?string $value): bool
    {
        $allowed = ['nombre', 'cargo', 'correo', 'telefono'];
        if ($empresaId <= 0 || $jefeId <= 0 || !in_array($column, $allowed, true)) {
            return false;
        }

        $value = $value !== null ? trim($value) : null;
        if ($column === 'nombre' && ($value === null || $value === '')) {
            return false;
        }

        $sql = 'UPDATE empresa_jefes SET ' . $column . ' = :value, updated_at = NOW() WHERE id = :id AND empresa_id = :empresa_id';
        $stmt = Database::connection()->prepare($sql);

        return $stmt->execute([
            'value' => $value === '' ? null : $value,
            'id' => $jefeId,
            'empresa_id' => $empresaId,
        ]);
    }

    public static function findById(int $id): ?array
    {
        if ($id <= 0) {
            return null;
        }
        $stmt = Database::connection()->prepare('SELECT * FROM empresa_jefes WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();

        return $row ?: null;
    }

    /**
     * @return list<array<string, mixed>>
     */
    public static function listByEmpresa(int $empresaId): array
    {
        if ($empresaId <= 0) {
            return [];
        }
        $stmt = Database::connection()->prepare(
            'SELECT * FROM empresa_jefes WHERE empresa_id = :eid ORDER BY nombre ASC'
        );
        $stmt->execute(['eid' => $empresaId]);
        $rows = $stmt->fetchAll();

        return is_array($rows) ? $rows : [];
    }

    /**
     * Busca un jefe existente por empresa + nombre (y correo si ambos tienen valor) o crea uno nuevo.
     * Devuelve null si no hay nombre de jefe.
     *
     * @param array{nombre?: mixed, cargo?: mixed, correo?: mixed, telefono?: mixed, nombre_contacto2?: mixed, correo_contacto2?: mixed} $data
     */
    public static function findOrCreate(int $empresaId, array $data): ?int
    {
        if ($empresaId <= 0) {
            return null;
        }
        $nombre = trim((string) ($data['nombre'] ?? ''));
        if ($nombre === '') {
            return null;
        }

        $cargo = self::nullIfEmptyString($data['cargo'] ?? null);
        $correo = self::nullIfEmptyString($data['correo'] ?? null);
        $telefono = self::nullIfEmptyString($data['telefono'] ?? null);
        $nombreContacto2 = self::nullIfEmptyString($data['nombre_contacto2'] ?? null);
        $correoContacto2 = self::nullIfEmptyString($data['correo_contacto2'] ?? null);

        $nombreKey = self::normalizeComparableName($nombre);

        $pdo = Database::connection();
        $stmt = $pdo->prepare('SELECT id, nombre, correo FROM empresa_jefes WHERE empresa_id = :eid');
        $stmt->execute(['eid' => $empresaId]);
        $candidates = $stmt->fetchAll() ?: [];

        if ($correo !== null) {
            $correoKey = mb_strtolower(trim($correo));
            foreach ($candidates as $row) {
                $rowCorreo = self::nullIfEmptyString($row['correo'] ?? null);
                if ($rowCorreo === null) {
                    continue;
                }
                if (mb_strtolower(trim($rowCorreo)) === $correoKey) {
                    self::patchAltContactIfMissing((int) $row['id'], $nombreContacto2, $correoContacto2);

                    return (int) $row['id'];
                }
            }
        }

        foreach ($candidates as $row) {
            $rowNombre = self::normalizeComparableName((string) ($row['nombre'] ?? ''));
            if ($rowNombre !== $nombreKey) {
                continue;
            }
            $rowCorreo = self::nullIfEmptyString($row['correo'] ?? null);
            if ($correo === null && $rowCorreo === null) {
                self::patchAltContactIfMissing((int) $row['id'], $nombreContacto2, $correoContacto2);
                return (int) $row['id'];
            }
            if ($correo !== null && $rowCorreo !== null && mb_strtolower(trim($correo)) === mb_strtolower(trim((string) $rowCorreo))) {
                self::patchAltContactIfMissing((int) $row['id'], $nombreContacto2, $correoContacto2);
                return (int) $row['id'];
            }
        }

        $ins = $pdo->prepare(
            'INSERT INTO empresa_jefes (empresa_id, nombre, cargo, correo, telefono, nombre_contacto2, correo_contacto2, created_at, updated_at)
             VALUES (:empresa_id, :nombre, :cargo, :correo, :telefono, :nombre_contacto2, :correo_contacto2, NOW(), NOW())'
        );
        $ins->execute([
            'empresa_id' => $empresaId,
            'nombre' => $nombre,
            'cargo' => $cargo,
            'correo' => $correo,
            'telefono' => $telefono,
            'nombre_contacto2' => $nombreContacto2,
            'correo_contacto2' => $correoContacto2,
        ]);

        return (int) $pdo->lastInsertId();
    }

    private static function nullIfEmptyString(mixed $v): ?string
    {
        if ($v === null) {
            return null;
        }
        $s = trim((string) $v);

        return $s === '' ? null : $s;
    }

    private static function normalizeComparableName(string $value): string
    {
        $normalized = preg_replace('/\s+/', ' ', trim($value)) ?? trim($value);
        $normalized = mb_strtolower($normalized);

        return str_replace(
            ['á', 'é', 'í', 'ó', 'ú', 'ü'],
            ['a', 'e', 'i', 'o', 'u', 'u'],
            $normalized
        );
    }

    private static function patchAltContactIfMissing(int $jefeId, ?string $nombreContacto2, ?string $correoContacto2): void
    {
        if ($jefeId <= 0 || ($nombreContacto2 === null && $correoContacto2 === null)) {
            return;
        }
        $sql = 'UPDATE empresa_jefes
                SET nombre_contacto2 = COALESCE(nombre_contacto2, :nombre_contacto2),
                    correo_contacto2 = COALESCE(correo_contacto2, :correo_contacto2),
                    updated_at = NOW()
                WHERE id = :id';
        Database::connection()->prepare($sql)->execute([
            'id' => $jefeId,
            'nombre_contacto2' => $nombreContacto2,
            'correo_contacto2' => $correoContacto2,
        ]);
    }
}
