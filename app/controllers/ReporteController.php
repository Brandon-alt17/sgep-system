<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Exports\ReporteMaestroExport;
use App\Helpers\Database;
use App\Services\ReporteMaestroData;
use RuntimeException;

class ReporteController
{
    public function index(): void
    {
        view('reports/maestro', [
            'rows' => ReporteMaestroData::rows($this->filtersFromRequest()),
        ]);
    }

    public function update(): void
    {
        $aprendizId = (int) ($_POST['aprendiz_id'] ?? 0);

        $campo = (string) ($_POST['campo'] ?? '');

        $valor = (string) ($_POST['valor'] ?? '');

        if ($campo === '') {
            redirect(APP_BASE_PATH . '/reportes/maestro');
        }

        $sql = '
            INSERT INTO reporte_campos (
                aprendiz_id,
                campo,
                valor,
                updated_at
            )
            VALUES (
                :aprendiz_id,
                :campo,
                :valor,
                NOW()
            )
            ON DUPLICATE KEY UPDATE
                valor = VALUES(valor),
                updated_at = NOW()
        ';

        Database::connection()
            ->prepare($sql)
            ->execute([
                'aprendiz_id' => $aprendizId,
                'campo' => $campo,
                'valor' => $valor,
            ]);

        redirect(APP_BASE_PATH . '/reportes/maestro');
    }

    public function export(): void
    {
        try {
            $path = (new ReporteMaestroExport())
                ->export($this->filtersFromRequest());
        } catch (RuntimeException $e) {
            log_error('Reporte maestro export: ' . $e->getMessage());
            redirect(APP_BASE_PATH . '/reportes/maestro');

            return;
        }

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header(
            'Content-Disposition: attachment; filename="' .
            ReporteMaestroExport::downloadFilename() .
            '"'
        );

        readfile($path);
        exit;
    }

    /**
     * @return array{estado?: string, ficha?: string, programa_id?: int}
     */
    private function filtersFromRequest(): array
    {
        $filters = [];
        $estado = trim((string) ($_GET['estado'] ?? ''));
        if ($estado !== '') {
            $filters['estado'] = $estado;
        }

        $ficha = trim((string) ($_GET['ficha'] ?? ''));
        if ($ficha !== '') {
            $filters['ficha'] = $ficha;
        }

        $programaId = (int) ($_GET['programa_id'] ?? 0);
        if ($programaId > 0) {
            $filters['programa_id'] = $programaId;
        }

        return $filters;
    }
}
