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
        $where = [];
        $params = [];
        if (!empty($filters['estado'])) {
            $where[] = 'a.estado = :estado';
            $params['estado'] = $filters['estado'];
        }
        if (!empty($filters['q'])) {
            $where[] = '(a.nombre_completo LIKE :q OR a.numero_documento LIKE :q)';
            $params['q'] = '%' . $filters['q'] . '%';
        }
        $sql = 'SELECT a.* FROM aprendices a';
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
        $sql = 'UPDATE aprendices SET nombre_completo=:nombre_completo, telefono=:telefono, correo_personal=:correo_personal, correo_institucional=:correo_institucional, estado=:estado, updated_at=NOW() WHERE id=:id';
        Database::connection()->prepare($sql)->execute([
            'id' => $id,
            'nombre_completo' => $data['nombre_completo'],
            'telefono' => $data['telefono'] ?? null,
            'correo_personal' => $data['correo_personal'] ?? null,
            'correo_institucional' => $data['correo_institucional'] ?? null,
            'estado' => $data['estado'] ?? 'Pendiente por iniciar',
        ]);
    }

    public static function findById(int $id): ?array
    {
        $sql = "
            SELECT 
                a.*,
                e.nombre AS empresa_nombre,
                e.nit,
                e.direccion,
                e.nombre_jefe,
                e.cargo_jefe,
                e.telefono_jefe
            FROM aprendices a
            LEFT JOIN empresas e ON a.empresa_id = e.id
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
