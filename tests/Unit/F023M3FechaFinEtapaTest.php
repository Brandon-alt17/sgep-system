<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Exports\F023Generator;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

final class F023M3FechaFinEtapaTest extends TestCase
{
    protected function setUp(): void
    {
        if (!defined('BASE_PATH')) {
            define('BASE_PATH', dirname(__DIR__, 2));
        }
        if (!function_exists('date_iso_to_dmY')) {
            require BASE_PATH . '/app/helpers/helpers.php';
        }
    }

    public function test_m3_maps_fecha_visita_to_fecha_fin_etapa_for_export(): void
    {
        $generator = new F023Generator();
        $method = new ReflectionMethod(F023Generator::class, 'momentoRowTemplateVars');
        $method->setAccessible(true);

        $vars = $method->invoke($generator, [
            'tipo' => 'M3',
            'fecha_visita' => '2026-06-15',
            'fecha_fin_etapa' => null,
        ]);

        $this->assertSame('15/06/2026', $vars['fecha_fin_etapa']);
        $this->assertSame('15/06/2026', $vars['fecha_visita']);
    }

    public function test_m3_keeps_explicit_fecha_fin_etapa_when_present(): void
    {
        $generator = new F023Generator();
        $method = new ReflectionMethod(F023Generator::class, 'momentoRowTemplateVars');
        $method->setAccessible(true);

        $vars = $method->invoke($generator, [
            'tipo' => 'M3',
            'fecha_visita' => '2026-06-15',
            'fecha_fin_etapa' => '2026-07-01',
        ]);

        $this->assertSame('01/07/2026', $vars['fecha_fin_etapa']);
    }
}
