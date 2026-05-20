<?php

declare(strict_types=1);

namespace App\Models;

use App\Helpers\Database;
use App\Helpers\ProgramaPdfParser;

class ProgramaContenido
{
    private static function sanitizeCompetenciaNombre(string $nombre): string
    {
        return ProgramaPdfParser::sanitizeCompetenciaNombre($nombre);
    }

    public static function existeCodigoCompetenciaEnPrograma(int $programaId, string $codigo, int $excludeCompetenciaId = 0): bool
    {
        $codigo = trim($codigo);
        if ($codigo === '') {
            return false;
        }
        $sql = 'SELECT COUNT(*) AS total
                FROM programa_competencias
                WHERE programa_id = :programa_id
                  AND LOWER(TRIM(codigo)) = LOWER(TRIM(:codigo))
                  AND (:exclude_id = 0 OR id <> :exclude_id)';
        $stmt = Database::connection()->prepare($sql);
        $stmt->execute([
            'programa_id' => $programaId,
            'codigo' => $codigo,
            'exclude_id' => $excludeCompetenciaId,
        ]);
        $row = $stmt->fetch();
        return ((int) ($row['total'] ?? 0)) > 0;
    }

    /** @return array<int,array<string,mixed>> */
    public static function competenciasConResultados(int $programaId): array
    {
        $sql = 'SELECT c.id AS competencia_id, c.codigo AS competencia_codigo, c.nombre AS competencia_nombre,
                       r.id AS resultado_id, r.codigo AS resultado_codigo, r.descripcion AS resultado_descripcion
                FROM programa_competencias c
                LEFT JOIN programa_resultados_aprendizaje r ON r.competencia_id = c.id
                WHERE c.programa_id = :programa_id
                ORDER BY c.orden ASC, c.id ASC, r.orden ASC, r.id ASC';
        $stmt = Database::connection()->prepare($sql);
        $stmt->execute(['programa_id' => $programaId]);
        $rows = $stmt->fetchAll();

        $grouped = [];
        foreach ($rows as $row) {
            $compId = (int) ($row['competencia_id'] ?? 0);
            if (!isset($grouped[$compId])) {
                $grouped[$compId] = [
                    'id' => $compId,
                    'codigo' => (string) ($row['competencia_codigo'] ?? ''),
                    'nombre' => self::sanitizeCompetenciaNombre((string) ($row['competencia_nombre'] ?? '')),
                    'resultados' => [],
                ];
            }
            $resultadoId = (int) ($row['resultado_id'] ?? 0);
            if ($resultadoId > 0) {
                $grouped[$compId]['resultados'][] = [
                    'id' => $resultadoId,
                    'codigo' => (string) ($row['resultado_codigo'] ?? ''),
                    'descripcion' => (string) ($row['resultado_descripcion'] ?? ''),
                ];
            }
        }

        return array_values($grouped);
    }

