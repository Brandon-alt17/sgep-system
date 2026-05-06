-- Asegura la columna antes de las vistas (entornos sin 019 aplicada o BD restaurada parcialmente).
-- run_migrations.php ignora duplicados MySQL 1060/1061.
ALTER TABLE aprendices ADD COLUMN proxima_visita DATE NULL;
ALTER TABLE aprendices ADD INDEX idx_aprendiz_proxima_visita (proxima_visita);

DROP VIEW IF EXISTS vw_momentos_con_proxima_visita;

CREATE VIEW vw_momentos_con_proxima_visita AS
SELECT
    m.id,
    m.aprendiz_id,
    m.tipo,
    m.numero_visita,
    m.fecha_visita,
    m.modalidad,
    m.obs_instructor,
    m.obs_aprendiz,
    m.obs_coformador,
    m.juicio_final,
    m.created_at,
    m.updated_at,
    a.proxima_visita
FROM momentos m
JOIN aprendices a ON a.id = m.aprendiz_id;

DROP VIEW IF EXISTS vw_aprendices_proximas_visitas;

CREATE VIEW vw_aprendices_proximas_visitas AS
SELECT
    a.id AS aprendiz_id,
    a.nombre_completo,
    a.estado,
    a.proxima_visita
FROM aprendices a
WHERE a.proxima_visita IS NOT NULL;
