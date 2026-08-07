<?php

declare(strict_types=1);

namespace App\Models;

use App\Helpers\Database;

final class VisitaExtraordinariaProgramada
{
    /**
     * @return list<array<string, mixed>>
     */
    public static function listByAprendiz(int $aprendizId): array
    {
        if ($aprendizId <= 0) {
            return [];
        }

        $stmt = Database::connection()->prepare(
            'SELECT id, aprendiz_id, numero_visita, fecha_programada, hora_programada, modalidad, completada
             FROM visitas_extraordinarias_programadas
             WHERE aprendiz_id = :aprendiz_id
             ORDER BY numero_visita ASC'
        );
        $stmt->execute(['aprendiz_id' => $aprendizId]);

        return $stmt->fetchAll() ?: [];
    }

    /**
     * Reemplaza las visitas extraordinarias programadas del aprendiz.
     *
     * @param list<array{fecha: ?string, hora: ?string, modalidad: ?string, completada: bool}> $rows
     */
    public static function syncForAprendiz(int $aprendizId, array $rows): void
    {
        if ($aprendizId <= 0) {
            return;
        }

        $pdo = Database::connection();
        $pdo->prepare('DELETE FROM visitas_extraordinarias_programadas WHERE aprendiz_id = :aprendiz_id')
            ->execute(['aprendiz_id' => $aprendizId]);

        if ($rows === []) {
            return;
        }

        $stmt = $pdo->prepare(
            'INSERT INTO visitas_extraordinarias_programadas
                (aprendiz_id, numero_visita, fecha_programada, hora_programada, modalidad, completada, created_at, updated_at)
             VALUES
                (:aprendiz_id, :numero_visita, :fecha_programada, :hora_programada, :modalidad, :completada, NOW(), NOW())'
        );

        $numero = 1;
        foreach ($rows as $row) {
            $fecha = trim((string) ($row['fecha'] ?? ''));
            $hora = trim((string) ($row['hora'] ?? ''));
            $modalidad = trim((string) ($row['modalidad'] ?? ''));
            if ($fecha === '' && $hora === '' && $modalidad === '' && empty($row['completada'])) {
                continue;
            }

            $stmt->execute([
                'aprendiz_id' => $aprendizId,
                'numero_visita' => $numero,
                'fecha_programada' => $fecha !== '' ? $fecha : null,
                'hora_programada' => $hora !== '' ? $hora : null,
                'modalidad' => self::normalizeModalidad($modalidad),
                'completada' => !empty($row['completada']) ? 1 : 0,
            ]);
            $numero++;
        }
    }

    /**
     * @param array<string, mixed> $post
     * @return list<array{fecha: ?string, hora: ?string, modalidad: ?string, completada: bool}>
     */
    public static function rowsFromPost(array $post): array
    {
        $raw = $post['extraordinarias'] ?? [];
        if (!is_array($raw)) {
            return [];
        }

        /** @var list<array{fecha: ?string, hora: ?string, modalidad: ?string, completada: bool}> $rows */
        $rows = [];
        foreach ($raw as $item) {
            if (!is_array($item)) {
                continue;
            }
            $fechaIso = date_post_to_iso((string) ($item['fecha'] ?? ''));
            $rows[] = [
                'fecha' => $fechaIso !== '' ? $fechaIso : null,
                'hora' => self::normalizeHora((string) ($item['hora'] ?? '')),
                'modalidad' => self::normalizeModalidad((string) ($item['modalidad'] ?? '')),
                'completada' => isset($item['completada']),
            ];
        }

        return $rows;
    }

    public static function normalizeHora(string $value): ?string
    {
        $value = trim($value);
        if ($value === '') {
            return null;
        }

        return preg_match('/^([01]\d|2[0-3]):([0-5]\d)$/', $value) === 1 ? $value : null;
    }

    private static function normalizeModalidad(string $value): ?string
    {
        $value = trim($value);
        if ($value === '') {
            return null;
        }

        return strcasecmp($value, 'Virtual') === 0 ? 'Virtual' : 'Presencial';
    }
}
