<?php

declare(strict_types=1);

namespace App\Exports;

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
                    $path = F023InfoTemplateMacroInjector::patchToTemp($path);
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
                $tempCleanup[] = $tmpDocx;
                $mergeInputs[] = $tmpDocx;
            }

            $out = base_path('storage/documents/F023_' . $aprendizId . '_' . time() . '.docx');
            $dir = dirname($out);
            if (!is_dir($dir)) {
                mkdir($dir, 0775, true);
            }

            F023DocxMerge::mergeInto($out, $mergeInputs);

            if ($formato === 'pdf') {
                try {
                    $pdfPath = F023DocxToPdf::convert($out);
                } catch (\Throwable $e) {
                    if (is_file($out)) {
                        @unlink($out);
                    }
                    throw $e;
                }
                if (is_file($out)) {
                    @unlink($out);
                }

                return $pdfPath;
            }

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
        $vars = array_merge(
            $infoForTpl,
            ['nombre_aprendiz' => (string) ($aprendiz['nombre_completo'] ?? '')],
            $this->momentoRowTemplateVars($momento),
            $this->factorTemplateVars(Momento::factoresByMomento((int) $momento['id']))
        );

        $tplDir = base_path('storage/templates/');
        if ($tipo === 'M1') {
            return [['path' => $tplDir . ($map['m1_template'] ?? 'm1.docx'), 'vars' => $vars]];
        }
        if ($tipo === 'M2' || $tipo === 'EX') {
            $file = $tipo === 'EX' ? ($map['ex_template'] ?? 'm2.docx') : ($map['m2_template'] ?? 'm2.docx');

            return [['path' => $tplDir . $file, 'vars' => $vars]];
        }
        if ($tipo === 'M3') {
            $out = [];
            foreach ($map['m3_templates'] ?? ['m3_p1.docx', 'm3_p2.docx'] as $rel) {
                $out[] = ['path' => $tplDir . $rel, 'vars' => $vars];
            }

            return $out;
        }

        return [];
    }

    /**
     * @param array<string,mixed> $momento
     * @return array<string,string>
     */
    private function momentoRowTemplateVars(array $momento): array
    {
        $dateCols = [
            'fecha_visita',
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
            } else {
                $out[$k] = $s;
            }
        }

        return $out;
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
            $out['factor_' . $i . '_valoracion'] = $r ? trim((string) ($r['valoracion'] ?? '')) : '';
            $out['factor_' . $i . '_observacion'] = $r ? trim((string) ($r['observacion'] ?? '')) : '';
            $i++;
        }
        foreach ($cfg['actitudinales'] as $nombre) {
            $r = $byNombre[$nombre] ?? null;
            $out['factor_' . $i . '_valoracion'] = $r ? trim((string) ($r['valoracion'] ?? '')) : '';
            $out['factor_' . $i . '_observacion'] = $r ? trim((string) ($r['observacion'] ?? '')) : '';
            $i++;
        }

        return $out;
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
