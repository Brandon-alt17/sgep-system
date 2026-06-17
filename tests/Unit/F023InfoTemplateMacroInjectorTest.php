<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Exports\F023InfoTemplateMacroInjector;
use PHPUnit\Framework\TestCase;
use ZipArchive;

final class F023InfoTemplateMacroInjectorTest extends TestCase
{
    public function test_patch_to_temp_preserves_document_xml_root(): void
    {
        $template = dirname(__DIR__, 2) . '/storage/templates/info.docx';
        if (!is_file($template)) {
            $this->markTestSkipped('Plantilla info.docx no disponible.');
        }

        $path = F023InfoTemplateMacroInjector::patchToTemp($template);

        try {
            $zip = new ZipArchive();
            $this->assertTrue($zip->open($path) === true);
            $xml = $zip->getFromName('word/document.xml');
            $zip->close();

            $this->assertIsString($xml);
            $this->assertStringContainsString('<?xml', $xml);
            $this->assertStringContainsString('<w:document', $xml);
            $this->assertStringContainsString('</w:document>', $xml);
            $firstTbl = strpos($xml, '<w:tbl');
            $this->assertNotFalse($firstTbl);
            $firstTableEnd = strpos($xml, '</w:tbl>', $firstTbl);
            $this->assertNotFalse($firstTableEnd);
            $firstTable = substr($xml, $firstTbl, $firstTableEnd + 8 - $firstTbl);
            $this->assertStringContainsString('<w:tblpPr', $firstTable);
            $this->assertStringContainsString('tblpXSpec="center"', $firstTable);
            $this->assertStringContainsString('<w:tblW w:w="8943" w:type="dxa"/>', $firstTable);
            $this->assertStringNotContainsString('<w:tblW w:w="5000" w:type="pct"/>', $firstTable);
        } finally {
            @unlink($path);
        }
    }

    public function test_patch_to_temp_pdf_layout_inlines_header_for_libreoffice(): void
    {
        $template = dirname(__DIR__, 2) . '/storage/templates/info.docx';
        if (!is_file($template)) {
            $this->markTestSkipped('Plantilla info.docx no disponible.');
        }

        $path = F023InfoTemplateMacroInjector::patchToTemp($template, false);

        try {
            $zip = new ZipArchive();
            $this->assertTrue($zip->open($path) === true);
            $xml = $zip->getFromName('word/document.xml');
            $zip->close();

            $firstTbl = strpos($xml, '<w:tbl');
            $this->assertNotFalse($firstTbl);
            $firstTableEnd = strpos($xml, '</w:tbl>', $firstTbl);
            $this->assertNotFalse($firstTableEnd);
            $firstTable = substr($xml, $firstTbl, $firstTableEnd + 8 - $firstTbl);
            $this->assertStringNotContainsString('<w:tblpPr', $firstTable);
            $this->assertStringContainsString('<w:jc w:val="center"/>', $firstTable);
        } finally {
            @unlink($path);
        }
    }
}
