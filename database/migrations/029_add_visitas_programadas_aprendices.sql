ALTER TABLE aprendices ADD COLUMN visita_programada_m1 DATE NULL;
ALTER TABLE aprendices ADD COLUMN visita_programada_m2 DATE NULL;
ALTER TABLE aprendices ADD COLUMN visita_programada_m3 DATE NULL;
ALTER TABLE aprendices ADD COLUMN modalidad_visita_m1 VARCHAR(20) NULL;
ALTER TABLE aprendices ADD COLUMN modalidad_visita_m2 VARCHAR(20) NULL;
ALTER TABLE aprendices ADD COLUMN modalidad_visita_m3 VARCHAR(20) NULL;
ALTER TABLE aprendices ADD COLUMN visita_m1_completada TINYINT(1) NOT NULL DEFAULT 0;
ALTER TABLE aprendices ADD COLUMN visita_m2_completada TINYINT(1) NOT NULL DEFAULT 0;

UPDATE aprendices
SET visita_programada_m1 = proxima_visita
WHERE visita_programada_m1 IS NULL
  AND proxima_visita IS NOT NULL;
