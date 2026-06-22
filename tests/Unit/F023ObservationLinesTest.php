<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Exports\F023ObservationLines;
use PHPUnit\Framework\TestCase;

final class F023ObservationLinesTest extends TestCase
{
    protected function setUp(): void
    {
        if (!defined('BASE_PATH')) {
            define('BASE_PATH', dirname(__DIR__, 2));
        }
    }

    public function test_split_empty_text(): void
    {
        $this->assertSame(['', ''], F023ObservationLines::split(''));
    }

    public function test_split_short_text_on_first_line_only(): void
    {
        $this->assertSame(['Hola mundo', ''], F023ObservationLines::split('Hola mundo'));
    }

    public function test_split_long_text_into_two_lines(): void
    {
        $perLine = F023ObservationLines::charsPerLine();
        $line1 = str_repeat('a', $perLine);
        $line2 = 'b';
        [$first, $second] = F023ObservationLines::split($line1 . $line2);

        $this->assertSame($line1, $first);
        $this->assertSame($line2, $second);
    }

    public function test_template_line_value_empty_lines(): void
    {
        $perLine = F023ObservationLines::charsPerLine();
        $this->assertSame("\u{00A0}", F023ObservationLines::templateLineValue('', 1));
        $this->assertSame("\u{00A0}", F023ObservationLines::templateLineValue('', 2));
        $this->assertSame('Texto', F023ObservationLines::templateLineValue('Texto', 2));
    }

    public function test_expand_template_vars_adds_second_line_macro(): void
    {
        $vars = F023ObservationLines::expandTemplateVars(
            ['obs_instructor' => 'Uno'],
            ['obs_instructor']
        );

        $this->assertSame('Uno', $vars['obs_instructor']);
        $this->assertSame("\u{00A0}", $vars['obs_instructor_l2']);
        $this->assertSame('', $vars['obs_instructor_l3']);
    }

    public function test_split_lines_supports_more_than_two_rows(): void
    {
        $perLine = F023ObservationLines::charsPerLine();
        $text = str_repeat('a', $perLine) . ' ' . str_repeat('b', $perLine) . ' ' . str_repeat('c', 20);
        $lines = F023ObservationLines::splitLines($text, $perLine * 3);

        $this->assertCount(3, $lines);
        $this->assertSame(str_repeat('a', $perLine), $lines[0]);
        $this->assertSame(str_repeat('b', $perLine), $lines[1]);
        $this->assertSame(str_repeat('c', 20), $lines[2]);
    }

    public function test_split_lines_respects_explicit_newlines(): void
    {
        $text = "Esto es una observación de prueba...\n"
            . "Esto es una observación de prueba...\n"
            . 'Esto es una observación de prueba...';
        $lines = F023ObservationLines::splitLines($text, 396);

        $this->assertCount(3, $lines);
        $this->assertSame('Esto es una observación de prueba...', $lines[0]);
        $this->assertSame('Esto es una observación de prueba...', $lines[1]);
        $this->assertSame('Esto es una observación de prueba...', $lines[2]);
    }
}
