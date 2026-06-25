<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

final class ReporteMaestroNovedadesEtapaTest extends TestCase
{
    public function test_opciones_novedades_etapa_incluyen_valores_excel(): void
    {
        /** @var array<string, list<string>> $options */
        $options = require dirname(__DIR__, 2) . '/config/novedades_etapa_options.php';

        $this->assertContains('APRENDIZ SIN DATOS PARA CONTACTAR', $options['estado_etapa']);
        $this->assertContains('N/A', $options['llamados_atencion']);
        $this->assertContains('RENUNCIA POR PARTE DEL APRENDIZ', $options['otros_novedad']);
        $this->assertContains('CAMBIO DE MODALIDAD', $options['comite_evaluacion']);
        $this->assertContains('SIN RADICAR SOLICITUD', $options['reingreso_vencimiento']);
    }

    public function test_reingreso_vencimiento_exporta_texto(): void
    {
        $this->assertSame(
            'RADICÓ SOLICITUD',
            \App\Services\ReporteMaestroData::formatReingresoVencimiento('RADICÓ SOLICITUD')
        );
        $this->assertSame('Sí', \App\Services\ReporteMaestroData::formatReingresoVencimiento('1'));
        $this->assertSame('', \App\Services\ReporteMaestroData::formatReingresoVencimiento(''));
    }
}
