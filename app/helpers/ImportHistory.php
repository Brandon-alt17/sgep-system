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
    public static function add(array $entry): void
    {
        $items = self::all();
        array_unshift($items, $entry);
        $items = array_slice($items, 0, self::MAX_ITEMS);

        $dir = dirname(self::filePath());
        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }

        file_put_contents(
            self::filePath(),
            json_encode($items, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)
        );
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
}

