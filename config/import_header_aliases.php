<?php

declare(strict_types=1);

/**
 * Patrones de encabezado (Google Forms) para ubicar columnas por nombre.
 * Complementa config/import_mapping.php cuando el formulario agrega o reordena preguntas.
 *
 * @return array<string, list<string>>
 */
return [
    'fecha_hora_formulario' => ['marca temporal', 'marca de tiempo', 'fecha hora'],
    'documento_identidad_vigente' => ['documento de identidad vigente', 'documento de identidad', 'numero de documento'],
    'tipo_documento' => ['tipo de documento', 'tipo documento'],
    'programa_formacion' => ['programa de formacion', 'programa formacion'],
    'numero_grupo' => ['numero de grupo', 'numero grupo', 'ficha', 'numero de ficha'],
    'modalidad_formacion' => ['modalidad de formacion', 'modalidad formacion'],
    'nombre_completo' => ['nombre completo'],
    'numero_celular' => ['numero de celular', 'numero celular', 'celular'],
    'direccion_domicilio_aprendiz' => ['direccion de domicilio', 'direccion domicilio'],
    'ciudad_domicilio_aprendiz' => ['ciudad de domicilio', 'ciudad domicilio'],
    'correo_electronico_personal' => ['correo electronico personal', 'correo personal'],
    'correo_electronico_institucional' => ['correo electronico institucional', 'correo institucional'],
    'alternativa_ep' => ['alternativa ep', 'alternativa de ep'],
    'empresa_entidad_coformadora' => ['empresa', 'entidad coformadora', 'coformadora'],
    'direccion_empresa' => ['direccion de la empresa', 'direccion empresa'],
    'ciudad_empresa' => ['ciudad de la empresa', 'ciudad empresa', 'ciudad donde realiza', 'ciudad de la practica', 'ciudad sede'],
    'direccion_realiza_practica' => ['direccion donde realiza', 'direccion practica'],
    'nit_empresa' => ['nit de la empresa', 'nit empresa', 'nit'],
    'correo_organizacional' => ['correo organizacional'],
    'nombre_jefe' => ['nombre del jefe', 'nombre jefe inmediato'],
    'cargo_jefe' => ['cargo del jefe', 'cargo jefe'],
    'correo_jefe' => ['correo del jefe', 'correo jefe'],
    'telefono_jefe' => ['telefono del jefe', 'telefono jefe'],
    'nombre_contacto_2' => ['nombre contacto 2', 'nombre del contacto 2'],
    'correo_contacto_2' => ['correo contacto 2', 'correo del contacto 2'],
    'nombre_instructor_seguimiento' => ['nombre del instructor de seguimiento', 'nombre instructor de seguimiento'],
    'telefono_instructor_seguimiento' => ['telefono del instructor de seguimiento', 'telefono instructor de seguimiento'],
    'correo_instructor_seguimiento' => ['correo del instructor de seguimiento', 'correo instructor de seguimiento', 'correo instructor seguimiento'],
    'tipo_asistencia' => ['tipo de asistencia', 'tipo asistencia'],
    'sugerencias_comentarios' => ['sugerencias', 'comentarios', 'sugerencias y comentarios'],
    'jefe_grupo' => ['jefe de grupo', 'instructor jefe', 'nombre del jefe de grupo'],
    'coordinacion' => ['coordinacion', 'area de coordinacion'],
];
