<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Helpers\ImportHistory;
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
