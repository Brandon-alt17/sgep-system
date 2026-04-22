<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Helpers\ImportHistory;
use App\Helpers\Database;
use App\Imports\AprendicesImport;

class ImportacionController
{
    private const HISTORY_PER_PAGE = 10;

    public function upload(): void
    {
        $historyState = $this->historyPaginationState();
        view('import/upload', [
            'history' => $historyState['items'],
            'historyPage' => $historyState['page'],
            'historyTotalPages' => $historyState['totalPages'],
            'templateUrl' => $this->templateUrl(),
            'templateAvailable' => $this->templateAvailable(),
        ]);
    }

    public function process(): void
    {
        if (empty($_FILES['archivo']['tmp_name'])) {
            $errorMessage = 'Debe seleccionar un archivo.';
            if ($this->isAjaxRequest()) {
                $this->jsonResponse(['ok' => false, 'message' => $errorMessage], 422);
                return;
            }
            $historyState = $this->historyPaginationState();
            view('import/upload', [
                'history' => $historyState['items'],
                'historyPage' => $historyState['page'],
                'historyTotalPages' => $historyState['totalPages'],
                'templateUrl' => $this->templateUrl(),
                'templateAvailable' => $this->templateAvailable(),
                'flashError' => $errorMessage,
            ]);
            return;
        }

        $fileName = (string) ($_FILES['archivo']['name'] ?? 'archivo.xlsx');
        $resultado = (new AprendicesImport())->import($_FILES['archivo']['tmp_name']);
        $processed = (int) ($resultado['inserted'] ?? 0) + (int) ($resultado['updated'] ?? 0);
        $errorCount = count($resultado['errors'] ?? []);
        $status = $errorCount > 0
            ? ($processed > 0 ? 'Parcial' : 'Fallido')
            : 'Exitoso';

        $importId = date('YmdHis') . '-' . substr(bin2hex(random_bytes(4)), 0, 8);
        ImportHistory::add([
            'id' => $importId,
            'file_name' => $fileName,
            'date' => date('Y-m-d'),
            'records' => $processed,
            'status' => $status,
            'resultado' => $resultado,
        ]);

        $redirectUrl = rtrim((string) APP_BASE_PATH, '/') . '/importar/resultado?id=' . urlencode($importId);
        if ($this->isAjaxRequest()) {
            $this->jsonResponse(['ok' => true, 'redirect' => $redirectUrl]);
            return;
        }

        redirect($redirectUrl);
    }

    public function showResult(): void
    {
        $id = trim((string) ($_GET['id'] ?? ''));
        $entry = $id !== '' ? ImportHistory::findById($id) : null;

        if ($entry === null) {
            http_response_code(404);
            view('errors/404', ['uri' => '/importar/resultado?id=' . $id]);
            return;
        }

        view('import/preview', [
            'entry' => $entry,
            'resultado' => (array) ($entry['resultado'] ?? []),
        ]);
    }

    public function resolveConflict(): void
    {
        $raw = file_get_contents('php://input');
        $decoded = json_decode($raw ?: '', true);
        if (!is_array($decoded)) {
            $this->jsonResponse(['ok' => false, 'message' => 'Solicitud inválida.'], 422);
            return;
        }

        $aprendizId = (int) ($decoded['aprendiz_id'] ?? 0);
        $field = trim((string) ($decoded['field'] ?? ''));
        $action = trim((string) ($decoded['action'] ?? ''));
        $newValue = $decoded['value'] ?? null;

        if ($aprendizId <= 0 || $field === '' || ($action !== 'new' && $action !== 'current')) {
            $this->jsonResponse(['ok' => false, 'message' => 'Parámetros inválidos.'], 422);
            return;
        }

        $allowedFields = [
            'nombre_completo',
            'tipo_documento',
            'telefono',
            'correo_personal',
            'correo_institucional',
            'ficha',
            'programa_id',
            'empresa_id',
            'fecha_hora_formulario',
            'direccion_domicilio',
            'ciudad_domicilio',
            'alternativa_ep',
            'fecha_sofia',
            'nombre_instructor_seguimiento',
            'telefono_instructor_seguimiento',
            'tipo_asistencia',
            'sugerencias_comentarios',
            'ficha_curso',
            'jefe_grupo',
            'coordinacion',
        ];
        if (!in_array($field, $allowedFields, true)) {
            $this->jsonResponse(['ok' => false, 'message' => 'Campo no permitido.'], 422);
            return;
        }

        if ($action === 'current') {
            $this->jsonResponse(['ok' => true, 'updated' => false]);
            return;
        }

        $pdo = Database::connection();
        $sql = 'UPDATE aprendices SET ' . $field . ' = :value, updated_at = NOW() WHERE id = :id';
        $stmt = $pdo->prepare($sql);
        $normalizedValue = $this->normalizeConflictValue($field, $newValue);
        $stmt->execute([
            'value' => $normalizedValue,
            'id' => $aprendizId,
        ]);

        $this->jsonResponse(['ok' => true, 'updated' => $stmt->rowCount() > 0]);
    }

    private function templateUrl(): string
    {
        return (string) env('IMPORT_TEMPLATE_URL', rtrim((string) APP_BASE_PATH, '/') . '/templates/plantilla_seguimiento.xlsx');
    }

    private function templateAvailable(): bool
    {
        return is_file(base_path('public/templates/plantilla_seguimiento.xlsx'));
    }

    private function isAjaxRequest(): bool
    {
        $requestedWith = strtolower((string) ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? ''));
        return $requestedWith === 'xmlhttprequest';
    }

    private function normalizeConflictValue(string $field, mixed $value): mixed
    {
        if (!is_string($value)) {
            return $value;
        }

        $normalized = trim($value);
        if ($field === 'tipo_documento') {
            $lower = mb_strtolower($normalized);
            $lower = str_replace(
                ['á', 'é', 'í', 'ó', 'ú', 'ü'],
                ['a', 'e', 'i', 'o', 'u', 'u'],
                $lower
            );
            if (str_starts_with($lower, 'cedula')) {
                return 'CC';
            }
            if (str_starts_with($lower, 'tarjeta')) {
                return 'TI';
            }
        }

        return $normalized;
    }

    /** @return array{items: array<int, array<string, mixed>>, page: int, totalPages: int} */
    private function historyPaginationState(): array
    {
        $allHistory = ImportHistory::all();
        $totalItems = count($allHistory);
        $totalPages = max(1, (int) ceil($totalItems / self::HISTORY_PER_PAGE));
        $requestedPage = (int) ($_GET['page'] ?? 1);
        $page = min(max(1, $requestedPage), $totalPages);
        $offset = ($page - 1) * self::HISTORY_PER_PAGE;

        return [
            'items' => array_slice($allHistory, $offset, self::HISTORY_PER_PAGE),
            'page' => $page,
            'totalPages' => $totalPages,
        ];
    }

    /** @param array<string, mixed> $payload */
    private function jsonResponse(array $payload, int $statusCode = 200): void
    {
        http_response_code($statusCode);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($payload, JSON_UNESCAPED_UNICODE);
    }
}
