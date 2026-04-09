<?php

declare(strict_types=1);

namespace App\Imports;

use App\Helpers\Database;
use App\Helpers\Normalizer;
use PhpOffice\PhpSpreadsheet\IOFactory;

class AprendicesImport
{
    public function import(string $path): array
    {
        $mapping = require base_path('config/import_mapping.php');
        $sheet = IOFactory::load($path)->getActiveSheet();
        $rows = $sheet->toArray();
        $results = ['inserted' => 0, 'updated' => 0, 'duplicates' => 0, 'errors' => []];

        foreach (array_slice($rows, 1) as $index => $row) {
            try {
                $assoc = [];
                foreach ($mapping as $i => $field) {
                    $assoc[$field] = $row[$i] ?? null;
                }
                $assoc = Normalizer::normalizeRow($assoc);
                if (empty($assoc['numero_documento']) || empty($assoc['nombre_completo'])) {
                    continue;
                }
                $existing = $this->findByDocumento((string) $assoc['numero_documento']);
                if ($existing) {
                    $this->updateEmptyFields((int) $existing['id'], $assoc);
                    $results['updated']++;
                    $results['duplicates']++;
                } else {
                    $this->insert($assoc);
                    $results['inserted']++;
                }
            } catch (\Throwable $e) {
                $results['errors'][] = 'Fila ' . ($index + 2) . ': ' . $e->getMessage();
            }
        }
        return $results;
    }

    private function findByDocumento(string $documento): ?array
    {
        $stmt = Database::connection()->prepare('SELECT * FROM aprendices WHERE numero_documento = :doc');
        $stmt->execute(['doc' => $documento]);
        return $stmt->fetch() ?: null;
    }

    private function insert(array $data): void
    {
        $sql = 'INSERT INTO aprendices (nombre_completo, tipo_documento, numero_documento, telefono, correo_personal, correo_institucional, ficha, estado, created_at, updated_at)
                VALUES (:nombre, :tipo_doc, :doc, :tel, :mail, :mail_inst, :ficha, :estado, NOW(), NOW())';
        Database::connection()->prepare($sql)->execute([
            'nombre' => $data['nombre_completo'],
            'tipo_doc' => $data['tipo_documento'] ?? 'CC',
            'doc' => $data['numero_documento'],
            'tel' => $data['telefono'] ?? null,
            'mail' => $data['correo_personal'] ?? null,
            'mail_inst' => $data['correo_institucional'] ?? null,
            'ficha' => $data['ficha'] ?? null,
            'estado' => $data['estado'] ?? 'Pendiente por iniciar',
        ]);
    }

    private function updateEmptyFields(int $id, array $data): void
    {
        $sql = 'UPDATE aprendices
                SET telefono = COALESCE(NULLIF(:telefono, ""), telefono),
                    correo_personal = COALESCE(NULLIF(:correo_personal, ""), correo_personal),
                    correo_institucional = COALESCE(NULLIF(:correo_institucional, ""), correo_institucional),
                    ficha = COALESCE(NULLIF(:ficha, ""), ficha),
                    updated_at = NOW()
                WHERE id = :id';
        Database::connection()->prepare($sql)->execute([
            'id' => $id,
            'telefono' => (string) ($data['telefono'] ?? ''),
            'correo_personal' => (string) ($data['correo_personal'] ?? ''),
            'correo_institucional' => (string) ($data['correo_institucional'] ?? ''),
            'ficha' => (string) ($data['ficha'] ?? ''),
        ]);
    }
}
