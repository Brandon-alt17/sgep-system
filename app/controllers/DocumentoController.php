<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Exports\F023Generator;
use App\Helpers\Database;
use App\Models\Aprendiz;
use App\Models\AprendizInfoGeneral;
use App\Models\Empresa;
use App\Models\Programa;

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
        $info = $this->buildInfoData($aprendiz, $programa, $empresa, $guardada);

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
        redirect(APP_BASE_PATH . '/documentos/info?aprendiz_id=' . $aprendizId . '&saved=1');
    }

    private function buildInfoData(array $aprendiz, ?array $programa, ?array $empresa, array $guardada): array
    {
        $valor = static function (array $prioritario, string $k, mixed $fallback = ''): string {
            $actual = trim((string) ($prioritario[$k] ?? ''));
            if ($actual !== '') {
                return $actual;
            }
            return trim((string) $fallback);
        };

        return [
            'regional' => $valor($guardada, 'regional', 'Risaralda'),
            'centro_formacion' => $valor($guardada, 'centro_formacion', 'Diseño e Innovación Tecnológica Industrial'),
            'nivel_formativo' => $valor($guardada, 'nivel_formativo', (string) ($programa['nivel'] ?? '')),
            'programa_formacion' => $valor($guardada, 'programa_formacion', (string) ($programa['nombre'] ?? '')),
            'numero_grupo' => $valor($guardada, 'numero_grupo', (string) ($aprendiz['ficha'] ?? '')),
            'modalidad_formacion' => $valor($guardada, 'modalidad_formacion', (string) ($programa['modalidad'] ?? '')),
            'estrategia_formativa' => $valor($guardada, 'estrategia_formativa', 'Dual'),
            'fecha_fin_etapa_lectiva' => $valor($guardada, 'fecha_fin_etapa_lectiva', ''),
            'fecha_registro_sofiaplus' => $valor($guardada, 'fecha_registro_sofiaplus', ''),
            'asistencia_nombre' => $valor($guardada, 'asistencia_nombre', ''),
            'asistencia_tipo' => $valor($guardada, 'asistencia_tipo', (string) ($aprendiz['tipo_asistencia'] ?? '')),
            'asistencia_contacto' => $valor($guardada, 'asistencia_contacto', ''),
            // Datos de solo lectura/autollenado para la vista
            'nombre_completo' => trim((string) ($aprendiz['nombre_completo'] ?? '')),
            'tipo_documento' => trim((string) ($aprendiz['tipo_documento'] ?? '')),
            'numero_documento' => trim((string) ($aprendiz['numero_documento'] ?? '')),
            'telefono' => trim((string) ($aprendiz['telefono'] ?? '')),
            'direccion_domicilio' => trim((string) ($aprendiz['direccion_domicilio'] ?? '')),
            'correo_personal' => trim((string) ($aprendiz['correo_personal'] ?? '')),
            'correo_institucional' => trim((string) ($aprendiz['correo_institucional'] ?? '')),
            'alternativa_ep' => trim((string) ($aprendiz['alternativa_ep'] ?? '')),
            'nombre_instructor_seguimiento' => trim((string) ($aprendiz['nombre_instructor_seguimiento'] ?? '')),
            'telefono_instructor_seguimiento' => trim((string) ($aprendiz['telefono_instructor_seguimiento'] ?? '')),
            'correo_instructor_seguimiento' => trim((string) ($aprendiz['correo_instructor_seguimiento'] ?? '')),
            'empresa_nombre' => trim((string) ($empresa['nombre'] ?? ($aprendiz['empresa_nombre'] ?? ''))),
            'empresa_direccion' => trim((string) ($empresa['direccion'] ?? ($aprendiz['direccion'] ?? ''))),
            'empresa_nit' => trim((string) ($empresa['nit'] ?? ($aprendiz['nit'] ?? ''))),
            'empresa_correo' => trim((string) ($empresa['correo_org'] ?? '')),
            'jefe_nombre' => trim((string) ($aprendiz['nombre_jefe'] ?? '')),
            'jefe_cargo' => trim((string) ($aprendiz['cargo_jefe'] ?? '')),
            'jefe_telefono' => trim((string) ($aprendiz['telefono_jefe'] ?? '')),
            'jefe_correo' => trim((string) ($aprendiz['correo_jefe'] ?? '')),
            'contacto2_nombre' => trim((string) ($aprendiz['nombre_contacto2_jefe'] ?? ($empresa['nombre_contacto2'] ?? ''))),
            'contacto2_correo' => trim((string) ($aprendiz['correo_contacto2_jefe'] ?? ($empresa['correo_contacto2'] ?? ''))),
        ];
    }
}
