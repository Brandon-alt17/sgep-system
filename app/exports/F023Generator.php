<?php

declare(strict_types=1);

namespace App\Exports;

use App\Helpers\Normalizer;
use App\Models\Aprendiz;
use App\Models\AprendizInfoGeneral;
use App\Models\Empresa;
use App\Models\Momento;
use App\Models\Programa;
use App\Services\F023InfoData;
use PhpOffice\PhpWord\TemplateProcessor;

class F023Generator
{
    /**
     * @param list<string> $partes tokens en orden: 'info', 'momento:{id}' y/o 'momento_tipo:M1|M2|M3' (vacío si no hay fila)
     */
    public function generate(int $aprendizId, array $partes, string $formato): string
    {
        $formato = strtolower(trim($formato));
        if (!in_array($formato, ['docx', 'pdf'], true)) {
            $formato = 'docx';
        }

        $aprendiz = $this->fetchAprendiz($aprendizId);
        if ($aprendiz === null) {
            throw new \RuntimeException('Aprendiz no encontrado.');
        }

        $map = require base_path('config/f023_template_map.php');
        $programa = $this->fetchPrograma((int) ($aprendiz['programa_id'] ?? 0));
        $empresa = $this->fetchEmpresa((int) ($aprendiz['empresa_id'] ?? 0));
        $guardada = AprendizInfoGeneral::findByAprendizId($aprendizId) ?? [];
        $info = F023InfoData::build($aprendiz, $programa, $empresa, $guardada);
        $infoForTpl = $this->infoArrayForTemplate($info, $map['info_placeholders'] ?? []);
        $infoForTpl['nombre_aprendiz'] = (string) ($info['nombre_completo'] ?? '');

        $segments = [];
        foreach ($partes as $token) {
            $token = (string) $token;
            if ($token === 'info') {
                $infoPath = base_path('storage/templates/' . ($map['info_template'] ?? 'info.docx'));
                $segments[] = [
                    'path' => $infoPath,
                    'vars' => $infoForTpl,
                    'patch_info_macros' => true,
                ];
                continue;
            }
            if (preg_match('/^momento:(\d+)$/', $token, $m)) {
                $mid = (int) $m[1];
                $momento = Momento::findById($mid);
                if ($momento === null || (int) $momento['aprendiz_id'] !== $aprendizId) {
                    continue;
                }
                foreach ($this->momentoTemplateSegments($momento, $map, $infoForTpl, $aprendiz) as $seg) {
                    $segments[] = $seg;
                }
                continue;
            }
            if (preg_match('/^momento_tipo:(M1|M2|M3)$/', $token, $m)) {
                $synthetic = [
                    'id' => 0,
                    'aprendiz_id' => $aprendizId,
                    'tipo' => (string) $m[1],
                ];
                foreach ($this->momentoTemplateSegments($synthetic, $map, $infoForTpl, $aprendiz) as $seg) {
                    $segments[] = $seg;
                }
            }
        }

        if ($segments === []) {
            throw new \RuntimeException('No hay segmentos válidos para exportar.');
        }

        $mergeInputs = [];
        $tempCleanup = [];
        try {
            foreach ($segments as $seg) {
                $path = (string) $seg['path'];
                if (!empty($seg['patch_info_macros'])) {
                    $preserveInfoHeader = ($formato === 'docx');
                    $path = F023InfoTemplateMacroInjector::patchToTemp($path, $preserveInfoHeader);
                    $tempCleanup[] = $path;
                }
                if (!empty($seg['patch_m1_macros'])) {
                    $path = F023M1TemplateMacroInjector::patchToTemp($path);
                    $tempCleanup[] = $path;
                }
                if (!empty($seg['patch_m2_macros'])) {
                    $path = F023M2TemplateMacroInjector::patchToTemp($path);
                    $tempCleanup[] = $path;
                }
                if (!empty($seg['patch_ex_macros'])) {
                    $path = F023ExTemplateMacroInjector::patchToTemp($path);
                    $tempCleanup[] = $path;
                }
                if (!empty($seg['patch_m3_macros'])) {
                    $path = F023M3TemplateMacroInjector::patchToTemp($path);
                    $tempCleanup[] = $path;
                }
                if (!is_file($path)) {
                    throw new \RuntimeException('Plantilla no encontrada: ' . basename($path));
                }
                $tpl = new TemplateProcessor($path);
                foreach ($seg['vars'] as $name => $value) {
                    $tpl->setValue((string) $name, (string) $value);
                }
                $tmp = tempnam(sys_get_temp_dir(), 'f023seg_');
                if ($tmp === false) {
                    throw new \RuntimeException('No se pudo crear archivo temporal.');
                }
                $tmpDocx = $tmp . '.docx';
                if (!rename($tmp, $tmpDocx)) {
                    @unlink($tmp);
                    throw new \RuntimeException('No se pudo preparar segmento .docx.');
                }
                $tpl->saveAs($tmpDocx);
                if (!empty($seg['patch_info_macros'])) {
                    F023InfoTemplateMacroInjector::applyLayoutToSavedDocx($tmpDocx, $formato === 'docx');
                }
                if (!empty($seg['patch_m1_macros'])) {
                    F023M1TemplateMacroInjector::applyLayoutToSavedDocx($tmpDocx);
                }
                if (!empty($seg['patch_m2_macros'])) {
                    F023M2TemplateMacroInjector::applyLayoutToSavedDocx($tmpDocx);
                }
                if (!empty($seg['patch_ex_macros'])) {
                    F023ExTemplateMacroInjector::applyLayoutToSavedDocx($tmpDocx);
                }
                if (!empty($seg['patch_m3_macros']) && str_contains(basename($path), 'm3_p2')) {
                    F023M3TemplateMacroInjector::applyJuicioMarcasToSavedDocx($tmpDocx, [
                        'm3_juicio_marca_aprobado' => (string) ($seg['vars']['m3_juicio_marca_aprobado'] ?? ''),
                        'm3_juicio_marca_no_aprobado' => (string) ($seg['vars']['m3_juicio_marca_no_aprobado'] ?? ''),
                    ]);
                }
                $tempCleanup[] = $tmpDocx;
                $mergeInputs[] = $tmpDocx;
            }

            $dir = base_path('storage/documents');
            if (!is_dir($dir)) {
                mkdir($dir, 0775, true);
            }

            if ($formato === 'pdf') {
                $pdfBasename = f023_export_basename(
                    (string) ($info['nombre_completo'] ?? ''),
                    (string) ($info['numero_grupo'] ?? ''),
                    'pdf'
                );

                $pdfOut = $this->exportPdfFromSegments(
                    $mergeInputs,
                    resolve_unique_storage_path($dir, $pdfBasename)
                );
                return $pdfOut;
            }

            $out = resolve_unique_storage_path(
                $dir,
                f023_export_basename(
                    (string) ($info['nombre_completo'] ?? ''),
                    (string) ($info['numero_grupo'] ?? ''),
                    'docx'
                )
            );

            F023DocxMerge::mergeInto(
                $out,
                $mergeInputs,
                false,
                $this->m3SegmentMask($segments)
            );

            return $out;
        } finally {
            foreach ($tempCleanup as $f) {
                if (is_file($f)) {
                    @unlink($f);
                }
            }
        }
    }

