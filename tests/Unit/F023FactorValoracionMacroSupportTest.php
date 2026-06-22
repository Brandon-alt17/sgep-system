<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Exports\F023FactorValoracionMacroSupport;
use App\Exports\F023M3TemplateMacroInjector;
use PHPUnit\Framework\TestCase;
use ZipArchive;

final class F023FactorValoracionMacroSupportTest extends TestCase
{
    protected function setUp(): void
    {
        if (!defined('BASE_PATH')) {
            define('BASE_PATH', dirname(__DIR__, 2));
        }
    }

    public function test_m3_factor_observation_paragraphs_are_left_aligned(): void
    {
        $template = BASE_PATH . '/storage/templates/m3_p1.docx';
        if (!is_file($template)) {
            $this->markTestSkipped('Plantilla m3_p1.docx no disponible.');
        }

        $patched = F023M3TemplateMacroInjector::patchToTemp($template);
        $zip = new ZipArchive();
        $this->assertTrue($zip->open($patched) === true);
        $xml = (string) $zip->getFromName('word/document.xml');
        $zip->close();
        @unlink($patched);

        $this->assertStringContainsString('${factor_0_observacion}', $xml);

        preg_match_all('/<w:tr\b[^>]*>.*?<\/w:tr>/s', $xml, $allRows);
        $factorRow = '';
        foreach ($allRows[0] as $row) {
            if (str_contains($row, '${factor_0_observacion}')) {
                $factorRow = $row;
                break;
            }
        }
        $this->assertNotSame('', $factorRow);

        preg_match_all('/<w:tc\b[^>]*>.*?<\/w:tc>/s', $factorRow, $cells);
        $obsCell = $cells[0][3] ?? '';
        $this->assertNotSame('', $obsCell);
        $this->assertDoesNotMatchRegularExpression('/<w:jc[^>]*w:val="center"/', $obsCell);
        $this->assertMatchesRegularExpression('/<w:jc[^>]*w:val="left"/', $obsCell);
    }

    public function test_inject_into_document_xml_left_aligns_observation_macro_paragraph(): void
    {
        $row = '<w:tr>'
            . '<w:tc><w:p><w:pPr><w:jc w:val="center"/></w:pPr><w:r><w:t>Aplicación de conocimientos</w:t></w:r></w:p></w:tc>'
            . '<w:tc><w:p><w:r><w:t>S</w:t></w:r></w:p></w:tc>'
            . '<w:tc><w:p><w:r><w:t>PM</w:t></w:r></w:p></w:tc>'
            . '<w:tc><w:p><w:pPr><w:jc w:val="center"/></w:pPr></w:p></w:tc>'
            . '</w:tr>';
        $xml = '<w:document><w:body><w:tbl>' . $row . '</w:tbl></w:body></w:document>';

        $patched = F023FactorValoracionMacroSupport::injectIntoDocumentXml($xml);
        $this->assertStringContainsString('${factor_0_observacion}', $patched);
        $this->assertDoesNotMatchRegularExpression('/factor_0_observacion.*?<w:jc[^>]*w:val="center"/s', $patched);
    }
}
