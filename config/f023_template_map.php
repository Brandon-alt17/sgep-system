<?php

declare(strict_types=1);

/**
 * Plantillas por bloque del F-023 (storage/templates/*.docx).
 *
 * Variables Word: PhpWord usa ${nombre}. La plantilla `info.docx` no trae marcadores; antes de
 * reemplazar, `F023InfoTemplateMacroInjector` inserta ${...} en las celdas según las etiquetas.
 * `m1.docx` usa `F023M1TemplateMacroInjector` con el mismo criterio (columnas de `momentos` + marcas de modalidad).
 * Las claves en `info_placeholders` deben coincidir con esos nombres. Momentos añaden columnas
 * de la tabla `momentos` y factores:
 *   factor_{0..12}_valoracion_s, factor_{0..12}_valoracion_pm (X según valoración),
 *   factor_{0..12}_observacion (orden en config/factores.php: 8 técnicos + 5 actitudinales).
 *
 * @return array{
 *   info_template: string,
 *   info_placeholders: list<string>,
 *   m1_template: string,
 *   m2_template: string,
 *   m3_templates: list<string>,
 *   ex_template: string,
 * }
 */
return [
    'info_template' => 'info.docx',
    'info_placeholders' => [
        'regional',
        'centro_formacion',
        'nivel_formativo',
        'programa_formacion',
        'numero_grupo',
        'modalidad_presencial',
        'modalidad_virtual',
        'modalidad_distancia',
        'estrategia_formativa',
        'fecha_fin_etapa_lectiva',
        'fecha_registro_sofiaplus',
        'asistencia_nombre',
        'asistencia_tipo',
        'asistencia_contacto',
        'nombre_completo',
        'tipo_documento',
        'numero_documento',
        'telefono',
        'direccion_domicilio',
        'correo_personal',
        'correo_institucional',
        'alternativa_ep',
        'nombre_instructor_seguimiento',
        'telefono_instructor_seguimiento',
        'correo_instructor_seguimiento',
        'empresa_nombre',
        'empresa_direccion',
        'empresa_nit',
        'empresa_correo',
        'jefe_nombre',
        'jefe_cargo',
        'jefe_telefono',
        'contacto2_nombre',
        'contacto2_telefono',
    ],
    'm1_template' => 'm1.docx',
    'm2_template' => 'm2.docx',
    'm3_templates' => ['m3_p1.docx', 'm3_p2.docx'],
    'ex_template' => 'm2.docx',
];
