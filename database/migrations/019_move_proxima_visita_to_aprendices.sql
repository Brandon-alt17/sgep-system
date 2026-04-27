ALTER TABLE aprendices
    ADD COLUMN proxima_visita DATE NULL AFTER estado,
    ADD INDEX idx_aprendiz_proxima_visita (proxima_visita);

UPDATE aprendices a
LEFT JOIN (
    SELECT aprendiz_id, MAX(proxima_visita) AS proxima_visita
    FROM momentos
    WHERE proxima_visita IS NOT NULL
    GROUP BY aprendiz_id
) m ON m.aprendiz_id = a.id
SET a.proxima_visita = m.proxima_visita
WHERE a.proxima_visita IS NULL;

SET @has_proxima_visita := (
    SELECT COUNT(*)
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'momentos'
      AND COLUMN_NAME = 'proxima_visita'
);

SET @drop_sql := IF(
    @has_proxima_visita > 0,
    'ALTER TABLE momentos DROP COLUMN proxima_visita',
    'SELECT 1'
);

PREPARE stmt_drop_proxima FROM @drop_sql;
EXECUTE stmt_drop_proxima;
DEALLOCATE PREPARE stmt_drop_proxima;
