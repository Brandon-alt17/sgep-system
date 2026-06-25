<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use ReflectionMethod;

final class ReporteMaestroObservacionesEstadoTest extends TestCase
{
    private static function invoke(string $method, mixed ...$args): mixed
    {
        $ref = new ReflectionMethod(\App\Services\ReporteMaestroData::class, $method);
        $ref->setAccessible(true);

        return $ref->invoke(null, ...$args);
    }

    public function test_observaciones_novedad_usa_panel_y_no_importacion(): void
    {
        $rc = ['observaciones_cert' => 'Observación del panel'];

        $this->assertSame(
            'Observación del panel',
            self::invoke('resolveObservacionesNovedad', $rc)
        );

        $rcConNovedad = [
            'observaciones_novedad' => 'Novedad registrada',
            'observaciones_cert' => 'Otra observación',
        ];
        $this->assertSame(
            'Novedad registrada',
            self::invoke('resolveObservacionesNovedad', $rcConNovedad)
        );
    }

    public function test_estado_etapa_rechaza_no_y_usa_estado_aprendiz(): void
    {
        $rc = ['estado_etapa' => 'No', 'estado_aprendiz' => 'En formación'];
        $row = ['estado' => 'Pendiente por iniciar'];

        $this->assertSame(
            'Pendiente por iniciar',
            self::invoke('resolveEstadoEtapa', $rc, $row)
        );
    }
}
