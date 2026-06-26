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
            '/wp:posOffset>4938395<\\/wp:posOffset>.*?\\$\\{m3_juicio_marca_aprobado\\}/s',
            $xml,
            'La marca de Aprobado debe ir dentro del cuadro anclado (textbox), no junto al texto'
        );
        $this->assertMatchesRegularExpression(
            '/wp:posOffset>6029325<\\/wp:posOffset>.*?\\$\\{m3_juicio_marca_no_aprobado\\}/s',
            $xml,
            'La marca de No aprobado debe ir dentro del cuadro anclado'
        );
        $this->assertDoesNotMatchRegularExpression(
            '/Aprobado<\\/w:t><\\/w:r>.*?<w:sz w:val="40".*?\\$\\{m3_juicio_marca_aprobado\\}/s',
            $xml,
            'No debe insertar la marca en el run de texto junto a la etiqueta Aprobado'
        );
        $this->assertMatchesRegularExpression(
            '/wp:posOffset>4938395<\\/wp:posOffset>.*?anchor="ctr".*?anchorCtr="1"/s',
            $xml,
            'El cuadro de Aprobado debe centrarse verticalmente dentro del recuadro'
        );
        $this->assertMatchesRegularExpression(
            '/<w:tc\b[^>]*>.*?<w:jc w:val="left"\/>.*?\$\{nombre_aprendiz\}/s',
            $xml,
            'El nombre del aprendiz en la línea de firma debe ir alineado a la izquierda'
        );
        $this->assertMatchesRegularExpression(
            '/<w:tc\b[^>]*>.*?<w:jc w:val="left"\/>.*?\$\{nombre_instructor_seguimiento\}/s',
            $xml,
            'El nombre del instructor en la línea de firma debe ir alineado a la izquierda'
        );
    }

    public function test_m3_p2_apply_juicio_marca_writes_x_inside_checkbox_after_save(): void
    {
        $template = BASE_PATH . '/storage/templates/m3_p2.docx';
        if (!is_file($template)) {
            $this->markTestSkipped('Plantilla m3_p2.docx no disponible.');
        }

        $patched = F023M3TemplateMacroInjector::patchToTemp($template);
        $proc = new \PhpOffice\PhpWord\TemplateProcessor($patched);
        $proc->setValue('m3_juicio_marca_aprobado', 'X');
        $proc->setValue('m3_juicio_marca_no_aprobado', '');
        $saved = tempnam(sys_get_temp_dir(), 'm3p2saved_') . '.docx';
        $proc->saveAs($saved);
        @unlink($patched);

        F023M3TemplateMacroInjector::applyJuicioMarcasToSavedDocx($saved, [
            'm3_juicio_marca_aprobado' => 'X',
            'm3_juicio_marca_no_aprobado' => '',
        ]);

        $zip = new ZipArchive();
        $this->assertTrue($zip->open($saved) === true);
        $xml = (string) $zip->getFromName('word/document.xml');
        $zip->close();
        @unlink($saved);

        $this->assertMatchesRegularExpression(
            '/wp:posOffset>4938395<\\/wp:posOffset>.*?<w:txbxContent>.*?<w:t[^>]*>X<\\/w:t>.*?<\\/w:txbxContent>/s',
            $xml
        );
        $this->assertMatchesRegularExpression(
            '/wp:posOffset>4938395<\\/wp:posOffset>.*?lIns="0".*?tIns="0"/s',
            $xml
        );
        $this->assertDoesNotMatchRegularExpression(
            '/Aprobado<\\/w:t><\\/w:r>.*?<w:t[^>]*>X<\\/w:t>/s',
            $xml
        );
    }
}
