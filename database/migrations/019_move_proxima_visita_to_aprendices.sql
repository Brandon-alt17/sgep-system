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

ALTER TABLE momentos
    DROP COLUMN IF EXISTS proxima_visita;
