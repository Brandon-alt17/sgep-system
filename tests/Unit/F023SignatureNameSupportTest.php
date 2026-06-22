<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Exports\F023M1TemplateMacroInjector;
use App\Exports\F023M2TemplateMacroInjector;
use App\Exports\F023M3TemplateMacroInjector;
use PHPUnit\Framework\TestCase;
use ZipArchive;

final class F023SignatureNameSupportTest extends TestCase
{
    protected function setUp(): void
    {
        if (!defined('BASE_PATH')) {
            define('BASE_PATH', dirname(__DIR__, 2));
        }
    }

    /**
     * @dataProvider templateProvider
     */
    public function test_patch_injects_signature_name_macros(string $template, callable $patch): void
    {
        $path = BASE_PATH . '/storage/templates/' . $template;
        if (!is_file($path)) {
            $this->markTestSkipped('Plantilla no disponible: ' . $template);
        }

        $patched = $patch($path);

        try {
            $zip = new ZipArchive();
            $this->assertTrue($zip->open($patched) === true);
            $xml = $zip->getFromName('word/document.xml');
            $zip->close();

            $this->assertIsString($xml);
            $this->assertStringContainsString('${nombre_aprendiz}', $xml);
            $this->assertStringContainsString('${nombre_instructor_seguimiento}', $xml);
        } finally {
            @unlink($patched);
        }
    }

    public static function templateProvider(): array
    {
        return [
            'm1' => ['m1.docx', [F023M1TemplateMacroInjector::class, 'patchToTemp']],
            'm2' => ['m2.docx', [F023M2TemplateMacroInjector::class, 'patchToTemp']],
            'm3_p2' => ['m3_p2.docx', [F023M3TemplateMacroInjector::class, 'patchToTemp']],
        ];
    }
}
