SET @has_aprendices_col := (
    SELECT COUNT(*)
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'aprendices'
      AND COLUMN_NAME = 'proxima_visita'
);

SET @add_col_sql := IF(
    @has_aprendices_col = 0,
    'ALTER TABLE aprendices ADD COLUMN proxima_visita DATE NULL AFTER estado',
    'SELECT 1'
);
PREPARE stmt_add_col FROM @add_col_sql;
EXECUTE stmt_add_col;
DEALLOCATE PREPARE stmt_add_col;

SET @has_aprendices_idx := (
    SELECT COUNT(*)
    FROM INFORMATION_SCHEMA.STATISTICS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'aprendices'
      AND INDEX_NAME = 'idx_aprendiz_proxima_visita'
);

SET @add_idx_sql := IF(
    @has_aprendices_idx = 0,
    'ALTER TABLE aprendices ADD INDEX idx_aprendiz_proxima_visita (proxima_visita)',
    'SELECT 1'
);
PREPARE stmt_add_idx FROM @add_idx_sql;
EXECUTE stmt_add_idx;
DEALLOCATE PREPARE stmt_add_idx;

SET @has_momentos_col := (
    SELECT COUNT(*)
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'momentos'
      AND COLUMN_NAME = 'proxima_visita'
);

SET @copy_sql := IF(
    @has_momentos_col > 0,
    'UPDATE aprendices a
     LEFT JOIN (
         SELECT aprendiz_id, MAX(proxima_visita) AS proxima_visita
         FROM momentos
         WHERE proxima_visita IS NOT NULL
         GROUP BY aprendiz_id
     ) m ON m.aprendiz_id = a.id
     SET a.proxima_visita = m.proxima_visita
     WHERE a.proxima_visita IS NULL',
    'SELECT 1'
);
PREPARE stmt_copy_proxima FROM @copy_sql;
EXECUTE stmt_copy_proxima;
DEALLOCATE PREPARE stmt_copy_proxima;

SET @drop_sql := IF(
    @has_momentos_col > 0,
    'ALTER TABLE momentos DROP COLUMN proxima_visita',
    'SELECT 1'
);
PREPARE stmt_drop_proxima FROM @drop_sql;
EXECUTE stmt_drop_proxima;
DEALLOCATE PREPARE stmt_drop_proxima;
