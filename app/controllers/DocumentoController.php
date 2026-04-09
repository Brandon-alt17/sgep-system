<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Exports\F023Generator;
use App\Helpers\Database;

class DocumentoController
{
    public function create(): void
    {
        $aprendizId = (int) ($_GET['aprendiz_id'] ?? 0);
        view('documents/generate', ['aprendiz_id' => $aprendizId]);
    }

    public function generate(): void
    {
        $aprendizId = (int) ($_POST['aprendiz_id'] ?? 0);
        $partes = $_POST['partes'] ?? [];
        $formato = (string) ($_POST['formato'] ?? 'docx');
        $path = (new F023Generator())->generate($aprendizId, (array) $partes, $formato);

        Database::connection()->prepare(
            'INSERT INTO documentos_generados (aprendiz_id, partes, formato, ruta_archivo, created_at) VALUES (:aprendiz_id, :partes, :formato, :ruta, NOW())'
        )->execute([
            'aprendiz_id' => $aprendizId,
            'partes' => json_encode($partes, JSON_UNESCAPED_UNICODE),
            'formato' => $formato,
            'ruta' => $path,
        ]);

        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . basename($path) . '"');
        readfile($path);
        exit;
    }
}
