-- Eliminación de columnas retiradas del formulario de importación.
SET @has_fecha_sofia := (
    SELECT COUNT(*)
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'aprendices'
      AND COLUMN_NAME = 'fecha_sofia'
);
SET @sql_fecha_sofia := IF(
    @has_fecha_sofia > 0,
    'ALTER TABLE aprendices DROP COLUMN fecha_sofia',
    'SELECT 1'
);
PREPARE stmt_fecha_sofia FROM @sql_fecha_sofia;
EXECUTE stmt_fecha_sofia;
DEALLOCATE PREPARE stmt_fecha_sofia;

SET @has_ficha_curso := (
    SELECT COUNT(*)
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'aprendices'
      AND COLUMN_NAME = 'ficha_curso'
);
SET @sql_ficha_curso := IF(
    @has_ficha_curso > 0,
    'ALTER TABLE aprendices DROP COLUMN ficha_curso',
    'SELECT 1'
);
PREPARE stmt_ficha_curso FROM @sql_ficha_curso;
EXECUTE stmt_ficha_curso;
DEALLOCATE PREPARE stmt_ficha_curso;
