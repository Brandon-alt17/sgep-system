-- Una sentencia por columna: run_migrations.php ejecuta cada bloque y omite si la columna ya existe.
ALTER TABLE aprendices ADD COLUMN fecha_hora_formulario DATETIME NULL;
ALTER TABLE aprendices ADD COLUMN direccion_domicilio VARCHAR(255) NULL;
ALTER TABLE aprendices ADD COLUMN ciudad_domicilio VARCHAR(100) NULL;
ALTER TABLE aprendices ADD COLUMN alternativa_ep VARCHAR(120) NULL;
ALTER TABLE aprendices ADD COLUMN fecha_sofia DATE NULL;
ALTER TABLE aprendices ADD COLUMN nombre_instructor_seguimiento VARCHAR(160) NULL;
ALTER TABLE aprendices ADD COLUMN telefono_instructor_seguimiento VARCHAR(30) NULL;
ALTER TABLE aprendices ADD COLUMN tipo_asistencia VARCHAR(80) NULL;
ALTER TABLE aprendices ADD COLUMN sugerencias_comentarios TEXT NULL;
ALTER TABLE aprendices ADD COLUMN ficha_curso VARCHAR(80) NULL;
ALTER TABLE aprendices ADD COLUMN jefe_grupo VARCHAR(120) NULL;
ALTER TABLE aprendices ADD COLUMN coordinacion VARCHAR(120) NULL;
