<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Exports\F023DocxToPdf;
use App\Exports\F023Generator;
use App\Helpers\Database;
use App\Models\Aprendiz;
use App\Models\AprendizInfoGeneral;
use App\Models\Empresa;
use App\Models\Momento;
use App\Models\Programa;
use App\Services\F023InfoData;

class DocumentoController
{
    public function create(): void
    {
        $aprendizId = (int) ($_GET['aprendiz_id'] ?? 0);
        if ($aprendizId <= 0) {
            redirect(APP_BASE_PATH . '/aprendices');

            return;
        }

        $aprendiz = Aprendiz::findById($aprendizId);
        if ($aprendiz === null) {
            http_response_code(404);
            view('errors/404', ['uri' => '/documentos/generar?aprendiz_id=' . $aprendizId]);

            return;
        }

        $programa = Programa::findById((int) ($aprendiz['programa_id'] ?? 0));
        $empresa = Empresa::findById((int) ($aprendiz['empresa_id'] ?? 0));
        $guardada = AprendizInfoGeneral::findByAprendizId($aprendizId) ?? [];
        $info = F023InfoData::build($aprendiz, $programa, $empresa, $guardada);

        $faltantes = 0;
        foreach (F023InfoData::requiredKeysForCompleteness() as $k) {
            if (trim((string) ($info[$k] ?? '')) === '') {
                $faltantes++;
            }
        }

        $exportMomentos = Momento::buildExportWizardRows($aprendizId);

        view('documents/generate', [
            'aprendiz' => $aprendiz,
            'aprendiz_id' => $aprendizId,
            'info' => $info,
            'export_momentos' => $exportMomentos,
            'info_faltantes_count' => $faltantes,
            'error' => (string) ($_GET['error'] ?? ''),
            'pdf_available' => F023DocxToPdf::isAvailable(),
            'pdf_availability_message' => F023DocxToPdf::availabilityMessage(),
        ]);
    }

    public function generate(): void
    {
        $aprendizId = (int) ($_POST['aprendiz_id'] ?? 0);
        $formato = strtolower(trim((string) ($_POST['formato'] ?? 'docx')));
        if (!in_array($formato, ['docx', 'pdf'], true)) {
            $formato = 'docx';
        }

        if ($aprendizId <= 0) {
            redirect(APP_BASE_PATH . '/aprendices');

            return;
        }

        $aprendiz = Aprendiz::findById($aprendizId);
        if ($aprendiz === null) {
            http_response_code(404);
            view('errors/404', ['uri' => '/documentos/generar?aprendiz_id=' . $aprendizId]);

            return;
        }

        $rawPartes = $_POST['partes'] ?? [];
        if (!is_array($rawPartes)) {
            $rawPartes = [];
        }
        $partes = $this->validateExportPartes($aprendizId, $rawPartes);
        if ($partes === []) {
            redirect(APP_BASE_PATH . '/documentos/generar?aprendiz_id=' . $aprendizId . '&error=sin_partes');

            return;
        }

        try {
            $path = (new F023Generator())->generate($aprendizId, $partes, $formato);
        } catch (\Throwable $e) {
            log_error('F023 export: ' . $e->getMessage());
            $errorKey = 'export_failed';
            if ($formato === 'pdf') {
                if (!F023DocxToPdf::isAvailable()) {
                    $errorKey = 'pdf_libreoffice';
                } elseif (str_contains($e->getMessage(), 'LibreOffice')) {
                    $errorKey = 'pdf_convert_failed';
                }
            }
            redirect(APP_BASE_PATH . '/documentos/generar?aprendiz_id=' . $aprendizId . '&error=' . $errorKey);

            return;
        }

        if (!is_file($path)) {
            redirect(APP_BASE_PATH . '/documentos/generar?aprendiz_id=' . $aprendizId . '&error=export_failed');

            return;
        }

        try {
            Database::connection()->prepare(
                'INSERT INTO documentos_generados (aprendiz_id, partes, formato, ruta_archivo, created_at) VALUES (:aprendiz_id, :partes, :formato, :ruta, NOW())'
            )->execute([
                'aprendiz_id' => $aprendizId,
                'partes' => json_encode($partes, JSON_UNESCAPED_UNICODE),
                'formato' => $formato,
                'ruta' => $path,
            ]);
        } catch (\Throwable $e) {
            log_error('F023 historial: ' . $e->getMessage());
            // El archivo ya se generó; se entrega aunque falle el registro en historial.
        }

        $mime = $formato === 'pdf'
            ? 'application/pdf'
            : 'application/vnd.openxmlformats-officedocument.wordprocessingml.document';
        header('Content-Type: ' . $mime);
        header('Content-Disposition: ' . content_disposition_attachment(basename($path)));
        readfile($path);
        exit;
    }

