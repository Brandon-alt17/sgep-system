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

    public function test_resolve_proxima_visita_pasa_a_ex_cuando_m1_m2_y_m3_completas(): void
    {
        $this->assertSame(
            '2026-09-01',
            Aprendiz::resolveProximaVisitaFromProgramadas(
                '2026-06-01',
                '2026-07-01',
                '2026-08-01',
                true,
                true,
                true,
                [
                    ['fecha_programada' => '2026-09-01', 'completada' => 0],
                    ['fecha_programada' => '2026-10-01', 'completada' => 0],
                ],
            )
        );
    }

    public function test_resolve_proxima_visita_salta_ex_completadas(): void
    {
        $this->assertSame(
            '2026-10-01',
            Aprendiz::resolveProximaVisitaFromProgramadas(
                '2026-06-01',
                '2026-07-01',
                '2026-08-01',
                true,
                true,
                true,
                [
                    ['fecha_programada' => '2026-09-01', 'completada' => 1],
                    ['fecha_programada' => '2026-10-01', 'completada' => 0],
                ],
            )
        );
    }

    public function test_resolve_proxima_visita_null_cuando_todo_completado(): void
    {
        $this->assertNull(
            Aprendiz::resolveProximaVisitaFromProgramadas(
                '2026-06-01',
                '2026-07-01',
                '2026-08-01',
                true,
                true,
                true,
                [
                    ['fecha_programada' => '2026-09-01', 'completada' => 1],
                ],
            )
        );
    }

    public function test_resolve_proxima_visita_hora_usa_hora_m1_si_esta_pendiente(): void
    {
        $this->assertSame(
            '09:00',
            Aprendiz::resolveProximaVisitaHoraFromProgramadas(
                '2026-06-01',
                '2026-07-01',
                '2026-08-01',
                false,
                false,
                false,
                [],
                '09:00',
                '10:00',
                '11:00',
            )
        );
    }

    public function test_resolve_proxima_visita_hora_pasa_a_m2_cuando_m1_completa(): void
    {
        $this->assertSame(
            '10:00',
            Aprendiz::resolveProximaVisitaHoraFromProgramadas(
                '2026-06-01',
                '2026-07-01',
                '2026-08-01',
                true,
                false,
                false,
                [],
                '09:00',
                '10:00',
                '11:00',
            )
        );
    }

    public function test_resolve_proxima_visita_hora_usa_hora_de_la_extraordinaria_pendiente(): void
    {
        $this->assertSame(
            '14:30',
            Aprendiz::resolveProximaVisitaHoraFromProgramadas(
                '2026-06-01',
                '2026-07-01',
                '2026-08-01',
                true,
                true,
                true,
                [
                    ['fecha_programada' => '2026-09-01', 'hora_programada' => '14:30', 'completada' => 0],
                ],
                '09:00',
                '10:00',
                '11:00',
            )
        );
    }

    public function test_resolve_proxima_visita_hora_null_cuando_momento_no_tiene_hora(): void
    {
        $this->assertNull(
            Aprendiz::resolveProximaVisitaHoraFromProgramadas(
                '2026-06-01',
                '2026-07-01',
                '2026-08-01',
                false,
                false,
                false,
            )
        );
    }

    public function test_resolve_proxima_visita_hora_null_cuando_todo_completado(): void
    {
        $this->assertNull(
            Aprendiz::resolveProximaVisitaHoraFromProgramadas(
                '2026-06-01',
                '2026-07-01',
                '2026-08-01',
                true,
                true,
                true,
                [
                    ['fecha_programada' => '2026-09-01', 'hora_programada' => '14:30', 'completada' => 1],
                ],
                '09:00',
                '10:00',
                '11:00',
            )
        );
    }
}
