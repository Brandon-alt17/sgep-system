<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Models\DocumentoGenerado;
use App\Models\Programa;
use App\Services\ReporteMaestroData;
use PHPUnit\Framework\TestCase;

final class EntregaPrioridadTest extends TestCase
{
    protected function setUp(): void
    {
        if (!defined('BASE_PATH')) {
            define('BASE_PATH', dirname(__DIR__, 2));
        }
    }

    public function test_import_schema_exige_32_columnas(): void
    {
        /** @var array{required_columns: int} $schema */
        $schema = require BASE_PATH . '/config/import_schema.php';
        $this->assertSame(32, $schema['required_columns']);
    }

    public function test_import_mapping_tiene_32_columnas(): void
    {
        /** @var array<int, string> $mapping */
        $mapping = require BASE_PATH . '/config/import_mapping.php';
        $this->assertCount(32, $mapping);
    }

    public function test_describe_partes_documento_f023(): void
    {
        $label = DocumentoGenerado::describePartes(['info', 'momento_tipo:M1']);
        $this->assertStringContainsString('Información', $label);
        $this->assertStringContainsString('Momento 1', $label);
    }

    public function test_programa_delete_by_id_retorna_bool(): void
    {
        $ref = new \ReflectionMethod(Programa::class, 'deleteById');
        $this->assertSame('bool', (string) $ref->getReturnType());
    }

    public function test_reporte_campo_editable_whitelist(): void
    {
        $this->assertTrue(ReporteMaestroData::isEditableCampo('fecha_aval_modalidad'));
        $this->assertTrue(ReporteMaestroData::isEditableCampo('observaciones'));
        $this->assertSame('observaciones_cert', ReporteMaestroData::resolveEditableCampoKey('observaciones'));
        $this->assertFalse(ReporteMaestroData::isEditableCampo('campo_malicioso'));
    }

    public function test_app_debug_default_es_false_sin_env(): void
    {
        $content = file_get_contents(BASE_PATH . '/config/app.php');
        $this->assertIsString($content);
        $this->assertStringContainsString("env('APP_DEBUG', 'false')", $content);
    }
}