    public function info(): void
    {
        $aprendizId = (int) ($_GET['aprendiz_id'] ?? 0);
        $aprendiz = Aprendiz::findById($aprendizId);

        if ($aprendiz === null) {
            http_response_code(404);
            view('errors/404', ['uri' => '/documentos/info?aprendiz_id=' . $aprendizId]);

            return;
        }

        $programa = Programa::findById((int) ($aprendiz['programa_id'] ?? 0));
        $empresa = Empresa::findById((int) ($aprendiz['empresa_id'] ?? 0));
        $guardada = AprendizInfoGeneral::findByAprendizId($aprendizId) ?? [];
        $info = F023InfoData::build($aprendiz, $programa, $empresa, $guardada);

        view('documentos/info', [
            'aprendiz' => $aprendiz,
            'info' => $info,
            'saved' => ((string) ($_GET['saved'] ?? '')) === '1',
        ]);
    }

    public function saveInfo(): void
    {
        $aprendizId = (int) ($_POST['aprendiz_id'] ?? 0);
        $aprendiz = Aprendiz::findById($aprendizId);
        if ($aprendiz === null) {
            http_response_code(404);
            view('errors/404', ['uri' => '/documentos/info?aprendiz_id=' . $aprendizId]);

            return;
        }

        AprendizInfoGeneral::upsertByAprendizId($aprendizId, $_POST);
        // El formulario F-023 también puede editar algunos campos del aprendiz (ej. instructor de seguimiento).
        // Esos campos viven en la tabla `aprendices`, por eso se persisten aquí sin tocar el resto del payload.
        $nombreInstructor = trim((string) ($_POST['nombre_instructor_seguimiento'] ?? ''));
        $telefonoInstructor = trim((string) ($_POST['telefono_instructor_seguimiento'] ?? ''));
        $correoInstructor = trim((string) ($_POST['correo_instructor_seguimiento'] ?? ''));
        $tipoAsistencia = AprendizInfoGeneral::tipoAsistenciaAprendizFromPost($_POST);

        Database::connection()->prepare(
            'UPDATE aprendices
             SET
                nombre_instructor_seguimiento = :nombre_instructor_seguimiento,
                telefono_instructor_seguimiento = :telefono_instructor_seguimiento,
                correo_instructor_seguimiento = :correo_instructor_seguimiento,
                tipo_asistencia = :tipo_asistencia,
                updated_at = NOW()
             WHERE id = :id'
        )->execute([
            'id' => $aprendizId,
            'nombre_instructor_seguimiento' => $nombreInstructor === '' ? null : $nombreInstructor,
            'telefono_instructor_seguimiento' => $telefonoInstructor === '' ? null : $telefonoInstructor,
            'correo_instructor_seguimiento' => $correoInstructor === '' ? null : $correoInstructor,
            'tipo_asistencia' => $tipoAsistencia,
        ]);

        redirect(APP_BASE_PATH . '/aprendices/show?id=' . $aprendizId . '&toast=info_f023_guardada');
    }

    /**
     * @param list<mixed> $raw
     * @return list<string>
     */
    private function validateExportPartes(int $aprendizId, array $raw): array
    {
        $ordered = [];
        foreach ($raw as $token) {
            $t = trim((string) $token);
            if ($t !== '') {
                $ordered[] = $t;
            }
        }

        $selectedIds = [];
        foreach ($ordered as $token) {
            if (!preg_match('/^momento:(\d+)$/', $token, $m)) {
                continue;
            }
            $mid = (int) $m[1];
            $row = Momento::findById($mid);
            if ($row !== null && (int) ($row['aprendiz_id'] ?? 0) === $aprendizId) {
                $selectedIds[$mid] = (string) ($row['tipo'] ?? '');
            }
        }

        $tiposWithRealSelected = [];
        foreach ($selectedIds as $tipo) {
            if (in_array($tipo, ['M1', 'M2', 'M3', 'EX'], true)) {
                $tiposWithRealSelected[$tipo] = true;
            }
        }

        $seen = [];
        $out = [];
        foreach ($ordered as $token) {
            if ($token === 'info') {
                if (!isset($seen['info'])) {
                    $seen['info'] = true;
                    $out[] = 'info';
                }

                continue;
            }
            if (preg_match('/^momento:(\d+)$/', $token, $m)) {
                $mid = (int) $m[1];
                $key = 'momento:' . $mid;
                if (isset($seen[$key])) {
                    continue;
                }
                if (!isset($selectedIds[$mid])) {
                    continue;
                }
                $seen[$key] = true;
                $out[] = $key;

                continue;
            }
            if (preg_match('/^momento_tipo:(M1|M2|M3)$/', $token, $m)) {
                $tipo = (string) $m[1];
                if (isset($tiposWithRealSelected[$tipo])) {
                    continue;
                }
                if (Momento::existsTipo($aprendizId, $tipo)) {
                    continue;
                }
                $key = 'momento_tipo:' . $tipo;
                if (isset($seen[$key])) {
                    continue;
                }
                $seen[$key] = true;
                $out[] = $key;
            }
        }

        return $out;
    }
}
