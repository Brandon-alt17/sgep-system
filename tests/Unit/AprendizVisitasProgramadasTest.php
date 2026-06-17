<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Models\Aprendiz;
use PHPUnit\Framework\TestCase;

final class AprendizVisitasProgramadasTest extends TestCase
{
    public function test_resolve_proxima_visita_usa_m1_si_esta_pendiente(): void
    {
        $this->assertSame(
            '2026-06-01',
            Aprendiz::resolveProximaVisitaFromProgramadas('2026-06-01', '2026-07-01', '2026-08-01', false, false)
        );
    }

    public function test_resolve_proxima_visita_permite_misma_fecha_en_los_tres(): void
    {
        $this->assertSame(
            '2026-06-15',
            Aprendiz::resolveProximaVisitaFromProgramadas('2026-06-15', '2026-06-15', '2026-06-15', false, false)
        );
    }

    public function test_resolve_proxima_visita_pasa_a_m2_cuando_m1_completa(): void
    {
        $this->assertSame(
            '2026-07-01',
            Aprendiz::resolveProximaVisitaFromProgramadas('2026-06-01', '2026-07-01', '2026-08-01', true, false)
        );
    }

    public function test_resolve_proxima_visita_pasa_a_m3_cuando_m1_y_m2_completas(): void
    {
        $this->assertSame(
            '2026-08-01',
            Aprendiz::resolveProximaVisitaFromProgramadas('2026-06-01', '2026-07-01', '2026-08-01', true, true)
        );
    }
}