    /**
     * @param array<string,string> $info
     * @param list<string> $keys
     * @return array<string,string>
     */
    private function infoArrayForTemplate(array $info, array $keys): array
    {
        $dateKeys = ['fecha_fin_etapa_lectiva', 'fecha_registro_sofiaplus'];
        $out = [];
        foreach ($keys as $key) {
            $raw = (string) ($info[$key] ?? '');
            if (in_array($key, $dateKeys, true)) {
                $out[$key] = $raw === '' ? '' : date_iso_to_dmY($raw);
            } else {
                $out[$key] = $raw;
            }
        }

        return $out;
    }

    /**
     * @param array<string,mixed> $momento
     * @param array<string,mixed> $map
     * @param array<string,string> $infoForTpl
     * @param array<string,mixed> $aprendiz
     * @return list<array{path: string, vars: array<string,string>}>
     */
    private function momentoTemplateSegments(array $momento, array $map, array $infoForTpl, array $aprendiz): array
    {
        $tipo = (string) ($momento['tipo'] ?? '');
        if ($tipo === 'M3') {
            $momento = F023M3RetroSupport::fillFromObservacionesIfEmpty($momento);
        }
        $vars = array_merge(
            $infoForTpl,
            ['nombre_aprendiz' => (string) ($aprendiz['nombre_completo'] ?? '')],
            [
                'nombre_instructor_seguimiento' => trim((string) ($aprendiz['nombre_instructor_seguimiento'] ?? '')),
                'nombre_coformador' => trim((string) ($aprendiz['nombre_jefe'] ?? '')),
            ],
            $this->momentoRowTemplateVars($momento),
            $this->factorTemplateVars(Momento::factoresByMomento((int) $momento['id'])),
            $this->diligenciamientoMarcasTemplateVars($momento),
            $this->juicioMarcasTemplateVars($momento)
        );

        $tplDir = base_path('storage/templates/');
        if ($tipo === 'M1') {
            return [[
                'path' => $tplDir . ($map['m1_template'] ?? 'm1.docx'),
                'vars' => $vars,
                'patch_m1_macros' => true,
            ]];
        }
        if ($tipo === 'EX') {
            $vars = array_merge($this->exPlaceholderDefaults(), $vars);
            if (($vars['numero_visita'] ?? '') === '' && !empty($momento['numero_visita'])) {
                $vars['numero_visita'] = (string) (int) $momento['numero_visita'];
            }

            return [[
                'path' => $tplDir . ($map['ex_template'] ?? 'extra.docx'),
                'vars' => $vars,
                'patch_ex_macros' => true,
            ]];
        }
        if ($tipo === 'M2') {
            $vars = array_merge($this->m2PlaceholderDefaults(), $vars);

            return [['path' => $tplDir . ($map['m2_template'] ?? 'm2.docx'), 'vars' => $vars, 'patch_m2_macros' => true]];
        }
        if ($tipo === 'M3') {
            $vars = array_merge($this->m3PlaceholderDefaults(), $vars);
            $out = [];
            foreach ($map['m3_templates'] ?? ['m3_p1.docx', 'm3_p2.docx'] as $rel) {
                $out[] = [
                    'path' => $tplDir . $rel,
                    'vars' => $vars,
                    'patch_m3_macros' => true,
                ];
            }

            return $out;
        }

        return [];
    }

