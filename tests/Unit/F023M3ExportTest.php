<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Exports\F023Generator;
use PHPUnit\Framework\TestCase;
use ZipArchive;

final class F023M3ExportTest extends TestCase
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

    public function test_m3_export_replaces_p1_date_and_link_placeholders(): void
    {
        if (!is_file(dirname(__DIR__, 2) . '/storage/templates/m3_p1.docx')) {
            $this->markTestSkipped('Plantilla m3_p1.docx no disponible.');
        }

        $path = (new F023Generator())->generate(42, ['momento_tipo:M3'], 'docx');
        $zip = new ZipArchive();
        $this->assertTrue($zip->open($path) === true);
        $xml = $zip->getFromName('word/document.xml');
        $zip->close();

        $this->assertIsString($xml);
        $this->assertStringNotContainsString('${fecha_inicio_etapa}', $xml);
        $this->assertStringNotContainsString('${fecha_fin_etapa}', $xml);
        $this->assertStringNotContainsString('${enlace_grabacion}', $xml);
        $this->assertStringNotContainsString('${numero_visitas_realizadas}', $xml);
        $this->assertStringNotContainsString('${modalidad}', $xml);
    }
}
