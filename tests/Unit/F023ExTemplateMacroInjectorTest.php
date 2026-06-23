<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Exports\F023ExTemplateMacroInjector;
use PHPUnit\Framework\TestCase;
use ZipArchive;

final class F023ExTemplateMacroInjectorTest extends TestCase
{
    protected function setUp(): void
    {
        if (!defined('BASE_PATH')) {
            define('BASE_PATH', dirname(__DIR__, 2));
        }
    }

    public function test_patch_to_temp_injects_ex_macros(): void
    {
        $template = dirname(__DIR__, 2) . '/storage/templates/extra.docx';
        if (!is_file($template)) {
            $this->markTestSkipped('Plantilla extra.docx no disponible.');
        }

        $path = F023ExTemplateMacroInjector::patchToTemp($template);

        try {
            $zip = new ZipArchive();
            $this->assertTrue($zip->open($path) === true);
            $xml = $zip->getFromName('word/document.xml');
            $zip->close();

            $this->assertIsString($xml);
            $this->assertStringContainsString('${numero_visita}', $xml);
            $this->assertStringContainsString('${fecha_seguimiento_anterior}', $xml);
            $this->assertStringContainsString('${fecha_visita}', $xml);
            $this->assertStringContainsString('${modalidad}', $xml);
            $this->assertStringContainsString('${enlace_grabacion}', $xml);
            $this->assertStringContainsString('${motivo_seguimiento_extraordinario}', $xml);
            $this->assertStringContainsString('${obs_instructor}', $xml);
            $this->assertStringContainsString('${obs_aprendiz}', $xml);
            $this->assertStringContainsString('${obs_coformador}', $xml);
            $this->assertStringContainsString('${obs_instructor_l2}', $xml);
            $this->assertStringContainsString('Compromisos por parte del instructor de seguimiento:</w:t></w:r></w:p><w:p', $xml);
            $this->assertStringNotContainsString('instructor de seguimiento:</w:t></w:r><w:r', $xml);
            $this->assertStringContainsString('${nombre_aprendiz}', $xml);
            $this->assertStringContainsString('${nombre_instructor_seguimiento}', $xml);
            $this->assertStringContainsString('${nombre_coformador}', $xml);
            $this->assertStringContainsString('${ex_marca_presencial}', $xml);
            $this->assertStringContainsString('${ex_marca_virtual}', $xml);
        } finally {
            @unlink($path);
        }
    }
}
