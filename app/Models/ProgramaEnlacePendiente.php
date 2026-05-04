<?php

declare(strict_types=1);

namespace App\Models;

use App\Helpers\Database;

class ProgramaEnlacePendiente
{
    /** @param array<string,mixed> $data */
    public static function createOrIgnorePending(array $data): void
    {
        $doc = trim((string) ($data['numero_documento'] ?? ''));
        $programaFuente = trim((string) ($data['programa_fuente'] ?? ''));
        if ($doc === '' || $programaFuente === '') {
            return;
        }

        $pdo = Database::connection();
        $check = $pdo->prepare('SELECT id FROM programa_enlaces_pendientes WHERE numero_documento = :doc AND programa_fuente = :programa AND estado = "pendiente" LIMIT 1');
        $check->execute([
            'doc' => $doc,
            'programa' => $programaFuente,
        ]);
        if ($check->fetch()) {
            return;
        }

        $sql = 'INSERT INTO programa_enlaces_pendientes
                (numero_documento, nombre_aprendiz, programa_fuente, nivel_fuente, modalidad_fuente, candidatos_json, motivo, estado, created_at, updated_at)
                VALUES
                (:numero_documento, :nombre_aprendiz, :programa_fuente, :nivel_fuente, :modalidad_fuente, :candidatos_json, :motivo, "pendiente", NOW(), NOW())';
        $pdo->prepare($sql)->execute([
            'numero_documento' => $doc,
            'nombre_aprendiz' => trim((string) ($data['nombre_aprendiz'] ?? '')),
            'programa_fuente' => $programaFuente,
            'nivel_fuente' => trim((string) ($data['nivel_fuente'] ?? '')),
            'modalidad_fuente' => trim((string) ($data['modalidad_fuente'] ?? '')),
            'candidatos_json' => (string) ($data['candidatos_json'] ?? '[]'),
            'motivo' => trim((string) ($data['motivo'] ?? 'not_found')),
        ]);
    }

    /** @return array<int,array<string,mixed>> */
    public static function pendingList(array $filters = []): array
    {
        $where = ['pep.estado = "pendiente"'];
        $params = [];
        $q = trim((string) ($filters['q'] ?? ''));
        if ($q !== '') {
            $like = '%' . $q . '%';
            $where[] = '(pep.numero_documento LIKE :q_doc OR pep.nombre_aprendiz LIKE :q_nom OR pep.programa_fuente LIKE :q_prog)';
            $params['q_doc'] = $like;
            $params['q_nom'] = $like;
            $params['q_prog'] = $like;
        }

        $sql = 'SELECT pep.*, a.id AS aprendiz_id, p.nombre AS programa_nombre_destino
                FROM programa_enlaces_pendientes pep
                LEFT JOIN aprendices a ON a.numero_documento = pep.numero_documento
                LEFT JOIN programas p ON p.id = pep.programa_id_destino
                WHERE ' . implode(' AND ', $where) . '
                ORDER BY pep.created_at DESC';
        $stmt = Database::connection()->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public static function resolve(int $pendingId, int $programaId): bool
    {
        $pdo = Database::connection();
        $pdo->beginTransaction();
        try {
            $stmt = $pdo->prepare('SELECT * FROM programa_enlaces_pendientes WHERE id = :id AND estado = "pendiente" LIMIT 1');
            $stmt->execute(['id' => $pendingId]);
            $pending = $stmt->fetch();
            if (!$pending) {
                $pdo->rollBack();
                return false;
            }

            $doc = (string) ($pending['numero_documento'] ?? '');
            if ($doc !== '') {
                $pdo->prepare('UPDATE aprendices SET programa_id = :programa_id, updated_at = NOW() WHERE numero_documento = :doc')
                    ->execute(['programa_id' => $programaId, 'doc' => $doc]);
            }

            $pdo->prepare('UPDATE programa_enlaces_pendientes
                           SET estado = "resuelto", programa_id_destino = :programa_id, resolved_at = NOW(), updated_at = NOW()
                           WHERE id = :id')
                ->execute(['programa_id' => $programaId, 'id' => $pendingId]);

            $pdo->commit();
            return true;
        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }
}
