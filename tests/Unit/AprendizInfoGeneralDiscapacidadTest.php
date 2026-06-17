<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Models\AprendizInfoGeneral;
use PHPUnit\Framework\TestCase;

final class AprendizInfoGeneralDiscapacidadTest extends TestCase
{
    public function test_sin_check_limpia_tipo_asistencia_en_aprendiz(): void
    {
        $post = [
            'asistencia_nombre' => 'María',
            'asistencia_tipo' => 'Intérprete',
            'asistencia_contacto' => '3001234567',
        ];

        $this->assertFalse(AprendizInfoGeneral::presentaDiscapacidadEnPost($post));
        $this->assertNull(AprendizInfoGeneral::tipoAsistenciaAprendizFromPost($post));
    }

    public function test_con_check_conserva_tipo_asistencia(): void
    {
        $post = [
            'presenta_discapacidad' => '1',
            'asistencia_tipo' => 'Lenguaje de señas',
        ];

        $this->assertTrue(AprendizInfoGeneral::presentaDiscapacidadEnPost($post));
        $this->assertSame('Lenguaje de señas', AprendizInfoGeneral::tipoAsistenciaAprendizFromPost($post));
    }
}
