<?php

declare(strict_types=1);

return [
    'short_text' => 160,
    'url' => 500,
    /** Caracteres por renglón en observaciones m2 (ancho útil hasta margen). */
    'obs_two_line_chars' => 138,
    'obs_instructor' => 396,
    'obs_aprendiz' => 396,
    'obs_coformador' => 396,
    /** Umbrales "recomendados" para obs_instructor/aprendiz/coformador (M2 y EX "Compromisos..."), ~70% del límite duro. */
    'obs_instructor_recomendado' => 280,
    'obs_aprendiz_recomendado' => 280,
    'obs_coformador_recomendado' => 280,
    /**
     * Suma de los 3 campos de observaciones complementarias (M2) / compromisos (EX): lo que
     * determina si esa sección desborda la página es el total combinado de las 3, no cada campo
     * por separado (las 3 líneas de subrayado comparten el mismo bloque de página). Usado por
     * F023DynamicFontScaleSupport para aplicar UN tamaño uniforme a las 3 a la vez.
     */
    'obs_complementarias_total' => 1188,
    'obs_complementarias_total_recomendado' => 840,
    'motivo_seguimiento_extraordinario' => 500,
    'motivo_seguimiento_extraordinario_recomendado' => 350,
    'compromisos' => 240,
    /** Columna "Observaciones / Compromisos de mejora" de cada fila de factores (M2, M3 p1, EX), ~70% del límite duro. */
    'compromisos_recomendado' => 168,
    'm1_competencias' => 1200,
    'm1_resultados' => 1200,
    'm1_actividades' => 3000,
    'm1_evidencias' => 1200,
    'm1_observaciones_adicionales' => 500,
    /**
     * Umbrales "recomendados" (no bloquean escritura): a partir de aquí el contador
     * avisa que el texto puede empujar el Momento 1 a una segunda hoja. Basado en pruebas
     * reales de generación (LibreOffice) con la plantilla m1.docx actual.
     */
    'm1_competencias_recomendado' => 600,
    'm1_resultados_recomendado' => 600,
    'm1_actividades_recomendado' => 900,
    'm1_evidencias_recomendado' => 600,
    'm1_observaciones_adicionales_recomendado' => 260,
    'retro_m3' => 1200,
    'retro_m3_recomendado' => 840,

    /**
     * Reducción progresiva de tamaño de letra (F023DynamicFontScale), en medios-puntos OOXML
     * (w:sz/w:szCs — 18 = 9pt, el tamaño base usado hoy en las 4 plantillas de Momento).
     * Cuando el texto de un campo supera su umbral "_recomendado" de arriba, el tamaño se
     * interpola hacia el piso hasta el límite duro del campo, como garantía real de 1 sola hoja
     * (el contador del formulario solo avisa; esto es lo que realmente se aplica al generar).
     *
     * Piso determinado empíricamente (scripts/f023_calibrar_piso_fuente.php): se generaron
     * documentos reales (Word -> PDF vía LibreOffice) con cada campo largo al límite duro y se
     * inspeccionó visualmente. A 12 (6pt) cada campo probado de forma aislada (o de a dos) se
     * mantiene en 1 sola hoja y legible. Bajarlo más deja de ser razonablemente legible.
     *
     * Riesgo residual conocido y aceptado: si TODOS los campos largos de un mismo momento se
     * llenan al límite duro simultáneamente, ese peor caso extremo sigue desbordando a una 2ª
     * hoja incluso con la letra en este piso — las tablas de factores/cabeceras/firmas ya
     * consumen un presupuesto fijo de página que ninguna reducción de letra razonable compensa.
     * Se decidió no bajar más el piso para no sacrificar legibilidad por un escenario extremo
     * poco realista (mismo criterio que ya se aceptó con el contador "recomendado" de M1).
     */
    'font_scale_max_half_points' => 18,
    'font_scale_floor_half_points' => 12,
];
