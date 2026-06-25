<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Services\ReporteMaestroData;
use PHPUnit\Framework\TestCase;

final class ReporteMaestroEstadoArlTest extends TestCase
{
    protected function setUp(): void
    {
        if (!defined('BASE_PATH')) {
            define('BASE_PATH', dirname(__DIR__, 2));
        }
    }

    public function test_normaliza_en_espera_legacy(): void
    {
        $this->assertSame(
            'En espera de afiliación',
            ReporteMaestroData::normalizeEstadoArl('En espera')
        );
    }

    public function test_exporta_estado_arl_en_mayusculas(): void
    {
        $this->assertSame(
            'EN ESPERA DE AFILIACIÓN',
            ReporteMaestroData::formatEstadoArlExport('En espera de afiliación')
        );
        $this->assertSame('AFILIADO', ReporteMaestroData::formatEstadoArlExport('Afiliado'));
        $this->assertSame('NO APLICA', ReporteMaestroData::formatEstadoArlExport('No aplica'));
    }

    public function test_cell_value_exporta_arl_y_estado(): void
    {
        /** @var array<string, mixed> $map */
        $map = require dirname(__DIR__, 2) . '/config/reporte_maestro_map.php';

        $row = ['estado_arl' => 'Afiliado', 'arl' => 'SURA'];
        $this->assertSame('AFILIADO', ReporteMaestroData::cellValue($row, 'estado_arl', $map));
        $this->assertSame('SURA', ReporteMaestroData::cellValue($row, 'arl', $map));
        $this->assertArrayHasKey('arl', $map['columns']);
        $this->assertSame('Y', $map['columns']['arl']);
    }
}
