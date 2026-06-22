<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Exports\F023Generator;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

final class F023M3JuicioMarcasTest extends TestCase
{
    protected function setUp(): void
    {
        if (!defined('BASE_PATH')) {
            define('BASE_PATH', dirname(__DIR__, 2));
        }
    }

    public function test_m3_juicio_marca_aprobado(): void
    {
        $generator = new F023Generator();
        $method = new ReflectionMethod(F023Generator::class, 'juicioMarcasTemplateVars');
        $method->setAccessible(true);

        $vars = $method->invoke($generator, ['tipo' => 'M3', 'juicio_final' => 'Aprobado']);

        $this->assertSame('X', $vars['m3_juicio_marca_aprobado']);
        $this->assertSame('___', $vars['m3_juicio_marca_no_aprobado']);
    }

    public function test_m3_juicio_marca_no_aprobado(): void
    {
        $generator = new F023Generator();
        $method = new ReflectionMethod(F023Generator::class, 'juicioMarcasTemplateVars');
        $method->setAccessible(true);

        $vars = $method->invoke($generator, ['tipo' => 'M3', 'juicio_final' => 'No aprobado']);

        $this->assertSame('___', $vars['m3_juicio_marca_aprobado']);
        $this->assertSame('X', $vars['m3_juicio_marca_no_aprobado']);
    }
}
