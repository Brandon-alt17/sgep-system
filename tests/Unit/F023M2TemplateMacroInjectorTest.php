<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Exports\F023M2TemplateMacroInjector;
use PHPUnit\Framework\TestCase;
use ZipArchive;

final class F023M2TemplateMacroInjectorTest extends TestCase
{
    protected function setUp(): void
    {
        if (!defined('BASE_PATH')) {
            define('BASE_PATH', dirname(__DIR__, 2));
        }
    }

    public function test_patch_to_temp_injects_enlace_grabacion_macro(): void
    {
        $template = dirname(__DIR__, 2) . '/storage/templates/m2.docx';
        if (!is_file($template)) {
            $this->markTestSkipped('Plantilla m2.docx no disponible.');
        }

        $path = F023M2TemplateMacroInjector::patchToTemp($template);

        try {
            $zip = new ZipArchive();
            $this->assertTrue($zip->open($path) === true);
            $xml = $zip->getFromName('word/document.xml');
            $zip->close();

            $this->assertIsString($xml);
            $this->assertStringContainsString('${enlace_grabacion}', $xml);
            $this->assertStringContainsString('${fecha_inicio_etapa}', $xml);
            $this->assertStringContainsString('${fecha_visita}', $xml);
            $this->assertStringContainsString('${modalidad}', $xml);
            $this->assertStringContainsString('${obs_instructor}', $xml);
            $this->assertStringContainsString('${obs_instructor_l2}', $xml);
            $this->assertStringContainsString('${obs_aprendiz}', $xml);
            $this->assertStringContainsString('${obs_aprendiz_l2}', $xml);
            $this->assertStringContainsString('${obs_coformador}', $xml);
            $this->assertStringContainsString('${obs_coformador_l2}', $xml);
            $this->assertStringContainsString('${obs_instructor_l3}', $xml);
            $this->assertStringContainsString('w:lineRule="exact"', $xml);
            $this->assertStringContainsString('w:jc w:val="left"/></w:pPr><w:r><w:rPr><w:rFonts w:ascii="Calibri"', $xml);

            libxml_use_internal_errors(true);
            $dom = new \DOMDocument();
            $this->assertTrue($dom->loadXML($xml), 'document.xml parcheado M2 debe ser XML válido');
            $this->assertStringContainsString('Firma Instructor de seguimiento</w:t>', $xml);
            $this->assertStringContainsString('Firma del </w:t>', $xml);
        } finally {
            @unlink($path);
        }
    }
}
