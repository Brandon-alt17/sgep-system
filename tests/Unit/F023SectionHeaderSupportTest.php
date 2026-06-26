<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Exports\F023ExTemplateMacroInjector;
use App\Exports\F023M1TemplateMacroInjector;
use App\Exports\F023M2TemplateMacroInjector;
use App\Exports\F023SectionHeaderSupport;
use PHPUnit\Framework\TestCase;
use ZipArchive;

final class F023SectionHeaderSupportTest extends TestCase
{
    protected function setUp(): void
    {
        if (!defined('BASE_PATH')) {
            define('BASE_PATH', dirname(__DIR__, 2));
        }
    }

    public function test_normalize_sect_pr_restores_default_header_reference(): void
    {
        $source = '<w:sectPr>'
            . '<w:headerReference w:type="default" r:id="rId9"/>'
            . '<w:headerReference w:type="first" r:id="rId10"/>'
            . '<w:titlePg/>'
            . '</w:sectPr>';
        $broken = '<w:sectPr><w:headerReference w:type="first" r:id="rId11"/></w:sectPr>';

        $fixed = F023SectionHeaderSupport::normalizeSectPrForPdfExport($broken, $source);

        $this->assertStringContainsString('<w:headerReference w:type="default" r:id="rId9"/>', $fixed);
        $this->assertStringNotContainsString('w:type="first"', $fixed);
        $this->assertStringNotContainsString('titlePg', $fixed);
    }

    public function test_m1_apply_layout_keeps_default_header_for_pdf(): void
    {
        $template = dirname(__DIR__, 2) . '/storage/templates/m1.docx';
        if (!is_file($template)) {
            $this->markTestSkipped('Plantilla m1.docx no disponible.');
        }

        $patched = F023M1TemplateMacroInjector::patchToTemp($template);
        $proc = new \PhpOffice\PhpWord\TemplateProcessor($patched);
        $saved = tempnam(sys_get_temp_dir(), 'm1hdr_') . '.docx';
        $proc->saveAs($saved);
        @unlink($patched);

        F023M1TemplateMacroInjector::applyLayoutToSavedDocx($saved);

        $zip = new ZipArchive();
        $this->assertTrue($zip->open($saved) === true);
        $xml = (string) $zip->getFromName('word/document.xml');
        $zip->close();
        @unlink($saved);

        $this->assertMatchesRegularExpression(
            '/<w:headerReference w:type="default" r:id="rId9"\/>/',
            $xml
        );
        $this->assertDoesNotMatchRegularExpression('/<w:headerReference w:type="first"/', $xml);
    }

    public function test_m2_apply_layout_keeps_default_header_for_pdf(): void
    {
        $template = dirname(__DIR__, 2) . '/storage/templates/m2.docx';
        if (!is_file($template)) {
            $this->markTestSkipped('Plantilla m2.docx no disponible.');
        }

        $patched = F023M2TemplateMacroInjector::patchToTemp($template);
        $proc = new \PhpOffice\PhpWord\TemplateProcessor($patched);
        $saved = tempnam(sys_get_temp_dir(), 'm2hdr_') . '.docx';
        $proc->saveAs($saved);
        @unlink($patched);

        F023M2TemplateMacroInjector::applyLayoutToSavedDocx($saved);

        $zip = new ZipArchive();
        $this->assertTrue($zip->open($saved) === true);
        $xml = (string) $zip->getFromName('word/document.xml');
        $zip->close();
        @unlink($saved);

        $this->assertMatchesRegularExpression(
            '/<w:headerReference w:type="default" r:id="rId9"\/>/',
            $xml
        );
        $this->assertDoesNotMatchRegularExpression('/<w:headerReference w:type="first"/', $xml);
    }

    public function test_ex_apply_layout_keeps_default_header_for_pdf(): void
    {
        $template = dirname(__DIR__, 2) . '/storage/templates/extra.docx';
        if (!is_file($template)) {
            $this->markTestSkipped('Plantilla extra.docx no disponible.');
        }

        $patched = F023ExTemplateMacroInjector::patchToTemp($template);
        $proc = new \PhpOffice\PhpWord\TemplateProcessor($patched);
        $saved = tempnam(sys_get_temp_dir(), 'exhdr_') . '.docx';
        $proc->saveAs($saved);
        @unlink($patched);

        F023ExTemplateMacroInjector::applyLayoutToSavedDocx($saved);

        $zip = new ZipArchive();
        $this->assertTrue($zip->open($saved) === true);
        $xml = (string) $zip->getFromName('word/document.xml');
        $zip->close();
        @unlink($saved);

        $this->assertMatchesRegularExpression(
            '/<w:headerReference w:type="default" r:id="rId9"\/>/',
            $xml
        );
        $this->assertDoesNotMatchRegularExpression('/<w:headerReference w:type="first"/', $xml);
    }
}
