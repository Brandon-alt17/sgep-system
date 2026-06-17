<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Helpers\Normalizer;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Campo correo institucional: texto libre (sin validación de formato email).
 */
final class NormalizerCorreoInstitucionalTest extends TestCase
{
    #[DataProvider('correosInstitucionalesLibresProvider')]
    public function test_correo_institucional_acepta_cualquier_texto(string $value): void
    {
        $this->assertTrue(Normalizer::isCorreoInstitucionalSenaValid($value));
    }

    /** @return array<string, array{0: string}> */
    public static function correosInstitucionalesLibresProvider(): array
    {
        return [
            'soy.sena.edu.co' => ['aprendiz@soy.sena.edu.co'],
            'gmail personal' => ['aprendiz@gmail.com'],
            'sin arroba' => ['Sin correo institucional'],
            'vacío' => [''],
            'teléfono u otro dato' => ['3001234567'],
        ];
    }

    public function test_normalize_row_preserva_texto_institucional_sin_forzar_minusculas(): void
    {
        $row = Normalizer::normalizeRow([
            'correo_institucional' => '  Sin Correo Institucional  ',
            'correo_electronico_institucional' => '  NO APLICA  ',
        ]);

        $this->assertSame('Sin Correo Institucional', $row['correo_institucional']);
        $this->assertSame('NO APLICA', $row['correo_electronico_institucional']);
    }

    public function test_normalize_row_sigue_normalizando_correo_personal(): void
    {
        $row = Normalizer::normalizeRow([
            'correo_personal' => '  Persona@Gmail.COM  ',
        ]);

        $this->assertSame('persona@gmail.com', $row['correo_personal']);
    }
}
