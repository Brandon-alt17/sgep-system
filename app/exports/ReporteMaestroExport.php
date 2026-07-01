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
     * @param array{estado?: string, ficha?: string, programa_id?: int|string, q?: string} $filters
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
        $advanceHighlightColumn = (string) ($map['advance_highlight_column'] ?? '');
        $protectedColumns = array_values(array_filter(
            (array) ($map['protected_columns'] ?? []),
            static fn (mixed $col): bool => is_string($col) && $col !== ''
        ));
        $columns = (array) ($map['columns'] ?? []);
        $formulaColumns = array_values(array_filter(
            (array) ($map['formula_columns'] ?? []),
            static fn (mixed $col): bool => is_string($col) && $col !== ''
        ));

        $writableColumns = array_filter(
            $columns,
            static fn (string $letter): bool => !in_array($letter, $protectedColumns, true)
                && !in_array($letter, $formulaColumns, true),
            ARRAY_FILTER_USE_BOTH
        );

        $clearLetters = array_values(array_unique(array_merge(
            array_values($writableColumns),
            $formulaColumns,
            ['A']
        )));
        $clearLetters = array_values(array_filter(
            $clearLetters,
            static fn (string $letter): bool => !in_array($letter, $protectedColumns, true)
        ));

        $formulaTemplates = $this->readFormulaTemplates($sheet, $styleSourceRow, $formulaColumns);

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
            $this->applyFormulaColumns(
                $sheet,
                $styleSourceRow,
                $firstDataRow,
                $lastDataRow,
                $formulaTemplates
            );
            $this->applyDataRowStyles(
                $sheet,
                $firstDataRow,
                $lastDataRow,
                $styleSourceRow,
                $styleRangeEnd,
                $styleHighlightEnd,
                $clearLetters
            );
            if ($advanceHighlightColumn !== '') {
                $this->applyAdvanceColumnHighlight(
                    $sheet,
                    $firstDataRow,
                    $lastDataRow,
                    $styleSourceRow,
                    $advanceHighlightColumn
                );
            }
        }

        $dir = sys_get_temp_dir();
        if (!is_dir($dir) || !is_writable($dir)) {
            $dir = base_path('storage/documents');
        }
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $path = $dir . DIRECTORY_SEPARATOR . 'reporte_maestro_' . date('Ymd_His') . '_' . getmypid() . '.xlsx';
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

    private function applyAdvanceColumnHighlight(
        Worksheet $sheet,
        int $firstDataRow,
        int $lastDataRow,
        int $styleSourceRow,
        string $columnLetter
    ): void {
        $source = $columnLetter . $styleSourceRow;
        for ($row = $firstDataRow; $row <= $lastDataRow; $row++) {
            $sheet->duplicateStyle($sheet->getStyle($source), $columnLetter . $row);
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

    /**
     * @param list<string> $formulaColumns
     * @return array<string, string>
     */
    private function readFormulaTemplates(Worksheet $sheet, int $sourceRow, array $formulaColumns): array
    {
        $templates = [];
        foreach ($formulaColumns as $column) {
            $value = $sheet->getCell($column . $sourceRow)->getValue();
            if (is_string($value) && str_starts_with($value, '=')) {
                $templates[$column] = $value;
            }
        }

        return $templates;
    }

    /**
     * @param array<string, string> $formulaTemplates
     */
    private function applyFormulaColumns(
        Worksheet $sheet,
        int $sourceRow,
        int $firstDataRow,
        int $lastDataRow,
        array $formulaTemplates
    ): void {
        if ($formulaTemplates === []) {
            return;
        }

        for ($row = $firstDataRow; $row <= $lastDataRow; $row++) {
            foreach ($formulaTemplates as $column => $formula) {
                $sheet->setCellValue(
                    $column . $row,
                    reporte_maestro_adjust_formula_row($formula, $sourceRow, $row)
                );
            }
        }
    }

    public static function adjustFormulaRow(string $formula, int $sourceRow, int $targetRow): string
    {
        return reporte_maestro_adjust_formula_row($formula, $sourceRow, $targetRow);
    }
}
