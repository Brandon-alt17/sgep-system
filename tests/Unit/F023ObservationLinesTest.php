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
        $this->assertSame(str_repeat('_', $perLine), F023ObservationLines::templateLineValue('', 2));
        $this->assertSame("\u{00A0}", F023ObservationLines::templateLineValue('', 1));
        $this->assertSame('Texto', F023ObservationLines::templateLineValue('Texto', 2));
    }

    public function test_expand_template_vars_adds_second_line_macro(): void
    {
        $vars = F023ObservationLines::expandTemplateVars(
            ['obs_instructor' => 'Uno'],
            ['obs_instructor']
        );

        $this->assertSame('Uno', $vars['obs_instructor']);
        $this->assertSame(str_repeat('_', F023ObservationLines::charsPerLine()), $vars['obs_instructor_l2']);
    }
}
