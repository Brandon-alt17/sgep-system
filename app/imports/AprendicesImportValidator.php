<?php

declare(strict_types=1);

namespace App\Imports;

use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Valida formato de archivo y estructura de tabla antes de importar aprendices.
 */
final class AprendicesImportValidator
{
    /**
     * @return array{valid: bool, errors: list<string>, warnings: list<string>}
     */
    public function validate(string $path, string $originalName = '', int $fileSize = 0): array
    {
        $errors = [];
        $warnings = [];
        $schema = require base_path('config/import_schema.php');
        $requiredColumns = (int) ($schema['required_columns'] ?? 30);
        $maxBytes = (int) ($schema['max_bytes'] ?? 5 * 1024 * 1024);
        $extensions = (array) ($schema['extensions'] ?? ['xlsx', 'xls', 'csv']);
        $criticalHeaders = (array) ($schema['critical_headers'] ?? []);

        if ($path === '' || !is_readable($path)) {
            return $this->result(false, ['No se pudo leer el archivo.'], $warnings);
        }

        $extension = strtolower(pathinfo($originalName !== '' ? $originalName : $path, PATHINFO_EXTENSION));
        if ($extension === '' || !in_array($extension, $extensions, true)) {
            $errors[] = 'Formato no válido. Use .xlsx, .xls o .csv de la plantilla.';
        }

        if ($fileSize > 0 && $fileSize > $maxBytes) {
            $errors[] = 'El archivo supera ' . (int) round($maxBytes / (1024 * 1024)) . ' MB.';
        }

        if ($errors !== []) {
            return $this->result(false, $errors, $warnings);
        }

        try {
            $reader = IOFactory::createReaderForFile($path);
            $reader->setReadDataOnly(true);
            $spreadsheet = $reader->load($path);
        } catch (\Throwable $e) {
            return $this->result(false, ['Archivo dañado, protegido o no es Excel válido.'], $warnings);
        }

        $sheet = $spreadsheet->getActiveSheet();
        $columnErrors = $this->validateColumnLayout($sheet, $requiredColumns);
        $headerErrors = $this->validateHeaderRow($sheet, $criticalHeaders);
        $dataErrors = $this->validateHasDataRows($sheet);

        $errors = array_merge($columnErrors, $headerErrors, $dataErrors);
        $spreadsheet->disconnectWorksheets();
        unset($spreadsheet);

        return $this->result($errors === [], $errors, $warnings);
    }

    /**
     * @param list<string> $errors
     * @param list<string> $warnings
     * @return array{valid: bool, errors: list<string>, warnings: list<string>}
     */
    private function result(bool $valid, array $errors, array $warnings): array
    {
        return [
            'valid' => $valid,
            'errors' => $errors,
            'warnings' => $warnings,
        ];
    }

    /** @return list<string> */
    private function validateColumnLayout(Worksheet $sheet, int $requiredColumns): array
    {
        $highestColumn = $sheet->getHighestDataColumn();
        if ($highestColumn === '') {
            return ['La hoja está vacía.'];
        }

        $columnCount = Coordinate::columnIndexFromString($highestColumn);
        if ($columnCount < $requiredColumns) {
            return [
                sprintf('Faltan columnas (%d de %d). No modifique la plantilla.', $columnCount, $requiredColumns),
            ];
        }

        return [];
    }

    /**
     * @param array<int, list<string>> $criticalHeaders
     * @return list<string>
     */
    private function validateHeaderRow(Worksheet $sheet, array $criticalHeaders): array
    {
        $headerRow = $sheet->rangeToArray(
            'A1:' . $sheet->getHighestDataColumn() . '1',
            null,
            true,
            true,
            false
        )[0] ?? [];

        if ($this->isRowCompletelyEmpty($headerRow)) {
            return ['Falta la fila de encabezados.'];
        }

        foreach ($criticalHeaders as $index => $needles) {
            $cell = (string) ($headerRow[$index] ?? '');
            if (!$this->headerMatches($cell, $needles)) {
                return ['Encabezados distintos a la plantilla oficial.'];
            }
        }

        return [];
    }

    /** @return list<string> */
    private function validateHasDataRows(Worksheet $sheet): array
    {
        $highestRow = $sheet->getHighestDataRow();
        if ($highestRow < 2) {
            return ['Sin filas de datos.'];
        }

        $highestColumn = $sheet->getHighestDataColumn();
        for ($row = 2; $row <= $highestRow; $row++) {
            $cells = $sheet->rangeToArray('A' . $row . ':' . $highestColumn . $row, null, true, true, false)[0] ?? [];
            if (!$this->isRowCompletelyEmpty($cells)) {
                return [];
            }
        }

        return ['No hay registros en el archivo.'];
    }

    /**
     * @param list<string> $needles
     */
    private function headerMatches(string $cell, array $needles): bool
    {
        $normalized = $this->normalizeHeader($cell);
        if ($normalized === '') {
            return false;
        }

        foreach ($needles as $needle) {
            $n = $this->normalizeHeader($needle);
            if ($n !== '' && str_contains($normalized, $n)) {
                return true;
            }
        }

        return false;
    }

    private function normalizeHeader(string $value): string
    {
        $v = mb_strtolower(trim($value));
        $v = str_replace(
            ['á', 'é', 'í', 'ó', 'ú', 'ü', 'ñ'],
            ['a', 'e', 'i', 'o', 'u', 'u', 'n'],
            $v
        );
        $v = preg_replace('/\s+/', ' ', $v) ?? $v;

        return $v;
    }

    /**
     * @param list<mixed> $row
     */
    private function isRowCompletelyEmpty(array $row): bool
    {
        foreach ($row as $value) {
            if ($value === null) {
                continue;
            }
            if (is_string($value) && trim($value) === '') {
                continue;
            }
            if ($value !== '') {
                return false;
            }
        }

        return true;
    }
}
