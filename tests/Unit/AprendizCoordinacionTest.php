<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Models\Aprendiz;
use PHPUnit\Framework\TestCase;

final class AprendizCoordinacionTest extends TestCase
{
    public function test_normalize_quita_coordinacion_duplicada_de_jefe_grupo(): void
    {
        [$jefe, $coord] = Aprendiz::normalizeJefeGrupoCoordinacion('María Pérez', 'María Pérez');

        $this->assertSame('María Pérez', $jefe);
        $this->assertSame('', $coord);
    }

    public function test_normalize_conserva_valores_distintos(): void
    {
        [$jefe, $coord] = Aprendiz::normalizeJefeGrupoCoordinacion(
            'Carlos Gómez',
            'Coordinación Académica'
        );

        $this->assertSame('Carlos Gómez', $jefe);
        $this->assertSame('Coordinación Académica', $coord);
    }

    public function test_normalize_ignora_mayusculas_y_espacios_extra(): void
    {
        [$jefe, $coord] = Aprendiz::normalizeJefeGrupoCoordinacion(
            '  JUAN  DÍAZ ',
            'juan díaz'
        );

        $this->assertSame('JUAN  DÍAZ', $jefe);
        $this->assertSame('', $coord);
    }
}
