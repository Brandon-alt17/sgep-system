-- Normaliza nulos de nivel para poder aplicar unicidad determinística.
UPDATE programas
SET nivel = ''
WHERE nivel IS NULL;

-- Limpia variantes frecuentes en nivel.
UPDATE programas
SET nivel = 'Técnico'
WHERE LOWER(TRIM(nivel)) IN ('tecnico', 'técnico', 'tec');

UPDATE programas
SET nivel = 'Tecnólogo'
WHERE LOWER(TRIM(nivel)) IN ('tecnologo', 'tecnólogo', 'tgo');

-- Normaliza nombre para reducir variantes de escritura.
UPDATE programas
SET nombre = TRIM(nombre);

-- Reasigna aprendices a un id canónico por (nombre, nivel).
CREATE TEMPORARY TABLE tmp_programa_keep AS
SELECT MIN(id) AS keep_id, nombre, nivel
FROM programas
GROUP BY nombre, nivel;

UPDATE aprendices a
JOIN programas p ON a.programa_id = p.id
JOIN tmp_programa_keep k ON k.nombre = p.nombre AND k.nivel = p.nivel
SET a.programa_id = k.keep_id
WHERE a.programa_id <> k.keep_id;

DELETE p
FROM programas p
JOIN tmp_programa_keep k ON k.nombre = p.nombre AND k.nivel = p.nivel
WHERE p.id <> k.keep_id;

ALTER TABLE programas
MODIFY COLUMN nivel VARCHAR(80) NOT NULL DEFAULT '';

-- Clave canónica de contención: nombre + nivel.
-- Se usan prefijos para compatibilidad con motores que limitan índices a 1000 bytes.
ALTER TABLE programas
ADD UNIQUE KEY uq_programas_nombre_nivel (nombre(150), nivel(40));
