<?php

declare(strict_types=1);

namespace App\Models;

use App\Helpers\Database;
use App\Services\ProgramaCatalogMatcher;

class ProgramaEnlacePendiente
{
    public static function aprendizHasValidProgramaVinculo(string $numeroDocumento): bool
    {
        $doc = trim($numeroDocumento);
        if ($doc === '') {
            return false;
        }

        $stmt = Database::connection()->prepare(
            'SELECT a.programa_id
             FROM aprendices a
             INNER JOIN programas p ON p.id = a.programa_id
             WHERE a.numero_documento = :doc
             LIMIT 1'
        );
        $stmt->execute(['doc' => $doc]);
        $row = $stmt->fetch();

        return is_array($row) && (int) ($row['programa_id'] ?? 0) > 0;
    }

    /**
     * Cierra pendientes abiertos cuando el aprendiz ya tiene programa_id válido en catálogo.
     */
    public static function closePendingIfAprendizVinculado(string $numeroDocumento): void
    {
        $doc = trim($numeroDocumento);
        if ($doc === '' || !self::aprendizHasValidProgramaVinculo($doc)) {
            return;
        }

        $pdo = Database::connection();
        $stmt = $pdo->prepare(
            'SELECT programa_id FROM aprendices WHERE numero_documento = :doc LIMIT 1'
        );
        $stmt->execute(['doc' => $doc]);
        $row = $stmt->fetch();
        $programaId = is_array($row) ? (int) ($row['programa_id'] ?? 0) : 0;
        if ($programaId <= 0) {
            return;
        }

        $pdo->prepare(
            'UPDATE programa_enlaces_pendientes
             SET estado = "resuelto",
                 programa_id_destino = :programa_id,
                 resolved_at = NOW(),
                 updated_at = NOW()
             WHERE numero_documento = :doc AND estado = "pendiente"'
        )->execute(['programa_id' => $programaId, 'doc' => $doc]);
    }

