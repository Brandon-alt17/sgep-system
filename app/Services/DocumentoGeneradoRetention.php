<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\DocumentoGenerado;

final class DocumentoGeneradoRetention
{
    public const MAX_PER_APRENDIZ = 5;

    public const RETENTION_DAYS = 90;

    /**
     * Elimina documentos que superan el límite por aprendiz o la antigüedad máxima.
     *
     * @return int Cantidad de registros eliminados
     */
    public static function pruneForAprendiz(
        int $aprendizId,
        int $maxPerAprendiz = self::MAX_PER_APRENDIZ,
        int $retentionDays = self::RETENTION_DAYS,
    ): int {
        if ($aprendizId <= 0) {
            return 0;
        }

        self::purgeLegacyReporteMaestroExports();

        $rows = DocumentoGenerado::listByAprendiz($aprendizId);
        $idsToDelete = self::selectIdsToPrune($rows, $maxPerAprendiz, $retentionDays);
        $deleted = 0;

        foreach ($idsToDelete as $id) {
            $row = DocumentoGenerado::findById($id);
            if ($row === null) {
                continue;
            }
            if (DocumentoGenerado::deleteRecordAndFile($row)) {
                $deleted++;
            }
        }

        return $deleted;
    }

    /**
     * @param list<array<string, mixed>> $rows Orden descendente por fecha (más reciente primero)
     * @return list<int>
     */
    public static function selectIdsToPrune(
        array $rows,
        int $maxPerAprendiz,
        int $retentionDays,
        ?\DateTimeImmutable $now = null,
    ): array {
        if ($rows === [] || $maxPerAprendiz < 1 || $retentionDays < 1) {
            return [];
        }

        $now ??= new \DateTimeImmutable('now');
        $cutoff = $now->modify('-' . $retentionDays . ' days');
        $idsToDelete = [];
        $kept = 0;

        foreach ($rows as $row) {
            $id = (int) ($row['id'] ?? 0);
            if ($id <= 0) {
                continue;
            }

            $createdAt = self::parseCreatedAt((string) ($row['created_at'] ?? ''));
            $tooOld = $createdAt !== null && $createdAt < $cutoff;

            if ($tooOld || $kept >= $maxPerAprendiz) {
                $idsToDelete[] = $id;
                continue;
            }

            $kept++;
        }

        return $idsToDelete;
    }

    /** Elimina exportaciones legacy del reporte maestro que ya no se conservan en disco. */
    public static function purgeLegacyReporteMaestroExports(): int
    {
        $dir = base_path('storage/documents');
        if (!is_dir($dir)) {
            return 0;
        }

        $deleted = 0;
        $pattern = rtrim($dir, '/\\') . DIRECTORY_SEPARATOR . 'reporte_maestro_*.xlsx';
        foreach (glob($pattern) ?: [] as $path) {
            if (is_file($path) && @unlink($path)) {
                $deleted++;
            }
        }

        return $deleted;
    }

    private static function parseCreatedAt(string $value): ?\DateTimeImmutable
    {
        $value = trim($value);
        if ($value === '') {
            return null;
        }

        try {
            return new \DateTimeImmutable($value);
        } catch (\Exception) {
            return null;
        }
    }
}
