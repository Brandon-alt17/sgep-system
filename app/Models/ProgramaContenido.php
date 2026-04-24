<?php

declare(strict_types=1);

namespace App\Models;

use App\Helpers\Database;

class ProgramaContenido
{
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
                    'nombre' => (string) ($row['competencia_nombre'] ?? ''),
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
                    'nombre' => trim((string) ($competencia['nombre'] ?? '')),
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
}
