ALTER TABLE empresa_jefes ADD COLUMN nombre_contacto2 VARCHAR(160) NULL;
ALTER TABLE empresa_jefes ADD COLUMN correo_contacto2 VARCHAR(150) NULL;
UPDATE empresa_jefes j
INNER JOIN empresas e ON e.id = j.empresa_id
SET j.nombre_contacto2 = COALESCE(NULLIF(j.nombre_contacto2, ''), NULLIF(e.nombre_contacto2, '')),
    j.correo_contacto2 = COALESCE(NULLIF(j.correo_contacto2, ''), NULLIF(e.correo_contacto2, ''))
WHERE j.nombre_contacto2 IS NULL OR j.correo_contacto2 IS NULL;
