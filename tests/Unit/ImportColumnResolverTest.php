<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Imports\ImportColumnResolver;
use App\Imports\ImportScalar;
use PHPUnit\Framework\TestCase;

final class ImportColumnResolverTest extends TestCase
{
    public function test_resolve_usa_encabezado_cuando_la_columna_se_desplazo(): void
    {
        if (!defined('BASE_PATH')) {
            define('BASE_PATH', dirname(__DIR__, 2));
        }

        $header = array_fill(0, 32, '');
        $header[0] = 'Marca temporal';
        $header[28] = 'Sugerencias y comentarios';
        $header[30] = 'Jefe de grupo';
        $header[31] = 'Coordinación';

        $default = require dirname(__DIR__, 2) . '/config/import_mapping.php';
        $map = ImportColumnResolver::resolve($header, $default);

        $this->assertSame(30, $map['jefe_grupo']);
        $this->assertSame(31, $map['coordinacion']);
        $this->assertSame(28, $map['sugerencias_comentarios']);
        $this->assertSame(24, $map['nombre_instructor_seguimiento']);
        $this->assertSame(25, $map['telefono_instructor_seguimiento']);
    }

    public function test_no_asigna_telefono_a_nombre_instructor(): void
    {
        if (!defined('BASE_PATH')) {
            define('BASE_PATH', dirname(__DIR__, 2));
        }

        $header = array_fill(0, 30, '');
        $header[24] = 'Nombre del instructor de seguimiento';
        $header[25] = 'Teléfono del instructor de seguimiento';
        $header[26] = 'Tipo de asistencia';

        $default = require dirname(__DIR__, 2) . '/config/import_mapping.php';
        $map = ImportColumnResolver::resolve($header, $default);

        $this->assertSame(24, $map['nombre_instructor_seguimiento']);
        $this->assertSame(25, $map['telefono_instructor_seguimiento']);
        $this->assertSame(26, $map['tipo_asistencia']);
    }

    public function test_separa_ciudad_empresa_de_ciudad_domicilio(): void
    {
        if (!defined('BASE_PATH')) {
            define('BASE_PATH', dirname(__DIR__, 2));
        }

        $header = array_fill(0, 32, '');
        $header[9] = 'Ciudad de domicilio del aprendiz';
        $header[14] = 'Dirección de la empresa';
        $header[15] = 'Ciudad de la empresa';
        $header[16] = 'Dirección donde realiza la práctica';

        $default = require dirname(__DIR__, 2) . '/config/import_mapping.php';
        $map = ImportColumnResolver::resolve($header, $default);

        $this->assertSame(9, $map['ciudad_domicilio_aprendiz']);
        $this->assertSame(15, $map['ciudad_empresa']);
        $this->assertSame(16, $map['direccion_realiza_practica']);
    }

    public function test_resuelve_correo_instructor_seguimiento_como_ultima_columna(): void
    {
        if (!defined('BASE_PATH')) {
            define('BASE_PATH', dirname(__DIR__, 2));
        }

        $header = array_fill(0, 33, '');
        $header[24] = 'Nombre del instructor de seguimiento';
        $header[25] = 'Teléfono del instructor de seguimiento';
        $header[26] = 'Tipo de asistencia';
        $header[30] = 'Ciudad de la empresa';
        $header[31] = 'Jefe de grupo';
        $header[32] = 'Correo del instructor de seguimiento';

        $default = require dirname(__DIR__, 2) . '/config/import_mapping.php';
        $map = ImportColumnResolver::resolve($header, $default);

        $this->assertSame(32, $map['correo_instructor_seguimiento']);
        $this->assertSame(24, $map['nombre_instructor_seguimiento']);
        $this->assertSame(11, $map['correo_electronico_institucional']);
    }

    public function test_import_scalar_ignora_placeholders(): void
    {
        $this->assertTrue(ImportScalar::isIgnorable('N/A'));
        $this->assertTrue(ImportScalar::isIgnorable('no'));
        $this->assertSame('', ImportScalar::clean('N/A'));
        $this->assertSame('María Pérez', ImportScalar::clean('María Pérez'));
    }
}
