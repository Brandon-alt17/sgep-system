<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Exports\F023DynamicFontScale;
use PHPUnit\Framework\TestCase;

final class F023DynamicFontScaleTest extends TestCase
{
    protected function setUp(): void
    {
        if (!defined('BASE_PATH')) {
            define('BASE_PATH', dirname(__DIR__, 2));
        }
    }

    public function test_plain_text_length_counts_all_characters_like_js_counter_and_server_truncation(): void
    {
        // Mismo criterio que value.length (public/js/app.js) y mb_substr() (MomentoController::applyTextLimits()).
        $this->assertSame(0, F023DynamicFontScale::plainTextLength(''));
        $this->assertSame(7, F023DynamicFontScale::plainTextLength("   \n\t  "));
        $this->assertSame(13, F023DynamicFontScale::plainTextLength("Hola   mundo\n"));
    }

    public function test_size_for_length_returns_max_when_at_or_below_recommended(): void
    {
        $this->assertSame(18, F023DynamicFontScale::sizeForLength(0, 600, 1200, 18, 12));
        $this->assertSame(18, F023DynamicFontScale::sizeForLength(600, 600, 1200, 18, 12));
    }

    public function test_size_for_length_returns_floor_when_at_or_above_hard_max(): void
    {
        $this->assertSame(12, F023DynamicFontScale::sizeForLength(1200, 600, 1200, 18, 12));
        $this->assertSame(12, F023DynamicFontScale::sizeForLength(5000, 600, 1200, 18, 12));
    }

    public function test_size_for_length_interpolates_between_thresholds(): void
    {
        // Punto medio exacto entre 600 y 1200 -> 15 en crudo, redondeado al paso par más cercano (16).
        $this->assertSame(16, F023DynamicFontScale::sizeForLength(900, 600, 1200, 18, 12));
    }

    public function test_size_for_length_rounds_to_steps_of_two_half_points(): void
    {
        $size = F023DynamicFontScale::sizeForLength(950, 600, 1200, 18, 12);
        $this->assertSame(0, $size % 2);
        $this->assertGreaterThanOrEqual(12, $size);
        $this->assertLessThanOrEqual(18, $size);
    }

    public function test_size_for_length_is_monotonic_non_increasing(): void
    {
        $previous = 18;
        foreach ([0, 200, 400, 600, 700, 800, 900, 1000, 1100, 1200, 1300] as $length) {
            $size = F023DynamicFontScale::sizeForLength($length, 600, 1200, 18, 12);
            $this->assertLessThanOrEqual($previous, $size);
            $previous = $size;
        }
    }

    public function test_size_for_length_ignores_misconfigured_thresholds(): void
    {
        // recomendado ausente/0 o hardMax <= recomendado: nunca reduce (config incompleta, comportamiento seguro).
        $this->assertSame(18, F023DynamicFontScale::sizeForLength(5000, 0, 1200, 18, 12));
        $this->assertSame(18, F023DynamicFontScale::sizeForLength(5000, 1200, 1200, 18, 12));
        $this->assertSame(18, F023DynamicFontScale::sizeForLength(5000, 1200, 600, 18, 12));
    }

    public function test_size_for_length_noop_when_floor_not_below_max(): void
    {
        $this->assertSame(18, F023DynamicFontScale::sizeForLength(5000, 600, 1200, 18, 18));
        $this->assertSame(18, F023DynamicFontScale::sizeForLength(5000, 600, 1200, 18, 20));
    }

    public function test_rewrite_run_font_size_replaces_sz_and_szcs(): void
    {
        $run = '<w:r><w:rPr><w:sz w:val="18"/><w:szCs w:val="18"/></w:rPr><w:t>Hola</w:t></w:r>';
        $result = F023DynamicFontScale::rewriteRunFontSize($run, 12);

        $this->assertStringContainsString('<w:sz w:val="12"/>', $result);
        $this->assertStringContainsString('<w:szCs w:val="12"/>', $result);
    }

    public function test_rewrite_run_font_size_replaces_sz_without_szcs(): void
    {
        $run = '<w:r><w:rPr><w:sz w:val="18"/></w:rPr><w:t>Hola</w:t></w:r>';
        $result = F023DynamicFontScale::rewriteRunFontSize($run, 12);

        $this->assertStringContainsString('<w:sz w:val="12"/>', $result);
        $this->assertStringNotContainsString('w:szCs', $result);
    }

    public function test_rewrite_run_font_size_is_noop_without_rpr(): void
    {
        $run = '<w:r><w:t>Hola</w:t></w:r>';
        $result = F023DynamicFontScale::rewriteRunFontSize($run, 12);

        $this->assertSame($run, $result);
    }

    public function test_rewrite_run_font_size_clamps_minimum(): void
    {
        $run = '<w:r><w:rPr><w:sz w:val="18"/></w:rPr><w:t>Hola</w:t></w:r>';
        $result = F023DynamicFontScale::rewriteRunFontSize($run, 0);

        $this->assertStringContainsString('<w:sz w:val="2"/>', $result);
    }

    public function test_scale_exact_line_spacing_scales_line_and_after_proportionally(): void
    {
        $paragraph = '<w:pPr><w:spacing w:after="60" w:before="0" w:line="276" w:lineRule="exact"/></w:pPr>';
        // Piso 12 sobre base 18 -> factor 12/18.
        $result = F023DynamicFontScale::scaleExactLineSpacing($paragraph, 12, 18);

        $this->assertStringContainsString('w:line="184"', $result);
        $this->assertStringContainsString('w:after="40"', $result);
        $this->assertStringContainsString('w:before="0"', $result);
    }

    public function test_scale_exact_line_spacing_ignores_non_exact_spacing(): void
    {
        $paragraph = '<w:pPr><w:spacing w:after="160" w:line="259" w:lineRule="auto"/></w:pPr>';
        $result = F023DynamicFontScale::scaleExactLineSpacing($paragraph, 12, 18);

        $this->assertSame($paragraph, $result);
    }

    public function test_scale_exact_line_spacing_noop_when_target_not_below_base(): void
    {
        $paragraph = '<w:pPr><w:spacing w:after="60" w:line="276" w:lineRule="exact"/></w:pPr>';
        $result = F023DynamicFontScale::scaleExactLineSpacing($paragraph, 18, 18);

        $this->assertSame($paragraph, $result);
    }
}
