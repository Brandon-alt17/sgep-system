<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Exports\ReporteMaestroExport;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PHPUnit\Framework\TestCase;

final class ReporteMaestroExportTest extends TestCase
{
    protected function setUp(): void
    {
        if (!defined('BASE_PATH')) {
            define('BASE_PATH', dirname(__DIR__, 2));
        }
    }

    public function test_adjust_formula_row_actualiza_referencias_de_fila(): void
    {
        $formula = '=IF(AND(J4<>"",OR(L4="X",M4="X")),IF(L4="X",EDATE(J4,18),EDATE(J4,12)),"")';
        $adjusted = ReporteMaestroExport::adjustFormulaRow($formula, 4, 10);

        $this->assertStringContainsString('J10', $adjusted);
        $this->assertStringContainsString('L10', $adjusted);
        $this->assertStringContainsString('M10', $adjusted);
        $this->assertStringContainsString('EDATE(J10,18)', $adjusted);
        $this->assertStringNotContainsString('J4', $adjusted);
    }

    public function test_plantilla_tiene_formulas_n_y_o_ajustables(): void
    {
        $template = dirname(__DIR__, 2) . '/storage/templates/Reporte Maestro.xlsx';
        if (!is_file($template)) {
            $this->markTestSkipped('Plantilla de reporte maestro no disponible.');
        }

        $sheet = IOFactory::load($template)->getSheetByName('APRENDICES EP 2024');
        $this->assertNotNull($sheet);

        $n4 = (string) $sheet->getCell('N4')->getValue();
        $o4 = (string) $sheet->getCell('O4')->getValue();

        $this->assertStringStartsWith('=IF(AND(J4', $n4);
        $this->assertStringStartsWith('=IF(N4=', $o4);

        $n5 = reporte_maestro_adjust_formula_row($n4, 4, 5);
        $o5 = reporte_maestro_adjust_formula_row($o4, 4, 5);

        $this->assertStringContainsString('J5', $n5);
        $this->assertStringContainsString('N5', $o5);
    }
}
