<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Services\ReporteMaestroData;
use PHPUnit\Framework\TestCase;

final class ReporteMaestroReglamentoTest extends TestCase
{
    public function test_calcula_vencimiento_y_semaforo_como_excel(): void
    {
        $iso = ReporteMaestroData::computeVencimientoTerminosIso('01/06/2024', true, false);
        $this->assertSame('2025-12-01', $iso);
        $this->assertSame('SIN FECHA', ReporteMaestroData::computeSemaforoVencimiento(''));
        $this->assertSame('🔴 VENCIDO', ReporteMaestroData::computeSemaforoVencimiento('2020-01-01'));

        $iso009 = ReporteMaestroData::computeVencimientoTerminosIso('01/06/2024', false, true);
        $this->assertSame('2025-06-01', $iso009);
    }

    public function test_solo_un_acuerdo_activo_en_reglamento_fields(): void
    {
        $fields = ReporteMaestroData::reglamentoFields(
            ['acuerdo_007' => '1', 'acuerdo_009' => '1', 'fecha_fin_plataforma' => '01/01/2024'],
            []
        );

        $this->assertTrue($fields['acuerdo_007']);
        $this->assertFalse($fields['acuerdo_009']);
        $this->assertSame('01/07/2025', $fields['vencimiento_terminos']);
    }
}
