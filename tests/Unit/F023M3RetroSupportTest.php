<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Exports\F023M3RetroSupport;
use PHPUnit\Framework\TestCase;

final class F023M3RetroSupportTest extends TestCase
{
    protected function setUp(): void
    {
        if (!defined('BASE_PATH')) {
            define('BASE_PATH', dirname(__DIR__, 2));
        }
    }

    public function test_fills_retro_from_observaciones_when_retro_empty(): void
    {
        $out = F023M3RetroSupport::fillFromObservacionesIfEmpty([
            'obs_instructor' => 'Observación instructor',
            'obs_aprendiz' => '',
            'obs_coformador' => 'Prueba coformador',
            'm3_retro_instructor_proceso' => '',
            'm3_retro_instructor_desempeno' => '',
            'm3_retro_aprendiz_proceso' => '',
            'm3_retro_coformador_proceso' => '',
        ]);

        $this->assertSame('Observación instructor', $out['m3_retro_instructor_proceso']);
        $this->assertSame('Prueba coformador', $out['m3_retro_coformador_proceso']);
        $this->assertSame('', $out['m3_retro_aprendiz_proceso']);
    }

    public function test_does_not_overwrite_existing_retro(): void
    {
        $out = F023M3RetroSupport::fillFromObservacionesIfEmpty([
            'obs_instructor' => 'Observación instructor',
            'm3_retro_instructor_proceso' => 'Retro manual',
            'm3_retro_instructor_desempeno' => '',
        ]);

        $this->assertSame('Retro manual', $out['m3_retro_instructor_proceso']);
    }
}
