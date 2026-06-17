<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Exports\F023DocxMerge;
use App\Exports\F023Generator;
use PHPUnit\Framework\TestCase;
use ZipArchive;

final class F023DocxMergeTest extends TestCase
{
    protected function setUp(): void
    {
        if (!defined('BASE_PATH')) {
            define('BASE_PATH', dirname(__DIR__, 2));
        }
        if (!defined('APP_BASE_PATH') && is_readable(BASE_PATH . '/.env')) {
            \Dotenv\Dotenv::createImmutable(BASE_PATH)->safeLoad();
            require BASE_PATH . '/config/app.php';
        }
    }

    public function test_merged_export_has_unique_para_ids_and_valid_document_root(): void
    {
        if (!is_file(dirname(__DIR__, 2) . '/storage/templates/info.docx')) {
            $this->markTestSkipped('Plantillas F-023 no disponibles.');
        }

        $path = (new F023Generator())->generate(
            42,
            ['info', 'momento_tipo:M1', 'momento_tipo:M2', 'momento_tipo:M3'],
            'docx'
        );

        $zip = new ZipArchive();
        $this->assertTrue($zip->open($path) === true);
        $xml = $zip->getFromName('word/document.xml');
        $zip->close();

        $this->assertIsString($xml);
        $this->assertStringContainsString('<w:document', $xml);
        $this->assertStringContainsString('</w:document>', $xml);

        preg_match_all('/w14:paraId="([0-9A-Fa-f]{8})"/', $xml, $matches);
        $ids = $matches[1] ?? [];
        $this->assertSame(count($ids), count(array_unique($ids)), 'w14:paraId duplicados en documento fusionado');

        libxml_use_internal_errors(true);
        $dom = new \DOMDocument();
        $this->assertTrue($dom->loadXML($xml), 'document.xml fusionado no es XML válido');
    }

    public function test_merge_into_rejects_empty_input(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        F023DocxMerge::mergeInto(sys_get_temp_dir() . '/empty.docx', []);
    }
}
