<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Exports\F023M3TemplateMacroInjector;
use PHPUnit\Framework\TestCase;
use ZipArchive;

final class F023M3P1TemplateMacroInjectorTest extends TestCase
{
    protected function setUp(): void
    {
        if (!defined('BASE_PATH')) {
            define('BASE_PATH', dirname(__DIR__, 2));
        }
    }

    public function test_m3_p1_injects_coformador_retroalimentacion_macros(): void
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

        foreach ([
            'm3_retro_coformador_proceso',
            'm3_retro_coformador_desempeno',
        ] as $macro) {
            $this->assertStringContainsString('${' . $macro . '}', $xml, 'Falta macro ' . $macro);
        }
    }
}
