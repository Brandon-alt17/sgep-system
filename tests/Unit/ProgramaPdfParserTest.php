<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Helpers\ProgramaPdfParser;
use PHPUnit\Framework\TestCase;

final class ProgramaPdfParserTest extends TestCase
{
    protected function setUp(): void
    {
        if (!defined('BASE_PATH')) {
            define('BASE_PATH', dirname(__DIR__, 2));
        }
    }

    public function test_detects_nombre_from_heading_line_ignoring_scrambled_table_cell_text(): void
    {
        // Reproduce el patron real: la celda "1.1 Denominacion del Programa" se extrae con texto
        // ajeno intercalado ("El programa aun se encuentra vigente"), pero el nombre real siempre
        // aparece como titulo suelto justo antes del encabezado "1. INFORMACION BASICA...".
        $page = <<<TXT
        MARKETING DIGITAL PARA EL SISTEMA MODA
        1. INFORMACION BÁSICA DEL PROGRAMA DE FORMACION TITULADA
        1.1 Denominación
        del Programa:
        El programa aún se encuentra vigente
        2
        1.2. Código
        Programa:
        1.3. Versión
        Programa:
        135329
        TXT;

        $result = ProgramaPdfParser::parsePages([$page]);

        $this->assertSame('MARKETING DIGITAL PARA EL SISTEMA MODA', $result['meta']['nombre']);
    }

    public function test_detects_nombre_split_across_two_lines_before_heading(): void
    {
        $page = <<<TXT
        IMPLEMENTACION DE INFRAESTRUCTURA DE TECNOLOGIAS DE LA
        INFORMACION Y LAS COMUNICACIONES.
        1. INFORMACION BÁSICA DEL PROGRAMA DE FORMACION TITULADA
        1.1 Denominación
        del Programa:
        El programa aún se encuentra vigente
        1
        1.2. Código
        TXT;

        $result = ProgramaPdfParser::parsePages([$page]);

        $this->assertSame(
            'IMPLEMENTACION DE INFRAESTRUCTURA DE TECNOLOGIAS DE LA INFORMACION Y LAS COMUNICACIONES.',
            $result['meta']['nombre']
        );
    }

    public function test_nombre_is_independent_of_filename_hint_since_it_reads_the_pdf_content(): void
    {
        // El nombre del archivo no influye en el parser: viene exclusivamente del texto del PDF.
        $page = <<<TXT
        GESTION DE REDES DE DATOS
        1. INFORMACION BÁSICA DEL PROGRAMA DE FORMACION TITULADA
        1.1 Denominación
        del Programa:
        El programa aún se encuentra vigente
        2
        TXT;

        $result = ProgramaPdfParser::parsePages([$page]);

        $this->assertSame('GESTION DE REDES DE DATOS', $result['meta']['nombre']);
    }

    public function test_returns_empty_nombre_with_warning_for_obsolete_format_without_numbered_heading(): void
    {
        // Formato obsoleto (p. ej. plantilla previa a la numeracion "1."): no debe inventarse un
        // nombre; debe quedar vacio para que el caller (CatalogoController) use el fallback de
        // nombre de archivo con advertencia.
        $page = <<<TXT
        Modelo de
        Mejora Continua
        TECNOLOGÍAS DE LA INFORMACIÓN, DISEÑO Y DESARROLLO DE SOFTWARE
        VERSIÓN: ESTADO:
        DURACIÓN
        MÁXIMA
        ESTIMADA DEL
        TXT;

        $result = ProgramaPdfParser::parsePages([$page]);

        $this->assertSame('', $result['meta']['nombre']);
        $messages = array_map(static fn (array $w): string => $w['message'], $result['warnings']);
        $this->assertContains('No se detecto nombre de programa automaticamente.', $messages);
    }

    public function test_strips_page_footer_noise_glued_without_spaces_from_resultados(): void
    {
        // Patron real observado: "Página 8 de 68" se extrae sin espacio entre "de" y el total de
        // paginas, y ese numero queda pegado a la marca de fecha/hora del pie de pagina
        // ("Página 8 de6817/10/24 11:34"), lo que antes rompia las validaciones de ruido basadas en
        // espacios/limites de palabra y dejaba el pie de pagina como parte (o totalidad) de un
        // resultado de aprendizaje.
        $page = <<<TXT
        4. CONTENIDOS CURRICULARES DE LA COMPETENCIA
        4.5 Resultados de aprendizaje
        03 VERIFICAR LAS TRANSFORMACIONES FÍSICAS DE LA MATERIA UTILIZANDO HERRAMIENTAS
        Página 8 de6817/10/24 11:34
        04 IDENTIFICAR LOS COMPONENTES DEL SISTEMA
        Página 20 de6817/10/24 11:34
        TXT;

        $result = ProgramaPdfParser::parsePages([$page]);

        $descripciones = [];
        foreach ($result['competencias'] as $comp) {
            foreach ($comp['resultados'] as $r) {
                $descripciones[$r['codigo']] = $r['descripcion'];
            }
        }

        $this->assertSame([
            'RA3' => 'VERIFICAR LAS TRANSFORMACIONES FÍSICAS DE LA MATERIA UTILIZANDO HERRAMIENTAS',
            'RA4' => 'IDENTIFICAR LOS COMPONENTES DEL SISTEMA',
        ], $descripciones);
    }
}