    /** @param array<string,mixed> $data */
    public static function createOrIgnorePending(array $data): void
    {
        $doc = trim((string) ($data['numero_documento'] ?? ''));
        $programaFuente = trim((string) ($data['programa_fuente'] ?? ''));
        if ($doc === '' || $programaFuente === '') {
            return;
        }

        if (self::aprendizHasValidProgramaVinculo($doc)) {
            self::closePendingIfAprendizVinculado($doc);

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

    /**
     * Reabre o crea pendientes para aprendices sin programa válido en catálogo
     * (programa_id nulo, huérfano o resuelto sin destino).
     */
    public static function syncAprendicesSinVinculoValido(): int
    {
        $pdo = Database::connection();
        $sql = 'SELECT a.id AS aprendiz_id,
                       a.numero_documento,
                       a.nombre_completo,
                       a.programa_id,
                       (
                           SELECT pep.programa_fuente
                           FROM programa_enlaces_pendientes pep
                           WHERE pep.numero_documento = a.numero_documento
                           ORDER BY pep.updated_at DESC, pep.id DESC
                           LIMIT 1
                       ) AS programa_fuente_hist,
                       (
                           SELECT pep.modalidad_fuente
                           FROM programa_enlaces_pendientes pep
                           WHERE pep.numero_documento = a.numero_documento
                           ORDER BY pep.updated_at DESC, pep.id DESC
                           LIMIT 1
                       ) AS modalidad_fuente_hist
                FROM aprendices a
                LEFT JOIN programas p ON p.id = a.programa_id
                WHERE a.programa_id IS NULL
                   OR a.programa_id = 0
                   OR p.id IS NULL';

        $rows = $pdo->query($sql)->fetchAll();
        $touched = 0;

        foreach ($rows as $row) {
            $doc = trim((string) ($row['numero_documento'] ?? ''));
            if ($doc === '') {
                continue;
            }

            $programaFuente = trim((string) ($row['programa_fuente_hist'] ?? ''));
            if ($programaFuente === '') {
                continue;
            }

            $modalidad = trim((string) ($row['modalidad_fuente_hist'] ?? ''));
            $match = ProgramaCatalogMatcher::resolve($programaFuente, $modalidad !== '' ? $modalidad : null);
            if ($match['id'] !== null && ($match['ambiguous'] ?? false) !== true) {
                $programaId = (int) $match['id'];
                $pdo->prepare('UPDATE aprendices SET programa_id = :programa_id, updated_at = NOW() WHERE id = :id')
                    ->execute(['programa_id' => $programaId, 'id' => (int) ($row['aprendiz_id'] ?? 0)]);
                $pdo->prepare(
                    'UPDATE programa_enlaces_pendientes
                     SET estado = "resuelto", programa_id_destino = :programa_id, resolved_at = NOW(), updated_at = NOW()
                     WHERE numero_documento = :doc AND estado = "pendiente"'
                )->execute(['programa_id' => $programaId, 'doc' => $doc]);
                $touched++;

                continue;
            }

            self::reopenOrCreatePending([
                'numero_documento' => $doc,
                'nombre_aprendiz' => trim((string) ($row['nombre_completo'] ?? '')),
                'programa_fuente' => $programaFuente,
                'nivel_fuente' => (string) ($match['detected_level'] ?? ''),
                'modalidad_fuente' => $modalidad,
                'candidatos_json' => json_encode((array) ($match['candidates'] ?? []), JSON_UNESCAPED_UNICODE),
                'motivo' => ($match['ambiguous'] ?? false) ? 'ambiguous' : 'invalid_link',
            ]);
            $touched++;
        }

        return $touched;
    }

    /** @param array<string,mixed> $data */
    private static function reopenOrCreatePending(array $data): void
    {
        $doc = trim((string) ($data['numero_documento'] ?? ''));
        $programaFuente = trim((string) ($data['programa_fuente'] ?? ''));
        if ($doc === '' || $programaFuente === '') {
            return;
        }

        $pdo = Database::connection();
        $stmt = $pdo->prepare(
            'SELECT id, estado, programa_id_destino
             FROM programa_enlaces_pendientes
             WHERE numero_documento = :doc AND programa_fuente = :programa
             ORDER BY id DESC
             LIMIT 1'
        );
        $stmt->execute(['doc' => $doc, 'programa' => $programaFuente]);
        $existing = $stmt->fetch();

        if (is_array($existing) && (string) ($existing['estado'] ?? '') === 'pendiente') {
            return;
        }

        if (is_array($existing) && (string) ($existing['estado'] ?? '') === 'resuelto') {
            $destino = (int) ($existing['programa_id_destino'] ?? 0);
            if ($destino <= 0) {
                $pdo->prepare(
                    'UPDATE programa_enlaces_pendientes
                     SET estado = "pendiente",
                         programa_id_destino = NULL,
                         resolved_at = NULL,
                         motivo = :motivo,
                         candidatos_json = :candidatos_json,
                         updated_at = NOW()
                     WHERE id = :id'
                )->execute([
                    'motivo' => trim((string) ($data['motivo'] ?? 'invalid_link')),
                    'candidatos_json' => (string) ($data['candidatos_json'] ?? '[]'),
                    'id' => (int) ($existing['id'] ?? 0),
                ]);

                return;
            }
        }

        self::createOrIgnorePending($data);
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

    public static function countAbiertos(): int
    {
        try {
            $stmt = Database::connection()->query('SELECT COUNT(*) FROM programa_enlaces_pendientes WHERE estado = "pendiente"');

            return (int) $stmt->fetchColumn();
        } catch (\Throwable) {
            return 0;
        }
    }

    public static function resolve(int $pendingId, int $programaId): bool
    {
        if ($programaId <= 0) {
            return false;
        }

        $pdo = Database::connection();
        $programaCheck = $pdo->prepare('SELECT id FROM programas WHERE id = :id LIMIT 1');
        $programaCheck->execute(['id' => $programaId]);
        if (!$programaCheck->fetch()) {
            return false;
        }

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
            self::closePendingIfAprendizVinculado($doc);

            return true;
        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }
}
