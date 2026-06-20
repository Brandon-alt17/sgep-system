<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Exports\F023M3TemplateMacroInjector;
use PHPUnit\Framework\TestCase;
use ZipArchive;

final class F023M3P2TemplateMacroInjectorTest extends TestCase
{
    protected function setUp(): void
    {
        if (!defined('BASE_PATH')) {
            define('BASE_PATH', dirname(__DIR__, 2));
        }
    }

    public function test_m3_p2_injects_retroalimentacion_and_juicio_macros(): void
    {
        $template = BASE_PATH . '/storage/templates/m3_p2.docx';
        if (!is_file($template)) {
            $this->markTestSkipped('Plantilla m3_p2.docx no disponible.');
        }

        $patched = F023M3TemplateMacroInjector::patchToTemp($template);
        $zip = new ZipArchive();
        $this->assertTrue($zip->open($patched) === true);
        $xml = (string) $zip->getFromName('word/document.xml');
        $zip->close();
        @unlink($patched);

        foreach ([
            'm3_retro_instructor_proceso',
            'm3_retro_instructor_desempeno',
            'm3_retro_aprendiz_proceso',
            'm3_retro_aprendiz_desempeno',
            'm3_juicio_marca_aprobado',
            'm3_juicio_marca_no_aprobado',
        ] as $macro) {
            $this->assertStringContainsString('${' . $macro . '}', $xml, 'Falta macro ' . $macro);
        }

        $this->assertMatchesRegularExpression(
            '/Aprobado<\\/w:t><\\/w:r>(?:(?!<w:sz w:val="40").)*<w:sz w:val="40"(?:(?!<\\/w:r>).)*\\$\\{m3_juicio_marca_aprobado\\}/s',
            $xml,
            'La marca de Aprobado debe ir en el cuadro (run sz 40), no pegada al texto'
        );
        $this->assertDoesNotMatchRegularExpression(
            '/Aprobado<\\/w:t><w:r><w:rPr>/',
            $xml,
            'No debe anidar un run de macro dentro del run de la etiqueta Aprobado'
        );
    }
}
