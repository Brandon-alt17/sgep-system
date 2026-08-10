<?php

declare(strict_types=1);

/**
 * Calibración empírica del piso de tamaño de letra (font_scale_floor_half_points) para la
 * reducción progresiva de fuente en F-023 (M1/M2/M3/EX). Genera un .docx real por plantilla con
 * el/los campo(s) de texto largo al límite duro exacto de config/f023_limites.php, lo convierte a
 * PDF vía LibreOffice, y deja los PDF en storage/documents/calibracion/ para inspección visual
 * manual (1 sola hoja + texto legible).
 *
 * Uso: php scripts/f023_calibrar_piso_fuente.php
 * No forma parte de la suite automatizada — genera artefactos reales, ejecutar manualmente.
 */

define('BASE_PATH', dirname(__DIR__));
require BASE_PATH . '/vendor/autoload.php';

use App\Exports\F023DocxToPdf;
use App\Exports\F023ExTemplateMacroInjector;
use App\Exports\F023M1TemplateMacroInjector;
use App\Exports\F023M2TemplateMacroInjector;
use App\Exports\F023M3TemplateMacroInjector;
use App\Exports\F023ObservationLines;
use PhpOffice\PhpWord\Settings;
use PhpOffice\PhpWord\TemplateProcessor;

Settings::setOutputEscapingEnabled(true);

$outDir = BASE_PATH . '/storage/documents/calibracion';
if (!is_dir($outDir)) {
    mkdir($outDir, 0777, true);
}

function saveSegment(string $templatePath, callable $patchToTempFn, callable $applyLayoutFn, array $vars): string
{
    $path = $patchToTempFn($templatePath);
    $tpl = new TemplateProcessor($path);
    foreach ($vars as $k => $v) {
        $tpl->setValue($k, (string) $v);
    }

    $tmp = tempnam(sys_get_temp_dir(), 'calib_');
    $tmpDocx = $tmp . '.docx';
    rename($tmp, $tmpDocx);
    $tpl->saveAs($tmpDocx);
    $applyLayoutFn($tmpDocx);

    return $tmpDocx;
}

function repeatToLength(string $phrase, int $length): string
{
    $repeated = str_repeat($phrase . ' ', (int) ceil($length / strlen($phrase)) + 1);

    return mb_substr($repeated, 0, $length);
}

function convertAndSave(string $docxPath, string $outDir, string $label): void
{
    if (!F023DocxToPdf::isAvailable()) {
        echo "  [$label] LibreOffice no disponible, se omite conversión a PDF.\n";
        rename($docxPath, "$outDir/$label.docx");

        return;
    }

    try {
        $pdfPath = F023DocxToPdf::convert($docxPath);
        copy($pdfPath, "$outDir/$label.pdf");
        @unlink($pdfPath);
        echo "  [$label] PDF generado: $outDir/$label.pdf\n";
    } catch (\Throwable $e) {
        echo "  [$label] FALLO conversión PDF: " . $e->getMessage() . "\n";
    }
    @unlink($docxPath);
}

$limites = require BASE_PATH . '/config/f023_limites.php';

echo "Piso actual (font_scale_floor_half_points): " . ($limites['font_scale_floor_half_points'] ?? 'no definido') . "\n\n";

// --- M1: los 4 campos de flujo libre al límite duro simultáneamente (peor caso real) ---
echo "Generando M1 (peor caso: 4 campos al límite duro)...\n";
$m1Docx = saveSegment(
    BASE_PATH . '/storage/templates/m1.docx',
    [F023M1TemplateMacroInjector::class, 'patchToTemp'],
    [F023M1TemplateMacroInjector::class, 'applyLayoutToSavedDocx'],
    [
        'fecha_inicio_etapa' => '01/01/2026',
        'fecha_fin_etapa' => '01/07/2026',
        'fecha_arl' => '01/01/2026',
        'numero_poliza_arl' => '123456789',
        'horario' => 'Diurno',
        'enlace_grabacion' => 'http://localhost/ejemplo?a=1&b=2',
        'm1_competencias' => repeatToLength('Competencia laboral de prueba con contenido realista para calibración.', (int) $limites['m1_competencias']),
        'm1_resultados' => repeatToLength('Resultado de aprendizaje de prueba con contenido realista para calibración.', (int) $limites['m1_resultados']),
        'm1_actividades' => repeatToLength('Actividad de concertación de prueba con contenido realista para calibración del piso de fuente.', (int) $limites['m1_actividades']),
        'm1_evidencias' => repeatToLength('Evidencia de aprendizaje de prueba con contenido realista para calibración.', (int) $limites['m1_evidencias']),
        'm1_observaciones_adicionales' => repeatToLength('Observación adicional de prueba para calibración del piso de fuente.', (int) $limites['m1_observaciones_adicionales']),
        'm1_observaciones_adicionales_l2' => '',
        'm1_observaciones_adicionales_l3' => '',
        'm1_observaciones_adicionales_l4' => '',
        'nombre_aprendiz' => 'Aprendiz De Calibración Con Nombre Largo',
        'nombre_instructor' => 'Instructor De Calibración',
        'nombre_coformador' => 'Coformador De Calibración',
        'ciudad_diligenciamiento' => 'Medellín',
        'fecha_diligenciamiento' => '01/01/2026',
        'm1_marca_presencial' => 'X',
        'm1_marca_virtual' => '',
    ]
);
convertAndSave($m1Docx, $outDir, 'm1_peor_caso');

