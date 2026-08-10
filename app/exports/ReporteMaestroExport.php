<?php

declare(strict_types=1);

namespace App\Exports;

use App\Services\ReporteMaestroData;
use DateTime;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
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

        $dateKeys = array_values(array_filter(
            (array) ($map['date_keys'] ?? []),
            static fn (mixed $key): bool => is_string($key) && $key !== ''
        ));

        // Algunas columnas de fecha traen formato "General" en la plantilla (p. ej. Z, AA, BE)
        // en vez de un formato de fecha — la fila de estilo (styleSourceRow) es la que se copia
        // a todas las demás filas vía duplicateStyle() más abajo, así que si su formato está mal
        // TODA la columna termina mostrando el número de serie de Excel en vez de una fecha.
        $this->ensureDateColumnNumberFormats($sheet, $styleSourceRow, $columns, $dateKeys);

        $rows = ReporteMaestroData::rows($filters);
        $excelRow = $firstDataRow;
        foreach ($rows as $row) {
            $row['num_aprendiz'] = $excelRow - $firstDataRow + 1;
            foreach ($writableColumns as $key => $colLetter) {
                $value = ReporteMaestroData::cellValue($row, (string) $key, $map);
                $coord = $colLetter . $excelRow;
                $excelDate = in_array($key, $dateKeys, true) && $value !== ''
                    ? $this->excelDateFromDmY((string) $value)
                    : null;
                if ($excelDate !== null) {
                    $sheet->setCellValueExplicit($coord, $excelDate, DataType::TYPE_NUMERIC);
                } elseif (is_string($value) && strlen($value) > 1 && $value[0] === '=') {
                    // Evita inyección de fórmulas: un dato de aprendiz que empiece por "=" (nombre,
                    // dirección, observación, etc.) no debe convertirse en fórmula viva al abrir el
                    // Excel — PhpSpreadsheet lo detectaría como TYPE_FORMULA en setCellValue().
                    $sheet->setCellValueExplicit($coord, $value, DataType::TYPE_STRING);
                } else {
                    $sheet->setCellValue($coord, $value);
                }
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
     * IMPORTANTE: Worksheet::duplicateStyle(Style $style, string $range) toma UN solo estilo de
     * origen (el de la PRIMERA celda del rango que se le pase) y lo aplica a TODO el rango
     * destino — no copia estilos "columna por columna". Pasarle un rango de origen multi-columna
     * (p. ej. 'C4:BJ4') hacía que TODAS las columnas de cada fila terminaran con el formato de la
     * columna C, perdiendo el formato propio de cada columna (fechas incluidas: se veían como el
     * número de serie de Excel en vez de una fecha en todas las filas salvo la primera). Por eso
     * aquí se recorre columna por columna.
     *
     * @param list<string> $columnLetters
     */
    private function applyDataRowStyles(
        Worksheet $sheet,
        int $firstDataRow,
        int $lastDataRow,
        int $styleSourceRow,
        string $styleRangeEnd,
        array $columnLetters
    ): void {
        $styleColumns = $this->columnLettersInRange('A', $styleRangeEnd);
        $sourceHeight = $sheet->getRowDimension($styleSourceRow)->getRowHeight();

        for ($row = $firstDataRow; $row <= $lastDataRow; $row++) {
            if ($row !== $styleSourceRow) {
                foreach ($styleColumns as $col) {
                    $sheet->duplicateStyle($sheet->getStyle($col . $styleSourceRow), $col . $row);
                }
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

    /**
     * Corrige en memoria (no toca el archivo de plantilla) el formato numérico de las columnas
     * de fecha cuya celda en la fila de estilo viene como "General": si se deja así, el valor
     * numérico de Excel que escribimos (ver excelDateFromDmY()) se ve como "46238" en vez de una
     * fecha, tanto en esa fila como en todas las demás filas de datos (heredan el mismo formato
     * vía duplicateStyle() en applyDataRowStyles()).
     *
     * @param array<string, string> $columns
     * @param list<string> $dateKeys
     */
    private function ensureDateColumnNumberFormats(
        Worksheet $sheet,
        int $styleSourceRow,
        array $columns,
        array $dateKeys
    ): void {
        foreach ($dateKeys as $key) {
            $letter = (string) ($columns[$key] ?? '');
            if ($letter === '') {
                continue;
            }

            $numberFormat = $sheet->getStyle($letter . $styleSourceRow)->getNumberFormat();
            if ($numberFormat->getFormatCode() === NumberFormat::FORMAT_GENERAL) {
                $numberFormat->setFormatCode('dd/mm/yyyy');
            }
        }
    }

    /**
     * Convierte una fecha en formato d/m/Y (como la produce ReporteMaestroData::cellValue)
     * al valor numérico serial de Excel, para que las celdas queden como fecha real
     * (necesario para que fórmulas como EDATE() de la plantilla funcionen).
     */
    private function excelDateFromDmY(string $value): ?float
    {
        $date = DateTime::createFromFormat('d/m/Y', $value);
        if ($date === false) {
            return null;
        }
        $date->setTime(0, 0, 0);

        return ExcelDate::PHPToExcel($date);
    }

    /** @return list<string> */
    private function columnLettersInRange(string $start, string $end): array
    {
        $startIndex = Coordinate::columnIndexFromString($start);
        $endIndex = Coordinate::columnIndexFromString($end);

        $letters = [];
        for ($i = $startIndex; $i <= $endIndex; $i++) {
            $letters[] = Coordinate::stringFromColumnIndex($i);
        }

        return $letters;
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
