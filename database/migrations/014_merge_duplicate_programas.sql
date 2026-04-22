-- 1) Completa nivel cuando venga incrustado en el nombre.
UPDATE programas
SET nivel = 'Técnico'
WHERE (nivel IS NULL OR TRIM(nivel) = '')
  AND (
    LOWER(TRIM(nombre)) LIKE 'tecnico %'
    OR LOWER(TRIM(nombre)) LIKE 'técnico %'
    OR LOWER(TRIM(nombre)) LIKE 'tec.%'
  );

UPDATE programas
SET nivel = 'Tecnólogo'
WHERE (nivel IS NULL OR TRIM(nivel) = '')
  AND (
    LOWER(TRIM(nombre)) LIKE 'tecnologo %'
    OR LOWER(TRIM(nombre)) LIKE 'tecnólogo %'
  );

-- 2) Limpia prefijos de nivel en el nombre para que quede solo el programa.
UPDATE programas
SET nombre = TRIM(
  CASE
    WHEN LOWER(TRIM(nombre)) LIKE 'tec.%' THEN SUBSTRING(TRIM(nombre), LOCATE(' ', TRIM(nombre)) + 1)
    WHEN LOWER(TRIM(nombre)) LIKE 'tecnico en %' OR LOWER(TRIM(nombre)) LIKE 'técnico en %' THEN SUBSTRING(TRIM(nombre), LOCATE(' en ', LOWER(TRIM(nombre))) + 4)
    WHEN LOWER(TRIM(nombre)) LIKE 'tecnico %' OR LOWER(TRIM(nombre)) LIKE 'técnico %' THEN SUBSTRING(TRIM(nombre), LOCATE(' ', TRIM(nombre)) + 1)
    WHEN LOWER(TRIM(nombre)) LIKE 'tecnologo en %' OR LOWER(TRIM(nombre)) LIKE 'tecnólogo en %' THEN SUBSTRING(TRIM(nombre), LOCATE(' en ', LOWER(TRIM(nombre))) + 4)
    WHEN LOWER(TRIM(nombre)) LIKE 'tecnologo %' OR LOWER(TRIM(nombre)) LIKE 'tecnólogo %' THEN SUBSTRING(TRIM(nombre), LOCATE(' ', TRIM(nombre)) + 1)
    ELSE TRIM(nombre)
  END
);

-- 3) Normaliza algunas variantes frecuentes de siglas.
UPDATE programas
SET nombre = REPLACE(nombre, 'Tics', 'TIC');

UPDATE programas
SET nombre = REPLACE(nombre, 'tic', 'TIC')
WHERE LOWER(nombre) LIKE '% tic%';

-- 4) Construye mapa temporal de claves normalizadas para detectar duplicados.
CREATE TEMPORARY TABLE tmp_programas_norm AS
SELECT
    id,
    LOWER(TRIM(
      REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(
        REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(nombre,
          'Á', 'A'), 'É', 'E'), 'Í', 'I'), 'Ó', 'O'), 'Ú', 'U'), 'Ü', 'U'),
          'á', 'a'), 'é', 'e'), 'í', 'i'), 'ó', 'o'), 'ú', 'u'), 'ü', 'u')
    )) AS norm_nombre,
    LOWER(TRIM(
      REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(
        REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(COALESCE(nivel, ''),
          'Á', 'A'), 'É', 'E'), 'Í', 'I'), 'Ó', 'O'), 'Ú', 'U'), 'Ü', 'U'),
          'á', 'a'), 'é', 'e'), 'í', 'i'), 'ó', 'o'), 'ú', 'u'), 'ü', 'u')
    )) AS norm_nivel
FROM programas;

CREATE TEMPORARY TABLE tmp_programas_keep AS
SELECT
    MIN(id) AS keep_id,
    norm_nombre,
    norm_nivel
FROM tmp_programas_norm
GROUP BY norm_nombre, norm_nivel;

-- 5) Reasigna aprendices al programa canónico.
UPDATE aprendices a
JOIN tmp_programas_norm n ON a.programa_id = n.id
JOIN tmp_programas_keep k ON k.norm_nombre = n.norm_nombre AND k.norm_nivel = n.norm_nivel
SET a.programa_id = k.keep_id
WHERE a.programa_id <> k.keep_id;

-- 6) Elimina programas duplicados (ya sin referencias en aprendices).
DELETE p
FROM programas p
JOIN tmp_programas_norm n ON p.id = n.id
JOIN tmp_programas_keep k ON k.norm_nombre = n.norm_nombre AND k.norm_nivel = n.norm_nivel
WHERE p.id <> k.keep_id;
