<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

final class F023ExportBasenameTest extends TestCase
{
    public function test_basename_usa_nombre_y_grupo(): void
    {
        $name = f023_export_basename('Juan Manuel Ríos Ricaurte', '3002085', 'docx');

        $this->assertSame('Juan Manuel Ríos Ricaurte 3002085.docx', $name);
    }

    public function test_basename_quita_caracteres_invalidos(): void
    {
        $name = f023_export_basename('Ana/López: Test', '12*34', 'pdf');

        $this->assertSame('AnaLópez Test 1234.pdf', $name);
    }

    public function test_basename_conserva_tildes(): void
    {
        $name = f023_export_basename('María José Niño', '2829411', 'pdf');

        $this->assertSame('María José Niño 2829411.pdf', $name);
        $this->assertTrue(mb_check_encoding($name, 'UTF-8'));
    }

    public function test_content_disposition_utf8(): void
    {
        $header = content_disposition_attachment('Juan Manuel Ríos Ricaurte 3002085.docx');

        $this->assertStringContainsString('filename="Juan Manuel R', $header);
        $this->assertStringContainsString("filename*=UTF-8''Juan%20Manuel%20R%C3%ADos%20Ricaurte%203002085.docx", $header);
    }
}