// --- M2: 3 compromisos al límite duro cada uno ---
echo "Generando M2 (peor caso: 3 observaciones complementarias al límite duro)...\n";
$m2ObsMax = repeatToLength('Observación complementaria de prueba con contenido realista para calibración del piso.', (int) $limites['obs_instructor']);
$m2Out = F023ObservationLines::expandTemplateVars(
    ['obs_instructor' => $m2ObsMax, 'obs_aprendiz' => $m2ObsMax, 'obs_coformador' => $m2ObsMax],
    ['obs_instructor', 'obs_aprendiz', 'obs_coformador']
);
$m2Docx = saveSegment(
    BASE_PATH . '/storage/templates/m2.docx',
    [F023M2TemplateMacroInjector::class, 'patchToTemp'],
    [F023M2TemplateMacroInjector::class, 'applyLayoutToSavedDocx'],
    array_merge([
        'fecha_inicio_etapa' => '01/01/2026',
        'fecha_visita' => '01/02/2026',
        'modalidad' => 'Virtual',
        'enlace_grabacion' => '',
        'nombre_aprendiz' => 'Aprendiz De Calibración Con Nombre Largo',
        'nombre_instructor_seguimiento' => 'Instructor De Calibración',
        'nombre_coformador' => 'Coformador De Calibración',
        'ciudad_diligenciamiento' => 'Medellín',
        'fecha_diligenciamiento' => '01/02/2026',
        'm2_marca_presencial' => '',
        'm2_marca_virtual' => 'X',
    ], $m2Out)
);
convertAndSave($m2Docx, $outDir, 'm2_peor_caso');

// --- M3 p1: retro_coformador al límite duro ---
echo "Generando M3 p1 (peor caso: retro_coformador al límite duro)...\n";
$retroMax = repeatToLength('Retroalimentación de proceso de prueba con contenido realista para calibración del piso de fuente.', (int) $limites['retro_m3']);
$m3P1Docx = saveSegment(
    BASE_PATH . '/storage/templates/m3_p1.docx',
    [F023M3TemplateMacroInjector::class, 'patchToTemp'],
    [F023M3TemplateMacroInjector::class, 'applyLayoutToSavedDocx'],
    [
        'nombre_aprendiz' => 'Aprendiz De Calibración Con Nombre Largo',
        'nombre_coformador' => 'Coformador De Calibración',
        'nombre_instructor' => 'Instructor De Calibración',
        'm3_retro_coformador_proceso' => $retroMax,
        'm3_retro_coformador_desempeno' => $retroMax,
    ]
);
convertAndSave($m3P1Docx, $outDir, 'm3_p1_peor_caso');

// --- M3 p2: retro_instructor + retro_aprendiz al límite duro ---
echo "Generando M3 p2 (peor caso: retro_instructor + retro_aprendiz al límite duro)...\n";
$m3P2Docx = saveSegment(
    BASE_PATH . '/storage/templates/m3_p2.docx',
    [F023M3TemplateMacroInjector::class, 'patchToTemp'],
    [F023M3TemplateMacroInjector::class, 'applyLayoutToSavedDocx'],
    [
        'nombre_aprendiz' => 'Aprendiz De Calibración Con Nombre Largo',
        'nombre_coformador' => 'Coformador De Calibración',
        'nombre_instructor' => 'Instructor De Calibración',
        'm3_retro_instructor_proceso' => $retroMax,
        'm3_retro_instructor_desempeno' => $retroMax,
        'm3_retro_aprendiz_proceso' => $retroMax,
        'm3_retro_aprendiz_desempeno' => $retroMax,
        'm3_juicio_marca_aprobado' => 'X',
        'm3_juicio_marca_no_aprobado' => '',
    ]
);
convertAndSave($m3P2Docx, $outDir, 'm3_p2_peor_caso');

// --- EX: motivo + 3 compromisos al límite duro ---
echo "Generando EX (peor caso: motivo + 3 compromisos al límite duro)...\n";
$exMotivoMax = repeatToLength('Motivo detallado de seguimiento extraordinario de prueba realista para calibración.', (int) $limites['motivo_seguimiento_extraordinario']);
$exObsMax = repeatToLength('Compromiso de seguimiento extraordinario de prueba realista para calibración del piso.', (int) $limites['obs_instructor']);
$exOut = F023ObservationLines::expandTemplateVars(
    ['obs_instructor' => $exObsMax, 'obs_aprendiz' => $exObsMax, 'obs_coformador' => $exObsMax],
    ['obs_instructor', 'obs_aprendiz', 'obs_coformador']
);
$exDocx = saveSegment(
    BASE_PATH . '/storage/templates/extra.docx',
    [F023ExTemplateMacroInjector::class, 'patchToTemp'],
    [F023ExTemplateMacroInjector::class, 'applyLayoutToSavedDocx'],
    array_merge([
        'numero_visita' => '1',
        'fecha_seguimiento_anterior' => '01/01/2026',
        'fecha_visita' => '01/02/2026',
        'modalidad' => 'Virtual',
        'enlace_grabacion' => '',
        'motivo_seguimiento_extraordinario' => $exMotivoMax,
        'nombre_aprendiz' => 'Aprendiz De Calibración Con Nombre Largo',
        'nombre_instructor_seguimiento' => 'Instructor De Calibración',
        'nombre_coformador' => 'Coformador De Calibración',
        'ciudad_diligenciamiento' => 'Medellín',
        'fecha_diligenciamiento' => '01/02/2026',
        'ex_marca_presencial' => '',
        'ex_marca_virtual' => 'X',
    ], $exOut)
);
convertAndSave($exDocx, $outDir, 'ex_peor_caso');

echo "\nListo. PDFs en: $outDir\n";
