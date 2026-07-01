<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Services\DocumentoGeneradoRetention;
use PHPUnit\Framework\TestCase;

final class DocumentoGeneradoRetentionTest extends TestCase
{
    public function test_mantiene_solo_los_cinco_mas_recientes(): void
    {
        $now = new \DateTimeImmutable('2026-06-01 12:00:00');
        $rows = [];
        for ($i = 1; $i <= 7; $i++) {
            $rows[] = [
                'id' => $i,
                'created_at' => $now->modify('-' . $i . ' days')->format('Y-m-d H:i:s'),
            ];
        }

        $ids = DocumentoGeneradoRetention::selectIdsToPrune($rows, 5, 90, $now);

        $this->assertSame([6, 7], $ids);
    }

    public function test_elimina_documentos_mas_viejos_de_noventa_dias(): void
    {
        $now = new \DateTimeImmutable('2026-06-01 12:00:00');
        $rows = [
            ['id' => 1, 'created_at' => '2026-05-20 10:00:00'],
            ['id' => 2, 'created_at' => '2026-02-01 10:00:00'],
            ['id' => 3, 'created_at' => '2026-01-01 10:00:00'],
        ];

        $ids = DocumentoGeneradoRetention::selectIdsToPrune($rows, 5, 90, $now);

        $this->assertSame([2, 3], $ids);
    }

    public function test_aplica_ambas_reglas_a_la_vez(): void
    {
        $now = new \DateTimeImmutable('2026-06-01 12:00:00');
        $rows = [];
        for ($i = 1; $i <= 6; $i++) {
            $rows[] = [
                'id' => $i,
                'created_at' => $now->modify('-' . $i . ' days')->format('Y-m-d H:i:s'),
            ];
        }
        $rows[] = ['id' => 99, 'created_at' => '2025-01-01 08:00:00'];

        $ids = DocumentoGeneradoRetention::selectIdsToPrune($rows, 5, 90, $now);

        $this->assertContains(6, $ids);
        $this->assertContains(99, $ids);
        $this->assertCount(2, $ids);
    }

    public function test_constantes_de_retencion(): void
    {
        $this->assertSame(5, DocumentoGeneradoRetention::MAX_PER_APRENDIZ);
        $this->assertSame(90, DocumentoGeneradoRetention::RETENTION_DAYS);
    }
}
