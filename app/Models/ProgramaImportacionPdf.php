<?php

declare(strict_types=1);

namespace App\Models;

use App\Helpers\Database;

class ProgramaImportacionPdf
{
    /** @param array<string,mixed> $data */
    public static function create(array $data): int
    {
        $sql = 'INSERT INTO programa_importaciones_pdf
                (programa_id, nombre_archivo, codigo_programa, nombre_programa, estado, advertencias_json, resumen_json, created_at, updated_at)
                VALUES
                (:programa_id, :nombre_archivo, :codigo_programa, :nombre_programa, :estado, :advertencias_json, :resumen_json, NOW(), NOW())';
        $pdo = Database::connection();
        $pdo->prepare($sql)->execute([
            'programa_id' => $data['programa_id'] ?? null,
            'nombre_archivo' => (string) ($data['nombre_archivo'] ?? ''),
            'codigo_programa' => (string) ($data['codigo_programa'] ?? ''),
            'nombre_programa' => (string) ($data['nombre_programa'] ?? ''),
            'estado' => (string) ($data['estado'] ?? 'borrador'),
            'advertencias_json' => (string) ($data['advertencias_json'] ?? '[]'),
            'resumen_json' => (string) ($data['resumen_json'] ?? '{}'),
        ]);
        return (int) $pdo->lastInsertId();
    }
}
