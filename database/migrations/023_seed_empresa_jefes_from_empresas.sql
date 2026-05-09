INSERT INTO empresa_jefes (empresa_id, nombre, cargo, correo, telefono, nombre_contacto2, correo_contacto2, created_at, updated_at)
SELECT e.id,
       TRIM(e.nombre_jefe),
       NULLIF(TRIM(e.cargo_jefe), ''),
       NULLIF(TRIM(e.correo_jefe), ''),
       NULLIF(TRIM(e.telefono_jefe), ''),
       NULLIF(TRIM(e.nombre_contacto2), ''),
       NULLIF(TRIM(e.correo_contacto2), ''),
       NOW(),
       NOW()
FROM empresas e
WHERE TRIM(COALESCE(e.nombre_jefe, '')) <> ''
  AND NOT EXISTS (SELECT 1 FROM empresa_jefes j WHERE j.empresa_id = e.id);
UPDATE aprendices a
INNER JOIN empresa_jefes j ON j.empresa_id = a.empresa_id
SET a.jefe_id = j.id
WHERE a.empresa_id IS NOT NULL
  AND a.jefe_id IS NULL;
