<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use ReflectionMethod;

final class ReporteMaestroVisibilityTest extends TestCase
{
    private static function appendVisibilitySql(array $filters): string
    {
        $sql = 'SELECT 1 FROM aprendices a WHERE 1=1';
        $params = [];
        $method = new ReflectionMethod(\App\Services\ReporteMaestroData::class, 'appendActiveReportVisibilitySql');
        $method->setAccessible(true);
        $method->invokeArgs(null, [&$sql, &$params, $filters]);

        return $sql;
    }

    public function test_active_report_excludes_finalizados_by_default(): void
    {
        $sql = self::appendVisibilitySql([]);

        $this->assertStringContainsString("a.estado <> 'Finalizada'", $sql);
        $this->assertStringContainsString("rc_f.valor = 'Finalizado'", $sql);
    }

    public function test_mostrar_finalizados_skips_visibility_filter(): void
    {
        $sql = self::appendVisibilitySql(['mostrar_finalizados' => true]);

        $this->assertStringNotContainsString('highlight_aprendiz_id', $sql);
        $this->assertStringNotContainsString("rc_f.valor = 'Finalizado'", $sql);
    }

    public function test_highlight_aprendiz_id_keeps_target_row_visible(): void
    {
        $sql = self::appendVisibilitySql(['aprendiz_id' => 42]);

        $this->assertStringContainsString('a.id = :highlight_aprendiz_id', $sql);
        $this->assertStringContainsString("a.estado <> 'Finalizada'", $sql);
    }
}
