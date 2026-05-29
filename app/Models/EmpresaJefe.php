<?php

declare(strict_types=1);

namespace App\Models;

use App\Helpers\Database;

class EmpresaJefe
{
    private const MAX_NOMBRE = 160;
    private const MAX_CARGO = 120;
    private const MAX_CORREO = 150;
    private const MAX_TELEFONO = 30;

    /**
     * @param array{nombre?: mixed, cargo?: mixed, correo?: mixed, telefono?: mixed, nombre_contacto2?: mixed, correo_contacto2?: mixed} $data
     */
    public static function updateByIdForEmpresa(int $empresaId, int $jefeId, array $data): void
    {
        if ($empresaId <= 0 || $jefeId <= 0) {
            return;
        }

        $nombre = self::clampField((string) ($data['nombre'] ?? ''), self::MAX_NOMBRE);
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
            'cargo' => self::clampOptionalField($data['cargo'] ?? null, self::MAX_CARGO),
            'correo' => self::clampOptionalField($data['correo'] ?? null, self::MAX_CORREO),
            'telefono' => self::clampOptionalField($data['telefono'] ?? null, self::MAX_TELEFONO),
            'nombre_contacto2' => self::clampOptionalField($data['nombre_contacto2'] ?? null, self::MAX_NOMBRE),
            'correo_contacto2' => self::clampOptionalField($data['correo_contacto2'] ?? null, self::MAX_CORREO),
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

        $maxByColumn = [
            'nombre' => self::MAX_NOMBRE,
            'cargo' => self::MAX_CARGO,
            'correo' => self::MAX_CORREO,
            'telefono' => self::MAX_TELEFONO,
        ];
        if ($value !== null && $value !== '') {
            $value = self::clampField($value, $maxByColumn[$column] ?? 255);
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

    public static function findByIdForEmpresa(int $empresaId, int $jefeId): ?array
    {
        if ($empresaId <= 0 || $jefeId <= 0) {
            return null;
        }
        $stmt = Database::connection()->prepare(
            'SELECT * FROM empresa_jefes WHERE id = :id AND empresa_id = :empresa_id LIMIT 1'
        );
        $stmt->execute(['id' => $jefeId, 'empresa_id' => $empresaId]);
        $row = $stmt->fetch();

        return $row ?: null;
    }

    /**
     * @param array{nombre?: mixed, cargo?: mixed, correo?: mixed, telefono?: mixed, nombre_contacto2?: mixed, correo_contacto2?: mixed} $data
     */
    public static function createForEmpresa(int $empresaId, array $data): ?int
    {
        if ($empresaId <= 0) {
            return null;
        }

        $nombre = self::clampField((string) ($data['nombre'] ?? ''), self::MAX_NOMBRE);
        if ($nombre === '') {
            return null;
        }

        $pdo = Database::connection();
        $ins = $pdo->prepare(
            'INSERT INTO empresa_jefes (empresa_id, nombre, cargo, correo, telefono, nombre_contacto2, correo_contacto2, created_at, updated_at)
             VALUES (:empresa_id, :nombre, :cargo, :correo, :telefono, :nombre_contacto2, :correo_contacto2, NOW(), NOW())'
        );
        $ins->execute([
            'empresa_id' => $empresaId,
            'nombre' => $nombre,
            'cargo' => self::clampOptionalField($data['cargo'] ?? null, self::MAX_CARGO),
            'correo' => self::clampOptionalField($data['correo'] ?? null, self::MAX_CORREO),
            'telefono' => self::clampOptionalField($data['telefono'] ?? null, self::MAX_TELEFONO),
            'nombre_contacto2' => self::clampOptionalField($data['nombre_contacto2'] ?? null, self::MAX_NOMBRE),
            'correo_contacto2' => self::clampOptionalField($data['correo_contacto2'] ?? null, self::MAX_CORREO),
        ]);

        return (int) $pdo->lastInsertId();
    }

    public static function countAprendicesLinked(int $jefeId): int
    {
        if ($jefeId <= 0) {
            return 0;
        }
        $stmt = Database::connection()->prepare('SELECT COUNT(*) FROM aprendices WHERE jefe_id = :id');
        $stmt->execute(['id' => $jefeId]);

        return (int) $stmt->fetchColumn();
    }

    public static function deleteByIdForEmpresa(int $empresaId, int $jefeId): bool
    {
        if ($empresaId <= 0 || $jefeId <= 0) {
            return false;
        }
        if (self::findByIdForEmpresa($empresaId, $jefeId) === null) {
            return false;
        }
        if (self::countAprendicesLinked($jefeId) > 0) {
            return false;
        }

        $stmt = Database::connection()->prepare(
            'DELETE FROM empresa_jefes WHERE id = :id AND empresa_id = :empresa_id'
        );
        $stmt->execute(['id' => $jefeId, 'empresa_id' => $empresaId]);

        return $stmt->rowCount() > 0;
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
        $nombre = self::clampField((string) ($data['nombre'] ?? ''), self::MAX_NOMBRE);
        if ($nombre === '') {
            return null;
        }

        $cargo = self::clampOptionalField($data['cargo'] ?? null, self::MAX_CARGO);
        $correo = self::clampOptionalField($data['correo'] ?? null, self::MAX_CORREO);
        $telefono = self::clampOptionalField($data['telefono'] ?? null, self::MAX_TELEFONO);
        $nombreContacto2 = self::clampOptionalField($data['nombre_contacto2'] ?? null, self::MAX_NOMBRE);
        $correoContacto2 = self::clampOptionalField($data['correo_contacto2'] ?? null, self::MAX_CORREO);

        $nombreKey = self::normalizeComparableName($nombre);

        $pdo = Database::connection();
        $stmt = $pdo->prepare(
            'SELECT id, nombre, correo, telefono, cargo FROM empresa_jefes WHERE empresa_id = :eid ORDER BY id ASC'
        );
        $stmt->execute(['eid' => $empresaId]);
        $candidates = $stmt->fetchAll() ?: [];

        foreach ($candidates as $row) {
            if (!self::matchesImportSupervisorRow($row, $nombreKey, $cargo, $correo, $telefono)) {
                continue;
            }
            self::patchAltContactIfMissing((int) $row['id'], $nombreContacto2, $correoContacto2);

            return (int) $row['id'];
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

    private static function clampField(string $value, int $maxLength): string
    {
        $value = trim($value);
        if ($value === '' || $maxLength <= 0) {
            return '';
        }
        if (mb_strlen($value) <= $maxLength) {
            return $value;
        }

        return mb_substr($value, 0, $maxLength);
    }

    private static function clampOptionalField(mixed $value, int $maxLength): ?string
    {
        $normalized = self::nullIfEmptyString($value);
        if ($normalized === null) {
            return null;
        }

        return self::clampField($normalized, $maxLength) ?: null;
    }

    /**
     * Identidad del jefe: nombre normalizado + correo (si ambos presentes deben coincidir).
     * Cargo y teléfono son atributos resolubles por UI, no discriminadores de identidad.
     *
     * @param array<string, mixed> $row
     */
    private static function matchesImportSupervisorRow(
        array $row,
        string $nombreKey,
        ?string $cargo,
        ?string $correo,
        ?string $telefono,
    ): bool {
        if (self::normalizeComparableName((string) ($row['nombre'] ?? '')) !== $nombreKey) {
            return false;
        }

        $rowCorreo = self::nullIfEmptyString($row['correo'] ?? null);

        // Si el archivo trae correo, debe coincidir con el de BD (o BD no tiene correo aún)
        if ($correo !== null && $rowCorreo !== null
            && mb_strtolower(trim($correo)) !== mb_strtolower(trim((string) $rowCorreo))) {
            return false;
        }
        // Si el archivo trae correo y BD no tiene, no reutilizar (podría ser otra persona sin correo)
        if ($correo !== null && $rowCorreo === null) {
            return false;
        }

        return true;
    }

    private static function normalizeComparablePhone(?string $value): string
    {
        if ($value === null) {
            return '';
        }

        return preg_replace('/\D+/', '', trim($value)) ?? '';
    }

    private static function normalizeComparableCargo(?string $value): string
    {
        if ($value === null) {
            return '';
        }

        $normalized = preg_replace('/\s+/', ' ', trim($value)) ?? trim($value);
        $normalized = mb_strtolower($normalized);
        $normalized = str_replace(
            ['á', 'é', 'í', 'ó', 'ú', 'ü', 'ñ'],
            ['a', 'e', 'i', 'o', 'u', 'u', 'n'],
            $normalized
        );
        $normalized = preg_replace('/[^a-z0-9\s]/u', '', $normalized) ?? $normalized;
        $parts = explode(' ', $normalized);
        if ($parts !== []) {
            $last = (string) end($parts);
            if (strlen($last) > 3 && str_ends_with($last, 's')) {
                $parts[count($parts) - 1] = substr($last, 0, -1);
                $normalized = implode(' ', $parts);
            }
        }

        return $normalized;
    }

    private static function normalizeComparableName(string $value): string
    {
        $normalized = preg_replace('/\s+/', ' ', trim($value)) ?? trim($value);
        $normalized = mb_strtolower($normalized);

        $normalized = str_replace(
            ['á', 'é', 'í', 'ó', 'ú', 'ü', 'ñ'],
            ['a', 'e', 'i', 'o', 'u', 'u', 'n'],
            $normalized
        );

        return preg_replace('/[^a-z0-9\s]/u', '', $normalized) ?? $normalized;
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
            'nombre_contacto2' => self::clampOptionalField($nombreContacto2, self::MAX_NOMBRE),
            'correo_contacto2' => self::clampOptionalField($correoContacto2, self::MAX_CORREO),
        ]);
    }
}
