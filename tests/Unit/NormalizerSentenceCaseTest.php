<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Helpers\Normalizer;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class NormalizerSentenceCaseTest extends TestCase
{
    #[DataProvider('sentenceCaseProvider')]
    public function test_normalize_sentence_case(string $input, string $expected): void
    {
        $this->assertSame($expected, Normalizer::normalizeSentenceCase($input));
    }

    /** @return array<string, array{0: string, 1: string}> */
    public static function sentenceCaseProvider(): array
    {
        return [
            'mayúsculas' => ['ANALIZAR REQUERIMIENTOS DE SOFTWARE', 'Analizar requerimientos de software'],
            'oraciones múltiples' => ['PRIMERA ORACIÓN. SEGUNDA ORACIÓN', 'Primera oración. Segunda oración'],
            'vacío' => ['', ''],
            'acentos' => ['ÉNFASIS EN DISEÑO', 'Énfasis en diseño'],
        ];
    }

    public function test_normalize_comma_list_sentence_case(): void
    {
        $input = 'COMPETENCIA UNO, COMPETENCIA DOS';
        $expected = 'Competencia uno, Competencia dos';
        $this->assertSame($expected, Normalizer::normalizeCommaListSentenceCase($input));
    }
}
