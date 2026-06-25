<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Services\F023InfoData;
use PHPUnit\Framework\TestCase;

final class ProgramaNombreCanonicalTest extends TestCase
{
    public function test_programa_nombre_canonical_prefiere_catalogo(): void
    {
        $this->assertSame(
            'Técnico en Programación de Software',
            programa_nombre_canonical(
                ['nombre' => 'Técnico en Programación de Software'],
                ['archivo_programa.pdf', 'Texto del Excel']
            )
        );
    }

    public function test_f023_info_usa_nombre_de_catalogo_sobre_guardada(): void
    {
        $info = F023InfoData::build(
            ['nombre_completo' => 'Ana', 'ficha' => '123'],
            ['nombre' => 'Nombre del catálogo', 'nivel' => 'Tecnólogo'],
            null,
            ['programa_formacion' => 'nombre_archivo_subido.pdf', 'nivel_formativo' => 'Auxiliar']
        );

        $this->assertSame('Nombre del catálogo', $info['programa_formacion']);
        $this->assertSame('Tecnólogo', $info['nivel_formativo']);
    }

    public function test_programa_meta_canonical_prevalece_sobre_resumen_importacion(): void
    {
        $meta = programa_meta_canonical(
            ['codigo' => '228118', 'nombre' => 'Nombre PDF confirmado', 'nivel' => 'Tecnólogo'],
            ['codigo' => '000', 'nombre' => 'programa_formacion_archivo.pdf', 'nivel' => 'Auxiliar']
        );

        $this->assertSame('228118', $meta['codigo']);
        $this->assertSame('Nombre PDF confirmado', $meta['nombre']);
        $this->assertSame('Tecnólogo', $meta['nivel']);
    }
}
