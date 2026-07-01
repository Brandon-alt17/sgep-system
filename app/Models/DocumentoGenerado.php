<?php

declare(strict_types=1);

namespace App\Models;

use App\Helpers\Database;

class DocumentoGenerado
{
    public static function countByAprendiz(int $aprendizId): int
    {
        if ($aprendizId <= 0) {
            return 0;
        }

        $stmt = Database::connection()->prepare(
            'SELECT COUNT(*) FROM documentos_generados WHERE aprendiz_id = :aprendiz_id'
        );
        $stmt->execute(['aprendiz_id' => $aprendizId]);

        return (int) $stmt->fetchColumn();
    }

    /**
     * @return list<array<string, mixed>>
     */
    public static function listByAprendiz(int $aprendizId, int $limit = 0, int $offset = 0): array
    {
        if ($aprendizId <= 0) {
            return [];
        }

        $sql = 'SELECT id, aprendiz_id, partes, formato, ruta_archivo, created_at
             FROM documentos_generados
             WHERE aprendiz_id = :aprendiz_id
             ORDER BY created_at DESC, id DESC';

        if ($limit > 0) {
            $sql .= ' LIMIT :limit OFFSET :offset';
        }

        $stmt = Database::connection()->prepare($sql);
        $stmt->bindValue('aprendiz_id', $aprendizId, \PDO::PARAM_INT);
        if ($limit > 0) {
            $stmt->bindValue('limit', $limit, \PDO::PARAM_INT);
            $stmt->bindValue('offset', max(0, $offset), \PDO::PARAM_INT);
        }
        $stmt->execute();

        return $stmt->fetchAll() ?: [];
    }

    /**
     * @return array<string, mixed>|null
     */
    public static function findById(int $id): ?array
    {
        if ($id <= 0) {
            return null;
        }

        $stmt = Database::connection()->prepare(
            'SELECT id, aprendiz_id, partes, formato, ruta_archivo, created_at
             FROM documentos_generados
             WHERE id = :id
             LIMIT 1'
        );
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();

        return is_array($row) ? $row : null;
    }

    public static function deleteRecordAndFile(array $row): bool
    {
        $id = (int) ($row['id'] ?? 0);
        if ($id <= 0) {
            return false;
        }

        $path = trim((string) ($row['ruta_archivo'] ?? ''));
        if ($path !== '' && DocumentoGenerado::isPathAllowed($path)) {
            @unlink($path);
        }

        $stmt = Database::connection()->prepare('DELETE FROM documentos_generados WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);

        return $stmt->rowCount() > 0;
    }

    /**
     * @param list<string>|string $partes
     */
    public static function describePartes(array|string $partes): string
    {
        if (is_string($partes)) {
            $decoded = json_decode($partes, true);
            $partes = is_array($decoded) ? $decoded : [];
        }

        $labels = [];
        foreach ($partes as $token) {
            $t = trim((string) $token);
            if ($t === '') {
                continue;
            }
            if ($t === 'info') {
                $labels[] = 'Información';
                continue;
            }
            if (preg_match('/^momento_tipo:(M1|M2|M3)$/', $t, $m) === 1) {
                $labels[] = 'Momento ' . substr($m[1], 1) . ' (plantilla vacía)';
                continue;
            }
            if (preg_match('/^momento:(\d+)$/', $t, $m) === 1) {
                $momentoId = (int) $m[1];
                $row = Momento::findById($momentoId);
                if ($row !== null) {
                    $tipo = (string) ($row['tipo'] ?? '');
                    if ($tipo === 'EX') {
                        $fecha = trim((string) ($row['fecha_visita'] ?? ''));
                        $labels[] = $fecha !== ''
                            ? 'Visita extraordinaria (' . date_iso_to_dmY($fecha) . ')'
                            : 'Visita extraordinaria';
                    } else {
                        $labels[] = 'Momento ' . substr($tipo, 1);
                    }
                } else {
                    $labels[] = 'Momento #' . $momentoId;
                }
            }
        }

        return $labels === [] ? '—' : implode(', ', $labels);
    }

    public static function isPathAllowed(string $path): bool
    {
        $path = trim($path);
        if ($path === '' || !is_file($path)) {
            return false;
        }

        $documentsDir = realpath(base_path('storage/documents'));
        $resolved = realpath($path);
        if ($documentsDir === false || $resolved === false) {
            return false;
        }

        return str_starts_with($resolved, $documentsDir . DIRECTORY_SEPARATOR)
            || $resolved === $documentsDir;
    }
}
