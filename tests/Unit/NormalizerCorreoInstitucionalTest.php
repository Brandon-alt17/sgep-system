<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Helpers\Normalizer;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Pruebas de normalización / validación de correo institucional SENA (RF-03, RN-01).
 */
final class NormalizerCorreoInstitucionalTest extends TestCase
{
    #[DataProvider('correosInstitucionalesValidosProvider')]
    public function test_correo_institucional_valido(string $email): void
    {
        $this->assertTrue(Normalizer::isCorreoInstitucionalSenaValid($email));
    }

    /** @return array<string, array{0: string}> */
    public static function correosInstitucionalesValidosProvider(): array
    {
        return [
            'soy.sena.edu.co' => ['aprendiz@soy.sena.edu.co'],
            'sena.edu.co directo' => ['instructor@sena.edu.co'],
            'vacío opcional' => [''],
            'normaliza mayúsculas en fila' => ['APRENDIZ@SOY.SENA.EDU.CO'],
        ];
    }

    #[DataProvider('correosInstitucionalesInvalidosProvider')]
    public function test_correo_institucional_invalido_sin_dominio_sena(string $email): void
    {
        $this->assertFalse(Normalizer::isCorreoInstitucionalSenaValid($email));
    }

    /** @return array<string, array{0: string}> */
    public static function correosInstitucionalesInvalidosProvider(): array
    {
        return [
            'gmail personal' => ['aprendiz@gmail.com'],
            'dominio similar falso' => ['aprendiz@senaedu.co'],
            'sin arroba' => ['correo-invalido'],
            'hotmail' => ['user@hotmail.com'],
        ];
    }

    public function test_normalize_row_convierte_correo_institucional_a_minusculas(): void
    {
        $row = Normalizer::normalizeRow([
            'correo_institucional' => '  USUARIO@SOY.SENA.EDU.CO  ',
        ]);

        $this->assertSame('usuario@soy.sena.edu.co', $row['correo_institucional']);
        $this->assertTrue(Normalizer::isCorreoInstitucionalSenaValid($row['correo_institucional']));
    }

    public function test_correo_normalizado_personal_sigue_siendo_invalido_como_institucional(): void
    {
        $row = Normalizer::normalizeRow([
            'correo_electronico_institucional' => 'Persona@Gmail.COM',
        ]);

        $this->assertSame('persona@gmail.com', $row['correo_electronico_institucional']);
        $this->assertFalse(
            Normalizer::isCorreoInstitucionalSenaValid($row['correo_electronico_institucional'])
        );
    }
}