    /**
     * Marcadores M2/EX con valor vacío si no hay dato en BD.
     *
     * @return array<string, string>
     */
    private function m2PlaceholderDefaults(): array
    {
        return [
            'fecha_inicio_etapa' => '',
            'fecha_visita' => '',
            'modalidad' => '',
            'enlace_grabacion' => '',
            'nombre_aprendiz' => '',
            'nombre_instructor_seguimiento' => '',
            'ciudad_diligenciamiento' => '',
            'fecha_diligenciamiento' => date('d/m/Y'),
            'm2_marca_presencial' => '___',
            'm2_marca_virtual' => '___',
        ];
    }

    /**
     * Marcadores EX (extra.docx) con valor vacío si no hay dato en BD.
     *
     * @return array<string, string>
     */
    private function exPlaceholderDefaults(): array
    {
        return [
            'numero_visita' => '',
            'fecha_seguimiento_anterior' => '',
            'fecha_visita' => '',
            'modalidad' => '',
            'enlace_grabacion' => '',
            'motivo_seguimiento_extraordinario' => '',
            'nombre_aprendiz' => '',
            'nombre_instructor_seguimiento' => '',
            'nombre_coformador' => '',
            'ciudad_diligenciamiento' => '',
            'fecha_diligenciamiento' => date('d/m/Y'),
            'ex_marca_presencial' => '___',
            'ex_marca_virtual' => '___',
        ];
    }

    /**
     * Marcadores M3 (p1 + p2) con valor vacío si no hay fila en BD o el momento sintético no trae datos.
     *
     * @return array<string, string>
     */
    private function m3PlaceholderDefaults(): array
    {
        $out = [
            'fecha_inicio_etapa' => '',
            'fecha_fin_etapa' => '',
            'numero_visitas_realizadas' => '',
            'modalidad' => '',
            'enlace_grabacion' => '',
            'nombre_aprendiz' => '',
            'nombre_instructor_seguimiento' => '',
            'ciudad_diligenciamiento' => '',
            'fecha_diligenciamiento' => date('d/m/Y'),
            'm3_marca_presencial' => '___',
            'm3_marca_virtual' => '___',
            'm3_juicio_marca_aprobado' => '',
            'm3_juicio_marca_no_aprobado' => '',
            'm3_retro_instructor_proceso' => '',
            'm3_retro_instructor_desempeno' => '',
            'm3_retro_aprendiz_proceso' => '',
            'm3_retro_aprendiz_desempeno' => '',
            'm3_retro_coformador_proceso' => '',
            'm3_retro_coformador_desempeno' => '',
            'juicio_final' => '',
        ];

        return array_merge($out, $this->factorTemplateVars([]));
    }