    /** @param array<int,array<string,mixed>> $competencias */
    public static function replaceProgramaContenido(int $programaId, array $competencias): void
    {
        $pdo = Database::connection();
        $pdo->beginTransaction();
        try {
            $pdo->prepare('DELETE FROM programa_resultados_aprendizaje WHERE programa_id = :programa_id')
                ->execute(['programa_id' => $programaId]);
            $pdo->prepare('DELETE FROM programa_competencias WHERE programa_id = :programa_id')
                ->execute(['programa_id' => $programaId]);

            $compStmt = $pdo->prepare('INSERT INTO programa_competencias (programa_id, codigo, nombre, orden, created_at, updated_at)
                                       VALUES (:programa_id, :codigo, :nombre, :orden, NOW(), NOW())');
            $resStmt = $pdo->prepare('INSERT INTO programa_resultados_aprendizaje (programa_id, competencia_id, codigo, descripcion, orden, created_at, updated_at)
                                      VALUES (:programa_id, :competencia_id, :codigo, :descripcion, :orden, NOW(), NOW())');

            foreach ($competencias as $cIndex => $competencia) {
                $compStmt->execute([
                    'programa_id' => $programaId,
                    'codigo' => trim((string) ($competencia['codigo'] ?? '')),
                    'nombre' => self::sanitizeCompetenciaNombre((string) ($competencia['nombre'] ?? '')),
                    'orden' => $cIndex + 1,
                ]);
                $competenciaId = (int) $pdo->lastInsertId();
                $resultados = (array) ($competencia['resultados'] ?? []);
                foreach ($resultados as $rIndex => $resultado) {
                    $resStmt->execute([
                        'programa_id' => $programaId,
                        'competencia_id' => $competenciaId,
                        'codigo' => trim((string) ($resultado['codigo'] ?? '')),
                        'descripcion' => trim((string) ($resultado['descripcion'] ?? '')),
                        'orden' => $rIndex + 1,
                    ]);
                }
            }
            $pdo->commit();
        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    /** @param array<int,array<string,string>> $resultados */
    public static function updateCompetenciaConResultados(int $programaId, int $competenciaId, string $codigo, string $nombre, array $resultados): void
    {
        $pdo = Database::connection();
        $pdo->beginTransaction();
        try {
            $pdo->prepare(
                'UPDATE programa_competencias
                 SET codigo = :codigo, nombre = :nombre, updated_at = NOW()
                 WHERE id = :id AND programa_id = :programa_id'
            )->execute([
                'codigo' => trim($codigo),
                'nombre' => self::sanitizeCompetenciaNombre($nombre),
                'id' => $competenciaId,
                'programa_id' => $programaId,
            ]);

            $pdo->prepare(
                'DELETE FROM programa_resultados_aprendizaje
                 WHERE competencia_id = :competencia_id AND programa_id = :programa_id'
            )->execute([
                'competencia_id' => $competenciaId,
                'programa_id' => $programaId,
            ]);

            $insertStmt = $pdo->prepare(
                'INSERT INTO programa_resultados_aprendizaje
                 (programa_id, competencia_id, codigo, descripcion, orden, created_at, updated_at)
                 VALUES
                 (:programa_id, :competencia_id, :codigo, :descripcion, :orden, NOW(), NOW())'
            );
            foreach ($resultados as $idx => $resultado) {
                $insertStmt->execute([
                    'programa_id' => $programaId,
                    'competencia_id' => $competenciaId,
                    'codigo' => trim((string) ($resultado['codigo'] ?? '')),
                    'descripcion' => trim((string) ($resultado['descripcion'] ?? '')),
                    'orden' => $idx + 1,
                ]);
            }

            $pdo->commit();
        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    public static function createCompetenciaVacia(int $programaId): int
    {
        $pdo = Database::connection();
        $pdo->beginTransaction();
        try {
            $pdo->prepare(
                'UPDATE programa_competencias
                 SET orden = orden + 1, updated_at = NOW()
                 WHERE programa_id = :programa_id'
            )->execute(['programa_id' => $programaId]);

            $insertStmt = $pdo->prepare(
                'INSERT INTO programa_competencias (programa_id, codigo, nombre, orden, created_at, updated_at)
                 VALUES (:programa_id, :codigo, :nombre, :orden, NOW(), NOW())'
            );
            $insertStmt->execute([
                'programa_id' => $programaId,
                'codigo' => '',
                'nombre' => '',
                'orden' => 1,
            ]);

            $id = (int) $pdo->lastInsertId();
            $pdo->commit();
            return $id;
        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    /** @param array<int,array<string,string>> $resultados */
    public static function createCompetenciaConResultados(int $programaId, string $codigo, string $nombre, array $resultados): int
    {
        $pdo = Database::connection();
        $pdo->beginTransaction();
        try {
            $pdo->prepare(
                'UPDATE programa_competencias
                 SET orden = orden + 1, updated_at = NOW()
                 WHERE programa_id = :programa_id'
            )->execute(['programa_id' => $programaId]);

            $insertCompetencia = $pdo->prepare(
                'INSERT INTO programa_competencias (programa_id, codigo, nombre, orden, created_at, updated_at)
                 VALUES (:programa_id, :codigo, :nombre, :orden, NOW(), NOW())'
            );
            $insertCompetencia->execute([
                'programa_id' => $programaId,
                'codigo' => trim($codigo),
                'nombre' => self::sanitizeCompetenciaNombre($nombre),
                'orden' => 1,
            ]);

            $competenciaId = (int) $pdo->lastInsertId();
            $insertResultado = $pdo->prepare(
                'INSERT INTO programa_resultados_aprendizaje
                 (programa_id, competencia_id, codigo, descripcion, orden, created_at, updated_at)
                 VALUES
                 (:programa_id, :competencia_id, :codigo, :descripcion, :orden, NOW(), NOW())'
            );
            foreach ($resultados as $idx => $resultado) {
                $insertResultado->execute([
                    'programa_id' => $programaId,
                    'competencia_id' => $competenciaId,
                    'codigo' => trim((string) ($resultado['codigo'] ?? '')),
                    'descripcion' => trim((string) ($resultado['descripcion'] ?? '')),
                    'orden' => $idx + 1,
                ]);
            }

            $pdo->commit();
            return $competenciaId;
        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    public static function deleteCompetencia(int $programaId, int $competenciaId): void
    {
        $stmt = Database::connection()->prepare(
            'DELETE FROM programa_competencias
             WHERE id = :id AND programa_id = :programa_id'
        );
        $stmt->execute([
            'id' => $competenciaId,
            'programa_id' => $programaId,
        ]);
    }
}
