<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Helpers\ImportHistory;
use App\Helpers\Database;
use App\Imports\AprendicesImport;
use App\Imports\AprendicesImportValidator;
use App\Models\EmpresaJefe;
use App\Models\Programa;
use App\Models\ProgramaEnlacePendiente;

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
            $errorMessage = 'Seleccione un archivo.';
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
        $tmpPath = (string) $_FILES['archivo']['tmp_name'];
        $fileSize = (int) ($_FILES['archivo']['size'] ?? 0);

        $validation = (new AprendicesImportValidator())->validate($tmpPath, $fileName, $fileSize);
        if (!$validation['valid']) {
            $this->respondImportValidationFailed($validation['errors']);
            return;
        }

        $resultado = (new AprendicesImport())->import($tmpPath);
        ProgramaEnlacePendiente::syncAprendicesSinVinculoValido();
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

        $resultado = (array) ($entry['resultado'] ?? []);
        $tieneProgramasPendientes = ($resultado['programa_pending_rows'] ?? []) !== [];

        view('import/preview', [
            'entry' => $entry,
            'resultado' => $resultado,
            'pendientesEnlaceCount' => $tieneProgramasPendientes ? Programa::countPendientesEnlace() : 0,
        ]);
    }

    public function showConflicts(): void
    {
        $id = trim((string) ($_GET['id'] ?? ''));
        $entry = $id !== '' ? ImportHistory::findById($id) : null;

        if ($entry === null) {
            http_response_code(404);
            view('errors/404', ['uri' => '/importar/conflictos?id=' . $id]);

            return;
        }

        $resultado = (array) ($entry['resultado'] ?? []);
        $base = rtrim((string) APP_BASE_PATH, '/');
        $importCtx = import_nav_context();
        $searchQ = trim((string) ($_GET['q'] ?? ''));
        $allConflictRows = (array) ($resultado['conflict_rows'] ?? []);
        if ($searchQ !== '') {
            $needle = mb_strtolower($searchQ);
            $allConflictRows = array_values(array_filter(
                $allConflictRows,
                static function (mixed $row) use ($needle): bool {
                    if (!is_array($row)) {
                        return false;
                    }
                    $name = mb_strtolower(trim((string) ($row['nombre'] ?? '')));
                    $doc = mb_strtolower(trim((string) ($row['identificacion'] ?? '')));

                    return str_contains($name, $needle) || str_contains($doc, $needle);
                }
            ));
        }
        $perPage = 10;
        $totalConflicts = count($allConflictRows);
        $totalPages = max(1, (int) ceil($totalConflicts / $perPage));
        $currentPage = (int) ($_GET['page'] ?? 1);
        $currentPage = min(max(1, $currentPage), $totalPages);
        $offset = ($currentPage - 1) * $perPage;
        $conflictPageItems = array_slice($allConflictRows, $offset, $perPage);

        $conflictQuery = ['id' => $id];
        if ($importCtx['fromImport']) {
            $conflictQuery['from'] = 'import';
        }
        if ($searchQ !== '') {
            $conflictQuery['q'] = $searchQ;
        }
        $conflictsBaseUrl = $base . '/importar/conflictos?'
            . http_build_query($conflictQuery)
            . '&page=%d';

        $aprendicesUrl = $base . '/aprendices';

        $conflictRowsAll = (array) ($resultado['conflict_rows'] ?? []);

        if ($this->isAjaxFilterRequest()) {
            partial('components/ui');
            $conflictsTdClasses = str_replace('h-[50px] ', '', ui_td_classes());
            $emptyMessage = $searchQ !== ''
                ? 'Sin coincidencias para esta búsqueda.'
                : 'No hay aprendices con conflictos pendientes.';
            ob_start();
            partial('import/_conflicts_rows', [
                'conflictRows' => $conflictPageItems,
                'tdClasses' => $conflictsTdClasses,
                'emptyMessage' => $emptyMessage,
            ]);
            $rowsHtml = (string) ob_get_clean();
            ob_start();
            if ($totalPages > 1) {
                ui_render_pagination(
                    $currentPage,
                    $totalPages,
                    $conflictsBaseUrl,
                    'Paginación de conflictos de importación',
                    'tabla-conflictos'
                );
            }
            $paginationHtml = (string) ob_get_clean();
            $this->jsonResponse([
                'ok' => true,
                'rowsHtml' => $rowsHtml,
                'paginationHtml' => $paginationHtml,
            ]);

            return;
        }

        view('import/conflictos', [
            'entry' => $entry,
            'conflictRows' => $conflictPageItems,
            'conflictRowsAll' => $conflictRowsAll,
            'conflictsCount' => count($conflictRowsAll),
            'conflictsFilteredCount' => $totalConflicts,
            'searchQ' => $searchQ,
            'currentPage' => $currentPage,
            'totalPages' => $totalPages,
            'conflictsPaginationUrl' => $conflictsBaseUrl,
            'pendingFieldLabels' => (array) require base_path('config/import_field_labels.php'),
            'importReturnUrl' => $importCtx['returnUrl'],
            'aprendicesUrl' => $aprendicesUrl,
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
            'nombre_instructor_seguimiento',
            'telefono_instructor_seguimiento',
            'tipo_asistencia',
            'sugerencias_comentarios',
            'jefe_grupo',
            'coordinacion',
            'jefe_id',
            'correo_organizacional',
            'jefe_nombre',
            'jefe_cargo',
            'jefe_correo',
            'jefe_telefono',
        ];
        if (!in_array($field, $allowedFields, true)) {
            $this->jsonResponse(['ok' => false, 'message' => 'Campo no permitido.'], 422);
            return;
        }

        if ($action === 'current') {
            $this->jsonResponse(['ok' => true, 'updated' => false]);
            return;
        }

        $incomingJefeId = (int) ($decoded['incoming_jefe_id'] ?? 0);
        try {
            $updated = $this->applyConflictAcceptNew($aprendizId, $field, $newValue, $incomingJefeId);
        } catch (\Throwable $e) {
            $this->jsonResponse(['ok' => false, 'message' => 'No se pudo aplicar el cambio.'], 500);

            return;
        }

        $this->jsonResponse(['ok' => true, 'updated' => $updated]);
    }

    public function completeConflictAprendiz(): void
    {
        $raw = file_get_contents('php://input');
        $decoded = json_decode($raw ?: '', true);
        if (!is_array($decoded)) {
            $this->jsonResponse(['ok' => false, 'message' => 'Solicitud inválida.'], 422);

            return;
        }

        $importId = trim((string) ($decoded['import_id'] ?? ''));
        $aprendizId = (int) ($decoded['aprendiz_id'] ?? 0);

        if ($importId === '' || $aprendizId <= 0) {
            $this->jsonResponse(['ok' => false, 'message' => 'Parámetros inválidos.'], 422);

            return;
        }

        if (ImportHistory::findById($importId) === null) {
            $this->jsonResponse(['ok' => false, 'message' => 'Importación no encontrada.'], 404);

            return;
        }

        $conflictRow = ImportHistory::findConflictAprendizRow($importId, $aprendizId);
        $resolutions = (array) ($decoded['resolutions'] ?? []);
        if ($conflictRow !== null) {
            $this->alignAprendizAfterConflictResolution($aprendizId, $conflictRow, $resolutions);
        }

        $removed = ImportHistory::removeConflictAprendiz($importId, $aprendizId);
        $remaining = ImportHistory::countConflictAprendices($importId);
        $this->jsonResponse([
            'ok' => true,
            'removed' => $removed,
            'remaining' => $remaining,
        ]);
    }

    /**
     * Alinea el aprendiz con los datos del archivo tras resolver conflictos (evita que reaparezcan al reimportar).
     *
     * @param array<string, mixed> $conflictRow
     * @param list<array{field?: string, action?: string, value?: mixed}> $resolutions
     */
    private function alignAprendizAfterConflictResolution(int $aprendizId, array $conflictRow, array $resolutions): void
    {
        $incomingJefeId = (int) ($conflictRow['incoming_jefe_id'] ?? 0);

        $actionByField = [];
        foreach ($resolutions as $item) {
            if (!is_array($item)) {
                continue;
            }
            $field = trim((string) ($item['field'] ?? ''));
            if ($field === '') {
                continue;
            }
            $actionByField[$field] = trim((string) ($item['action'] ?? '')) === 'new' ? 'new' : 'current';
        }

        if ($incomingJefeId > 0) {
            $pdo = \App\Helpers\Database::connection();
            $checkJefe = $pdo->prepare(
                'SELECT id FROM empresa_jefes WHERE id = :id LIMIT 1'
            );
            $checkJefe->execute(['id' => $incomingJefeId]);
            if ($checkJefe->fetch() !== false) {
                $pdo->prepare('UPDATE aprendices SET jefe_id = :jefe_id, updated_at = NOW() WHERE id = :id')
                    ->execute(['jefe_id' => $incomingJefeId, 'id' => $aprendizId]);
            }
        }

        foreach ($resolutions as $item) {
            if (!is_array($item)) {
                continue;
            }
            $field = trim((string) ($item['field'] ?? ''));
            if ($field === '' || ($actionByField[$field] ?? '') !== 'new') {
                continue;
            }
            $this->applyConflictAcceptNew(
                $aprendizId,
                $field,
                $item['value'] ?? null,
                $incomingJefeId
            );
        }

        if (($actionByField['correo_organizacional'] ?? '') === 'new') {
            $fileCorreoOrg = trim((string) ($conflictRow['file_correo_org'] ?? ''));
            if ($fileCorreoOrg !== '') {
                $this->applyConflictAcceptNew($aprendizId, 'correo_organizacional', $fileCorreoOrg, $incomingJefeId);
            }
        }
    }

    private function applyConflictAcceptNew(int $aprendizId, string $field, mixed $newValue, int $incomingJefeId = 0): bool
    {
        $pdo = Database::connection();
        $stmt = $pdo->prepare('SELECT id, empresa_id, jefe_id FROM aprendices WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $aprendizId]);
        $aprendiz = $stmt->fetch();
        if (!$aprendiz) {
            return false;
        }

        $empresaId = (int) ($aprendiz['empresa_id'] ?? 0);
        $currentJefeId = (int) ($aprendiz['jefe_id'] ?? 0);

        if ($field === 'jefe_id') {
            if ($empresaId <= 0) {
                return false;
            }

            $newJefeId = (int) $this->normalizeConflictValue($field, $newValue);
            if ($newJefeId <= 0 && $incomingJefeId > 0) {
                $newJefeId = $incomingJefeId;
            }
            if ($newJefeId <= 0) {
                return false;
            }

            $check = $pdo->prepare(
                'SELECT id FROM empresa_jefes WHERE id = :id AND empresa_id = :empresa_id LIMIT 1'
            );
            $check->execute(['id' => $newJefeId, 'empresa_id' => $empresaId]);
            if ($check->fetch() === false) {
                return false;
            }

            $update = $pdo->prepare(
                'UPDATE aprendices SET jefe_id = :jefe_id, updated_at = NOW() WHERE id = :id'
            );
            $update->execute(['jefe_id' => $newJefeId, 'id' => $aprendizId]);

            return true;
        }

        if ($field === 'correo_organizacional') {
            if ($empresaId <= 0) {
                return false;
            }
            $correo = trim((string) $this->normalizeConflictValue($field, $newValue));
            $update = $pdo->prepare('UPDATE empresas SET correo_org = :correo, updated_at = NOW() WHERE id = :id');
            $update->execute(['correo' => $correo !== '' ? $correo : null, 'id' => $empresaId]);

            return $update->rowCount() > 0;
        }

        $jefeColumnMap = [
            'jefe_nombre' => 'nombre',
            'jefe_cargo' => 'cargo',
            'jefe_correo' => 'correo',
            'jefe_telefono' => 'telefono',
        ];
        if (isset($jefeColumnMap[$field])) {
            if ($empresaId <= 0) {
                return false;
            }

            $targetJefeId = $currentJefeId;
            if ($incomingJefeId > 0 && $incomingJefeId !== $currentJefeId) {
                $pdo->prepare('UPDATE aprendices SET jefe_id = :jefe_id, updated_at = NOW() WHERE id = :id')
                    ->execute(['jefe_id' => $incomingJefeId, 'id' => $aprendizId]);
                $targetJefeId = $incomingJefeId;
            } elseif ($targetJefeId <= 0 && $incomingJefeId > 0) {
                $pdo->prepare('UPDATE aprendices SET jefe_id = :jefe_id, updated_at = NOW() WHERE id = :id')
                    ->execute(['jefe_id' => $incomingJefeId, 'id' => $aprendizId]);
                $targetJefeId = $incomingJefeId;
            }

            if ($targetJefeId <= 0) {
                return false;
            }

            $value = trim((string) $this->normalizeConflictValue($field, $newValue));

            return EmpresaJefe::patchColumnForEmpresa($empresaId, $targetJefeId, $jefeColumnMap[$field], $value);
        }

        $sql = 'UPDATE aprendices SET ' . $field . ' = :value, updated_at = NOW() WHERE id = :id';
        $stmt = $pdo->prepare($sql);
        $normalizedValue = $this->normalizeConflictValue($field, $newValue);
        $stmt->execute([
            'value' => $normalizedValue,
            'id' => $aprendizId,
        ]);

        return $stmt->rowCount() > 0;
    }

    /**
     * @param list<string> $errors
     */
    private function respondImportValidationFailed(array $errors): void
    {
        $message = $errors[0] ?? 'El archivo no coincide con la plantilla.';
        if ($this->isAjaxRequest()) {
            $this->jsonResponse([
                'ok' => false,
                'message' => $message,
                'errors' => $errors,
            ], 422);
            return;
        }

        $historyState = $this->historyPaginationState();
        view('import/upload', [
            'history' => $historyState['items'],
            'historyPage' => $historyState['page'],
            'historyTotalPages' => $historyState['totalPages'],
            'templateUrl' => $this->templateUrl(),
            'templateAvailable' => $this->templateAvailable(),
            'flashError' => $message,
            'flashErrors' => $errors,
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

    private function normalizeConflictValue(string $field, mixed $value): mixed
    {
        if (in_array($field, ['programa_id', 'empresa_id', 'jefe_id'], true)) {
            if ($value === null || $value === '') {
                return null;
            }

            return (int) $value;
        }

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

    private function isAjaxFilterRequest(): bool
    {
        $isXmlHttpRequest = strtolower((string) ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '')) === 'xmlhttprequest';
        $isAjaxQueryFlag = ((string) ($_GET['ajax'] ?? '')) === '1';

        return $isXmlHttpRequest || $isAjaxQueryFlag;
    }
}