    /**
     * @param list<array<string,mixed>> $segments
     */
    private function segmentsIncludeM3(array $segments): bool
    {
        foreach ($segments as $seg) {
            $base = basename((string) ($seg['path'] ?? ''));
            if (preg_match('/^m3_p[12]\.docx$/', $base)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param list<array<string,mixed>> $segments
     * @return list<bool>
     */
    private function m3SegmentMask(array $segments): array
    {
        $mask = [];
        foreach ($segments as $seg) {
            $base = basename((string) ($seg['path'] ?? ''));
            $mask[] = (bool) preg_match('/^m3_p[12]\.docx$/', $base);
        }

        return $mask;
    }

    /**
     * @param array<string,mixed> $momento
     * @return array<string,string>
     */
    private function momentoRowTemplateVars(array $momento): array
    {
        $dateCols = [
            'fecha_visita',
            'fecha_seguimiento_anterior',
            'fecha_inicio_etapa',
            'fecha_fin_etapa',
            'fecha_arl',
            'fecha_diligenciamiento',
        ];
        $skip = ['id', 'aprendiz_id', 'created_at', 'updated_at'];
        $out = [];
        foreach ($momento as $k => $v) {
            if (!is_string($k) || in_array($k, $skip, true)) {
                continue;
            }
            $s = $v === null ? '' : (is_scalar($v) ? (string) $v : '');
            $s = trim($s);
            if (in_array($k, $dateCols, true)) {
                $out[$k] = $s === '' ? '' : date_iso_to_dmY($s);
            } elseif ($k === 'm1_competencias' || $k === 'm1_resultados') {
                $out[$k] = Normalizer::normalizeCommaListSentenceCase($s);
            } else {
                $out[$k] = $s;
            }
        }

        if (in_array($out['tipo'] ?? '', ['M1', 'M2', 'M3', 'EX'], true)) {
            if (($out['fecha_diligenciamiento'] ?? '') === '') {
                $out['fecha_diligenciamiento'] = date('d/m/Y');
            }

            if (($out['modalidad_diligenciamiento'] ?? '') === '') {
                $fallbackModalidad = trim((string) ($momento['modalidad'] ?? ''));
                if ($fallbackModalidad !== '') {
                    $out['modalidad_diligenciamiento'] = $fallbackModalidad;
                }
            }

            if (trim($out['modalidad'] ?? '') === '') {
                $fallbackSeguimiento = trim($out['modalidad_diligenciamiento'] ?? '');
                if ($fallbackSeguimiento !== '') {
                    $out['modalidad'] = $fallbackSeguimiento;
                }
            }

            if (($out['ciudad_diligenciamiento'] ?? '') === '') {
                $fallbackCiudad = trim((string) ($momento['ciudad'] ?? ''));
                if ($fallbackCiudad !== '') {
                    $out['ciudad_diligenciamiento'] = $fallbackCiudad;
                }
            }

            if (($out['tipo'] ?? '') === 'M3' && trim($out['fecha_fin_etapa'] ?? '') === '') {
                $out['fecha_fin_etapa'] = trim($out['fecha_visita'] ?? '');
            }
        }

        if (($out['tipo'] ?? '') === 'EX') {
            return F023ObservationLines::expandTemplateVars($out, [
                'obs_instructor',
                'obs_aprendiz',
                'obs_coformador',
            ], 1);
        }

        return F023ObservationLines::expandTemplateVars($out, [
            'obs_instructor',
            'obs_aprendiz',
            'obs_coformador',
            'm1_observaciones_adicionales',
        ]);
    }

    /**
     * Marcas X / ___ de modalidad en el pie de diligenciamiento (M1, M2, M3 y EX).
     *
     * @param array<string,mixed> $momento
     * @return array<string,string>
     */
    private function diligenciamientoMarcasTemplateVars(array $momento): array
    {
        $tipo = (string) ($momento['tipo'] ?? '');
        if (!in_array($tipo, ['M1', 'M2', 'M3', 'EX'], true)) {
            return [];
        }

        $prefix = match ($tipo) {
            'EX' => 'ex',
            default => strtolower($tipo),
        };
        $modalidad = trim((string) ($momento['modalidad_diligenciamiento'] ?? ''));
        if ($modalidad === '') {
            $modalidad = trim((string) ($momento['modalidad'] ?? ''));
        }

        $isPresencial = strcasecmp($modalidad, 'Presencial') === 0;
        $isVirtual = strcasecmp($modalidad, 'Virtual') === 0;

        return [
            $prefix . '_marca_presencial' => $isPresencial ? 'X' : '___',
            $prefix . '_marca_virtual' => $isVirtual ? 'X' : '___',
        ];
    }

    /**
     * Marcas X junto a Aprobado / No aprobado en M3 p2.
     *
     * @param array<string,mixed> $momento
     * @return array<string,string>
     */
    private function juicioMarcasTemplateVars(array $momento): array
    {
        if ((string) ($momento['tipo'] ?? '') !== 'M3') {
            return [];
        }

        $juicio = trim((string) ($momento['juicio_final'] ?? ''));
        $isAprobado = strcasecmp($juicio, 'Aprobado') === 0;
        $isNoAprobado = strcasecmp($juicio, 'No aprobado') === 0;

        return [
            'm3_juicio_marca_aprobado' => $isAprobado ? 'X' : '',
            'm3_juicio_marca_no_aprobado' => $isNoAprobado ? 'X' : '',
        ];
    }

    /**
     * @param list<array<string,mixed>> $factoresRows
     * @return array<string,string>
     */
    private function factorTemplateVars(array $factoresRows): array
    {
        $cfg = require base_path('config/factores.php');
        $byNombre = [];
        foreach ($factoresRows as $r) {
            $n = trim((string) ($r['nombre_factor'] ?? ''));
            if ($n !== '') {
                $byNombre[$n] = $r;
            }
        }
        $out = [];
        $i = 0;
        foreach ($cfg['tecnicos'] as $nombre) {
            $r = $byNombre[$nombre] ?? null;
            $out = array_merge($out, self::factorValoracionMarks($i, $r));
            $i++;
        }
        foreach ($cfg['actitudinales'] as $nombre) {
            $r = $byNombre[$nombre] ?? null;
            $out = array_merge($out, self::factorValoracionMarks($i, $r));
            $i++;
        }

        return $out;
    }

    /**
     * @param array<string,mixed>|null $factorRow
     * @return array<string,string>
     */
    private function factorValoracionMarks(int $index, ?array $factorRow): array
    {
        $valoracion = $factorRow ? strtoupper(trim((string) ($factorRow['valoracion'] ?? ''))) : '';
        $isSatisfactorio = $valoracion === 'S';
        $isPorMejorar = $valoracion === 'PM';

        return [
            'factor_' . $index . '_valoracion_s' => $isSatisfactorio ? 'X' : '',
            'factor_' . $index . '_valoracion_pm' => $isPorMejorar ? 'X' : '',
            ...self::factorObservacionTemplateVars($index, $factorRow),
        ];
    }

    /**
     * @param array<string,mixed>|null $factorRow
     * @return array<string, string>
     */
    private static function factorObservacionTemplateVars(int $index, ?array $factorRow): array
    {
        $obs = $factorRow ? trim((string) ($factorRow['observacion'] ?? '')) : '';
        [$line1, $line2] = F023ObservationLines::split($obs);

        return [
            'factor_' . $index . '_observacion' => F023ObservationLines::combineTwoLines($line1, $line2),
        ];
    }

    /**
     * PDF por segmento: cada plantilla se convierte sola y luego se unen los PDF.
     * LibreOffice deforma tablas cuando el .docx fue armado por concatenación XML.
     *
     * @param list<string> $segmentDocxPaths
     *
     * @throws \RuntimeException
     */
    private function exportPdfFromSegments(array $segmentDocxPaths, string $out): string
    {
        $pdfParts = [];
        try {
            foreach ($segmentDocxPaths as $docxPath) {
                $pdfParts[] = F023DocxToPdf::convert($docxPath);
            }

            F023PdfMerge::merge($pdfParts, $out);

            return $out;
        } finally {
            foreach ($pdfParts as $part) {
                if (is_file($part)) {
                    @unlink($part);
                }
            }
        }
    }

    /** @return array<string,mixed>|null */
    private function fetchAprendiz(int $aprendizId): ?array
    {
        return Aprendiz::findById($aprendizId);
    }

    /** @return array<string,mixed>|null */
    private function fetchPrograma(int $id): ?array
    {
        if ($id <= 0) {
            return null;
        }

        return Programa::findById($id);
    }

    /** @return array<string,mixed>|null */
    private function fetchEmpresa(int $id): ?array
    {
        if ($id <= 0) {
            return null;
        }

        return Empresa::findById($id);
    }
}
