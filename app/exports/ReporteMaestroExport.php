<?php

declare(strict_types=1);

namespace App\Exports;

use App\Services\ReporteMaestroData;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use RuntimeException;

class ReporteMaestroExport
{
    /**
     * @param array{estado?: string, ficha?: string, programa_id?: int|string} $filters
     */
    public function export(array $filters = []): string
    {
        /** @var array<string, mixed> $map */
        $map = require base_path('config/reporte_maestro_map.php');
        $templatePath = base_path((string) $map['template_path']);

        if (!is_file($templatePath)) {
            throw new RuntimeException('Plantilla de reporte maestro no encontrada: ' . $templatePath);
        }

        $spreadsheet = IOFactory::load($templatePath);
        $sheetName = (string) ($map['sheet_name'] ?? '');
        $sheet = $sheetName !== '' && $spreadsheet->sheetNameExists($sheetName)
            ? $spreadsheet->getSheetByName($sheetName)
            : $spreadsheet->getActiveSheet();

        if (!$sheet instanceof Worksheet) {
            throw new RuntimeException('No se pudo abrir la hoja del reporte maestro.');
        }

        $firstDataRow = (int) ($map['first_data_row'] ?? 4);
        $styleSourceRow = (int) ($map['style_source_row'] ?? $firstDataRow);
        $styleRangeEnd = (string) ($map['style_range_end'] ?? 'BJ');
        $styleHighlightEnd = (string) ($map['style_highlight_end'] ?? 'B');
        $protectedColumns = array_values(array_filter(
            (array) ($map['protected_columns'] ?? []),
            static fn (mixed $col): bool => is_string($col) && $col !== ''
        ));
        $columns = (array) ($map['columns'] ?? []);

        $writableColumns = array_filter(
            $columns,
            static fn (string $letter): bool => !in_array($letter, $protectedColumns, true),
            ARRAY_FILTER_USE_BOTH
        );

        $clearLetters = array_values(array_unique(array_merge(
            array_values($writableColumns),
            ['A']
        )));
        $clearLetters = array_values(array_filter(
            $clearLetters,
            static fn (string $letter): bool => !in_array($letter, $protectedColumns, true)
        ));

        $this->clearDataRows($sheet, $firstDataRow, $clearLetters);

        $rows = ReporteMaestroData::rows($filters);
        $excelRow = $firstDataRow;
        foreach ($rows as $row) {
            $row['num_aprendiz'] = $excelRow - $firstDataRow + 1;
            foreach ($writableColumns as $key => $colLetter) {
                $value = ReporteMaestroData::cellValue($row, (string) $key, $map);
                $sheet->setCellValue($colLetter . $excelRow, $value);
            }
            $excelRow++;
        }

        $lastDataRow = $excelRow - 1;
        if ($lastDataRow >= $firstDataRow) {
            $this->applyDataRowStyles(
                $sheet,
                $firstDataRow,
                $lastDataRow,
                $styleSourceRow,
                $styleRangeEnd,
                $styleHighlightEnd,
                $clearLetters
            );
        }

        $dir = base_path('storage/documents');
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $path = $dir . '/reporte_maestro_' . date('Ymd_His') . '.xlsx';
        IOFactory::createWriter($spreadsheet, 'Xlsx')->save($path);

        return $path;
    }

    /**
     * @param list<string> $columnLetters
     */
    private function clearDataRows(Worksheet $sheet, int $firstDataRow, array $columnLetters): void
    {
        $highestRow = max($firstDataRow, (int) $sheet->getHighestRow());

        for ($row = $firstDataRow; $row <= $highestRow; $row++) {
            foreach ($columnLetters as $letter) {
                $coord = $letter . $row;
                $sheet->setCellValue($coord, null);
                $sheet->getCell($coord)->setHyperlink(null);
            }
        }
    }

    /**
     * Unifica estilo de todas las filas de datos y elimina hipervínculos vacíos de la plantilla.
     *
     * @param list<string> $columnLetters
     */
    private function applyDataRowStyles(
        Worksheet $sheet,
        int $firstDataRow,
        int $lastDataRow,
        int $styleSourceRow,
        string $styleRangeEnd,
        string $styleHighlightEnd,
        array $columnLetters
    ): void {
        $highlightSource = 'A' . $styleSourceRow . ':' . $styleHighlightEnd . $styleSourceRow;
        $bodyStartCol = $this->nextColumnLetter($styleHighlightEnd);
        $bodySource = $bodyStartCol . $styleSourceRow . ':' . $styleRangeEnd . $styleSourceRow;
        $sourceHeight = $sheet->getRowDimension($styleSourceRow)->getRowHeight();

        for ($row = $firstDataRow; $row <= $lastDataRow; $row++) {
            if ($row !== $styleSourceRow) {
                $sheet->duplicateStyle(
                    $sheet->getStyle($highlightSource),
                    'A' . $row . ':' . $styleHighlightEnd . $row
                );
                $sheet->duplicateStyle(
                    $sheet->getStyle($bodySource),
                    $bodyStartCol . $row . ':' . $styleRangeEnd . $row
                );
                if ($sourceHeight >= 0) {
                    $sheet->getRowDimension($row)->setRowHeight($sourceHeight);
                }
            }

            foreach ($columnLetters as $letter) {
                $sheet->getCell($letter . $row)->setHyperlink(null);
            }
        }
    }

    private function nextColumnLetter(string $column): string
    {
        $index = Coordinate::columnIndexFromString($column);

        return Coordinate::stringFromColumnIndex($index + 1);
    }

    public static function downloadFilename(): string
    {
        return 'Reporte_seguimiento_maestro_' . date('Y-m-d') . '.xlsx';
    }
}
