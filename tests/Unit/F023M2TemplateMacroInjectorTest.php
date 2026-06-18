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
        } finally {
            @unlink($path);
        }
    }
}
