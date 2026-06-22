<?php

declare(strict_types=1);

namespace App\Helpers;

class ImportHistory
{
    private const MAX_ITEMS = 20;

    private static function filePath(): string
    {
        return base_path('storage/app/imports/history.json');
    }

    /** @return array<int, array<string, mixed>> */
    public static function all(): array
    {
        $file = self::filePath();
        if (!is_file($file)) {
            return [];
        }

        $raw = file_get_contents($file);
        if ($raw === false || trim($raw) === '') {
            return [];
        }

        $decoded = json_decode($raw, true);
        if (!is_array($decoded)) {
            return [];
        }

        return array_values(array_filter($decoded, static fn ($row): bool => is_array($row)));
    }

    /** @param array<string, mixed> $entry */
    public static function add(array $entry): bool
    {
        $items = self::all();
        array_unshift($items, $entry);
        $items = array_slice($items, 0, self::MAX_ITEMS);

        $dir = dirname(self::filePath());
        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }

        return self::write($items);
    }

    /**
     * Quita un aprendiz de conflict_rows tras resolver todos sus conflictos en la UI.
     */
    public static function removeConflictAprendiz(string $importId, int $aprendizId): bool
    {
        $importId = trim($importId);
        if ($importId === '' || $aprendizId <= 0) {
            return false;
        }

        $items = self::all();
        $changed = false;
        foreach ($items as $index => $row) {
            if ((string) ($row['id'] ?? '') !== $importId) {
                continue;
            }

            $resultado = (array) ($row['resultado'] ?? []);
            $conflictRows = (array) ($resultado['conflict_rows'] ?? []);
            $filtered = array_values(array_filter(
                $conflictRows,
                static fn (mixed $cr): bool => (int) (((array) $cr)['aprendiz_id'] ?? 0) !== $aprendizId
            ));

            if (count($filtered) === count($conflictRows)) {
                return false;
            }

            $resultado['conflict_rows'] = $filtered;
            if (isset($resultado['conflicts'])) {
                $resultado['conflicts'] = max(0, (int) $resultado['conflicts'] - 1);
            }
            $items[$index]['resultado'] = $resultado;
            $changed = true;
            break;
        }

        if (!$changed) {
            return false;
        }

        self::write($items);

        return true;
    }

    /**
     * Vacía el historial. Si el archivo fue creado por el servidor web y no es escribible,
     * intenta borrarlo y recrearlo (requiere permiso de escritura en el directorio).
     */
    public static function clear(): bool
    {
        $empty = json_encode([], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

        return $empty !== false && self::persistFile($empty);
    }

    /** @param array<int, array<string, mixed>> $items */
    private static function write(array $items): bool
    {
        $encoded = json_encode($items, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        if ($encoded === false) {
            return false;
        }

        return self::persistFile($encoded);
    }

    private static function persistFile(string $contents): bool
    {
        $path = self::filePath();
        $dir = dirname($path);
        if (!is_dir($dir) && !mkdir($dir, 0777, true) && !is_dir($dir)) {
            return false;
        }

        if (is_file($path) && !is_writable($path) && !unlink($path)) {
            return false;
        }

        return file_put_contents($path, $contents, LOCK_EX) !== false;
    }

    public static function findById(string $id): ?array
    {
        foreach (self::all() as $row) {
            if ((string) ($row['id'] ?? '') === $id) {
                return $row;
            }
        }

        return null;
    }

    /**
     * @return array<string, mixed>|null
     */
    public static function findConflictAprendizRow(string $importId, int $aprendizId): ?array
    {
        $importId = trim($importId);
        if ($importId === '' || $aprendizId <= 0) {
            return null;
        }

        $entry = self::findById($importId);
        if ($entry === null) {
            return null;
        }

        foreach ((array) (((array) ($entry['resultado'] ?? []))['conflict_rows'] ?? []) as $row) {
            if (!is_array($row)) {
                continue;
            }
            if ((int) ($row['aprendiz_id'] ?? 0) === $aprendizId) {
                return $row;
            }
        }

        return null;
    }

    /**
     * Última ejecución de importación registrada en el historial (más reciente).
     *
     * @return array<string, mixed>|null
     */
    public static function latestImport(): ?array
    {
        $all = self::all();

        return $all[0] ?? null;
    }

    /**
     * Importación más reciente del historial que aún reporta conflictos sin resolver.
     *
     * @return array<string, mixed>|null
     */
    public static function latestWithConflicts(): ?array
    {
        foreach (self::all() as $row) {
            $conflictRows = (array) (((array) ($row['resultado'] ?? []))['conflict_rows'] ?? []);
            if ($conflictRows !== []) {
                return $row;
            }
        }

        return null;
    }

    public static function countConflictAprendices(?string $importId = null): int
    {
        if ($importId !== null && $importId !== '') {
            $entry = self::findById($importId);

            return $entry === null
                ? 0
                : count((array) (((array) ($entry['resultado'] ?? []))['conflict_rows'] ?? []));
        }

        $entry = self::latestImport();

        return $entry === null
            ? 0
            : count((array) (((array) ($entry['resultado'] ?? []))['conflict_rows'] ?? []));
    }
}

