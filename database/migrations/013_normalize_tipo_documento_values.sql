UPDATE aprendices
SET tipo_documento = 'CC'
WHERE LOWER(TRIM(tipo_documento)) IN (
    'cc',
    'cedula',
    'cédula',
    'cedula de',
    'cédula de',
    'cedula de ciudadania',
    'cédula de ciudadanía'
);

UPDATE aprendices
SET tipo_documento = 'TI'
WHERE LOWER(TRIM(tipo_documento)) IN (
    'ti',
    'tarjeta',
    'tarjeta de',
    'tarjeta de identidad'
);
