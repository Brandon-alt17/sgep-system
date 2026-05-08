<?php

declare(strict_types=1);

namespace App\Models;

use App\Helpers\Database;
use PDO;

class Aprendiz
{
    public const ESTADOS_VALIDOS = [
        'Pendiente por iniciar',
        'En ejecución',
        'Aplazada',
        'Finalizada',
        'Por certificar',
        'Certificado',
        'Pendiente por comité',
    ];

    public static function paginated(array $filters, int $limit = 25, int $offset = 0): array
    {
        $pdo = Database::connection();
        ['where' => $where, 'params' => $params] = self::buildFiltersWhere($filters);

        $sql = '
            SELECT 
                a.*,
                e.nombre AS empresa_nombre,
                e.nit
            FROM aprendices a
            LEFT JOIN empresas e ON a.empresa_id = e.id
        ';

        if ($where !== []) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }

        $sql .= ' ORDER BY a.created_at DESC LIMIT :limit OFFSET :offset';

        $stmt = $pdo->prepare($sql);

        foreach ($params as $k => $v) {
            $stmt->bindValue(':' . $k, $v);
        }

        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);

        $stmt->execute();

        return $stmt->fetchAll();
    }

    public static function countFiltered(array $filters): int
    {
        $pdo = Database::connection();
        ['where' => $where, 'params' => $params] = self::buildFiltersWhere($filters);

        $sql = 'SELECT COUNT(*) AS total FROM aprendices a';
        if ($where !== []) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }

        $stmt = $pdo->prepare($sql);
        foreach ($params as $k => $v) {
            $stmt->bindValue(':' . $k, $v);
        }
        $stmt->execute();

        return (int) ($stmt->fetchColumn() ?: 0);
    }

    private static function buildFiltersWhere(array $filters): array
    {
        $where = [];
        $params = [];

        if (!empty($filters['estado'])) {
            $where[] = 'a.estado = :estado';
            $params['estado'] = $filters['estado'];
        }

        if (!empty($filters['ficha'])) {
            $where[] = 'a.ficha = :ficha';
            $params['ficha'] = $filters['ficha'];
        }

        $empresaId = (int) ($filters['empresa_id'] ?? 0);
        if ($empresaId > 0) {
            $where[] = 'a.empresa_id = :empresa_id';
            $params['empresa_id'] = $empresaId;
        }

        $programaId = (int) ($filters['programa_id'] ?? 0);
        if ($programaId > 0) {
            $where[] = 'a.programa_id = :programa_id';
            $params['programa_id'] = $programaId;
        }

        if (!empty($filters['q'])) {
            $where[] = '(a.nombre_completo LIKE :q_nombre OR a.numero_documento LIKE :q_documento)';
            $likeQ = '%' . $filters['q'] . '%';
            $params['q_nombre'] = $likeQ;
            $params['q_documento'] = $likeQ;
        }

        return ['where' => $where, 'params' => $params];
    }

    // Agregar método para obtener valores únicos de fichas
    public static function getUniqueFichas(): array
    {
        $stmt = Database::connection()->prepare('SELECT DISTINCT ficha FROM aprendices WHERE ficha IS NOT NULL AND ficha != "" ORDER BY ficha');
        $stmt->execute();
        return array_column($stmt->fetchAll(), 'ficha');
    }

    // Agregar método para obtener valores únicos de estados
    public static function getUniqueEstados(): array
    {
        $stmt = Database::connection()->prepare('SELECT DISTINCT estado FROM aprendices WHERE estado IS NOT NULL ORDER BY estado');
        $stmt->execute();
        return array_column($stmt->fetchAll(), 'estado');
    }

    public static function findByCedula(string $cedula): ?array
    {
        $stmt = Database::connection()->prepare('SELECT * FROM aprendices WHERE numero_documento = :cedula');
        $stmt->execute(['cedula' => $cedula]);
        return $stmt->fetch() ?: null;
    }

    public static function create(array $data): int
    {
        $sql = 'INSERT INTO aprendices (nombre_completo, tipo_documento, numero_documento, telefono, correo_personal, correo_institucional, programa_id, ficha, estado, created_at, updated_at)
                VALUES (:nombre_completo, :tipo_documento, :numero_documento, :telefono, :correo_personal, :correo_institucional, :programa_id, :ficha, :estado, NOW(), NOW())';
        $pdo = Database::connection();
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            'nombre_completo' => $data['nombre_completo'],
            'tipo_documento' => $data['tipo_documento'],
            'numero_documento' => $data['numero_documento'],
            'telefono' => $data['telefono'] ?? null,
            'correo_personal' => $data['correo_personal'] ?? null,
            'correo_institucional' => $data['correo_institucional'] ?? null,
            'programa_id' => $data['programa_id'] ?? null,
            'ficha' => $data['ficha'] ?? null,
            'estado' => $data['estado'] ?? 'Pendiente por iniciar',
        ]);
        return (int) $pdo->lastInsertId();
    }

    public static function update(int $id, array $data): void
    {
        $empresaId = (int) ($data['empresa_id'] ?? 0);
        if ($empresaId <= 0) {
            $stmt = Database::connection()->prepare('SELECT empresa_id FROM aprendices WHERE id = :id LIMIT 1');
            $stmt->execute(['id' => $id]);
            $row = $stmt->fetch();
            $empresaId = (int) ($row['empresa_id'] ?? 0);
        }

        $jefeIdResolved = self::resolveJefeIdFromUpdatePayload($empresaId, $data);

        $sql = 'UPDATE aprendices SET nombre_completo=:nombre_completo, telefono=:telefono, correo_personal=:correo_personal, correo_institucional=:correo_institucional, estado=:estado, jefe_id=:jefe_id, updated_at=NOW() WHERE id=:id';
        Database::connection()->prepare($sql)->execute([
            'id' => $id,
            'nombre_completo' => $data['nombre_completo'],
            'telefono' => $data['telefono'] ?? null,
            'correo_personal' => $data['correo_personal'] ?? null,
            'correo_institucional' => $data['correo_institucional'] ?? null,
            'estado' => $data['estado'] ?? 'Pendiente por iniciar',
            'jefe_id' => $jefeIdResolved,
        ]);
    }

    /**
     * @param array<string, mixed> $data
     */
    private static function resolveJefeIdFromUpdatePayload(int $empresaId, array $data): ?int
    {
        $jefeIdRaw = trim((string) ($data['jefe_id'] ?? ''));

        if ($jefeIdRaw === '' || $jefeIdRaw === '0') {
            return null;
        }

        if ($jefeIdRaw === '__new__') {
            if ($empresaId <= 0) {
                return null;
            }

            return EmpresaJefe::findOrCreate($empresaId, [
                'nombre' => $data['nombre_jefe'] ?? '',
                'cargo' => $data['cargo_jefe'] ?? '',
                'correo' => $data['correo_jefe'] ?? '',
                'telefono' => $data['telefono_jefe'] ?? '',
                'nombre_contacto2' => $data['nombre_contacto2_jefe'] ?? '',
                'correo_contacto2' => $data['correo_contacto2_jefe'] ?? '',
            ]);
        }

        $jefeId = (int) $jefeIdRaw;
        if ($jefeId <= 0) {
            return null;
        }

        $jefe = EmpresaJefe::findById($jefeId);
        if ($jefe === null) {
            return null;
        }
        if ($empresaId > 0 && (int) ($jefe['empresa_id'] ?? 0) !== $empresaId) {
            return null;
        }

        return $jefeId;
    }

    public static function findById(int $id): ?array
    {
        $sql = "
            SELECT 
                a.*,
                e.nombre AS empresa_nombre,
                e.nit,
                e.direccion,
                j.nombre AS nombre_jefe,
                j.cargo AS cargo_jefe,
                j.telefono AS telefono_jefe,
                j.correo AS correo_jefe,
                j.nombre_contacto2 AS nombre_contacto2_jefe,
                j.correo_contacto2 AS correo_contacto2_jefe
            FROM aprendices a
            LEFT JOIN empresas e ON a.empresa_id = e.id
            LEFT JOIN empresa_jefes j ON a.jefe_id = j.id
            WHERE a.id = :id
        ";

        $stmt = Database::connection()->prepare($sql);
        $stmt->execute(['id' => $id]);

        return $stmt->fetch() ?: null;
    }

    public static function updateEstado(int $id, string $nuevoEstado, ?string $motivo = null): void
    {
        if (!in_array($nuevoEstado, self::ESTADOS_VALIDOS, true)) {
            throw new \InvalidArgumentException('Estado no válido.');
        }
        $pdo = Database::connection();
        $actual = self::findById($id);
        $pdo->beginTransaction();
        try {
            $pdo->prepare('UPDATE aprendices SET estado=:estado, updated_at=NOW() WHERE id=:id')
                ->execute(['estado' => $nuevoEstado, 'id' => $id]);
            $pdo->prepare('INSERT INTO estados_historial (aprendiz_id, estado_anterior, estado_nuevo, motivo, created_at) VALUES (:id, :anterior, :nuevo, :motivo, NOW())')
                ->execute([
                    'id' => $id,
                    'anterior' => $actual['estado'] ?? null,
                    'nuevo' => $nuevoEstado,
                    'motivo' => $motivo,
                ]);
            $pdo->commit();
        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }
}