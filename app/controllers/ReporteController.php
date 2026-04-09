<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Exports\ReporteMaestroExport;
use App\Helpers\Database;

class ReporteController
{
    public function index(): void
    {
        $rows = Database::connection()->query(
            'SELECT a.id, a.numero_documento, a.nombre_completo, a.estado, rc.updated_at AS reporte_actualizado
             FROM aprendices a
             LEFT JOIN reporte_campos rc ON rc.aprendiz_id = a.id
             ORDER BY a.nombre_completo ASC'
        )->fetchAll();
        view('reports/maestro', ['rows' => $rows]);
    }

    public function update(): void
    {
        $aprendizId = (int) ($_POST['aprendiz_id'] ?? 0);
        $campo = (string) ($_POST['campo'] ?? '');
        $valor = (string) ($_POST['valor'] ?? '');
        if ($campo === '') {
            redirect(APP_BASE_PATH . '/reportes/maestro');
        }
        $sql = 'INSERT INTO reporte_campos (aprendiz_id, campo, valor, updated_at)
                VALUES (:aprendiz_id, :campo, :valor, NOW())
                ON DUPLICATE KEY UPDATE valor = VALUES(valor), updated_at = NOW()';
        Database::connection()->prepare($sql)->execute([
            'aprendiz_id' => $aprendizId,
            'campo' => $campo,
            'valor' => $valor,
        ]);
        redirect(APP_BASE_PATH . '/reportes/maestro');
    }

    public function export(): void
    {
        $path = (new ReporteMaestroExport())->export($_GET);
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . basename($path) . '"');
        readfile($path);
        exit;
    }
}
