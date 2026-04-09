INSERT INTO programas (codigo, nombre, nivel, modalidad) VALUES
('228106', 'Análisis y Desarrollo de Software', 'Tecnólogo', 'Presencial')
ON DUPLICATE KEY UPDATE nombre = VALUES(nombre);

INSERT INTO aprendices (nombre_completo, tipo_documento, numero_documento, telefono, correo_personal, ficha, programa_id, estado)
SELECT 'Aprendiz Demo', 'CC', '1000000001', '3000000000', 'demo@sena.edu.co', '2829411', p.id, 'En ejecución'
FROM programas p
WHERE p.codigo = '228106'
ON DUPLICATE KEY UPDATE nombre_completo = VALUES(nombre_completo);
