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
        try {
            $rows = ReporteMaestroData::rows($this->filtersFromRequest());
        } catch (\Throwable $e) {
            log_error('Reporte maestro index: ' . $e->getMessage());
            view('errors/500', [
                'message' => (defined('APP_DEBUG') && APP_DEBUG)
                    ? $e->getMessage()
                    : 'No se pudo cargar el reporte maestro.',
            ]);

            return;
        }

        view('reports/maestro', [
            'rows' => $rows,
            'activeFilters' => $this->filtersFromRequest(),
        ]);
    }

    public function update(): void
    {
        $aprendizId = (int) ($_POST['aprendiz_id'] ?? 0);

        $campo = trim((string) ($_POST['campo'] ?? ''));

        $valor = (string) ($_POST['valor'] ?? '');

        if ($campo === '' || $aprendizId <= 0) {
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

        try {
            Database::connection()
                ->prepare($sql)
                ->execute([
                    'aprendiz_id' => $aprendizId,
                    'campo' => $campo,
                    'valor' => $valor,
                ]);
        } catch (\Throwable $e) {
            log_error('Reporte maestro update: ' . $e->getMessage());
        }

        redirect(APP_BASE_PATH . '/reportes/maestro');
    }

    public function sync(): void
    {
        $raw = file_get_contents('php://input');
        $decoded = json_decode($raw !== false ? $raw : '', true);
        if (!is_array($decoded)) {
            $this->respondSync(false, 'Payload inválido.');

            return;
        }

        $rows = $decoded['rows'] ?? null;
        if (!is_array($rows)) {
            $aprendizId = (int) ($decoded['aprendiz_id'] ?? 0);
            $campos = $decoded['campos'] ?? null;
            if ($aprendizId > 0 && is_array($campos)) {
                $rows = [['aprendiz_id' => $aprendizId, 'campos' => $campos]];
            } else {
                $this->respondSync(false, 'Sin filas para sincronizar.');

                return;
            }
        }

        try {
            foreach ($rows as $row) {
                if (!is_array($row)) {
                    continue;
                }
                $aprendizId = (int) ($row['aprendiz_id'] ?? 0);
                $campos = $row['campos'] ?? null;
                if ($aprendizId <= 0 || !is_array($campos)) {
                    continue;
                }
                ReporteMaestroData::persistCampos($aprendizId, $campos);
            }
        } catch (\Throwable $e) {
            log_error('Reporte maestro sync: ' . $e->getMessage());
            $this->respondSync(false, 'No se pudieron guardar los cambios.');

            return;
        }

        $this->respondSync(true);
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

        if (!is_file($path)) {
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
     * @return array{estado?: string, ficha?: string, programa_id?: int, q?: string}
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

        $q = trim((string) ($_GET['q'] ?? ''));
        if ($q !== '') {
            $filters['q'] = $q;
        }

        return $filters;
    }

    private function respondSync(bool $ok, string $message = ''): void
    {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'ok' => $ok,
            'message' => $message,
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
}
