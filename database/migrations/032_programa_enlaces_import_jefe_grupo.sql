ALTER TABLE programa_enlaces_pendientes
    ADD COLUMN jefe_grupo_fuente VARCHAR(120) NULL AFTER modalidad_fuente,
    ADD COLUMN coordinacion_fuente VARCHAR(120) NULL AFTER jefe_grupo_fuente;
