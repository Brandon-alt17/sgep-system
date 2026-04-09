<?php

declare(strict_types=1);

namespace App\Exports;

use App\Helpers\Database;
use Dompdf\Dompdf;
use PhpOffice\PhpWord\TemplateProcessor;

class F023Generator
{
    public function generate(int $aprendizId, array $partes, string $formato): string
    {
        $aprendiz = $this->fetchAprendiz($aprendizId);
        if ($aprendiz === null) {
            throw new \RuntimeException('Aprendiz no encontrado.');
        }

        if ($formato === 'pdf') {
            $dompdf = new Dompdf();
            $dompdf->loadHtml('<h1>GFPI-F-023</h1><p>' . htmlspecialchars((string) $aprendiz['nombre_completo']) . '</p>');
            $dompdf->render();
            $path = base_path('storage/documents/F023_' . $aprendizId . '_' . time() . '.pdf');
            file_put_contents($path, $dompdf->output());
            return $path;
        }

        $templatePath = base_path('storage/templates/GFPI-F-023_V06.docx');
        if (!file_exists($templatePath)) {
            throw new \RuntimeException('Plantilla F-023 no encontrada.');
        }
        $tpl = new TemplateProcessor($templatePath);
        $tpl->setValue('nombre_aprendiz', (string) $aprendiz['nombre_completo']);
        $path = base_path('storage/documents/F023_' . $aprendizId . '_' . time() . '.docx');
        $tpl->saveAs($path);
        return $path;
    }

    private function fetchAprendiz(int $aprendizId): ?array
    {
        $stmt = Database::connection()->prepare('SELECT * FROM aprendices WHERE id=:id');
        $stmt->execute(['id' => $aprendizId]);
        return $stmt->fetch() ?: null;
    }
}
