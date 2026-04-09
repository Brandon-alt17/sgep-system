CREATE TABLE IF NOT EXISTS aprendices (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre_completo VARCHAR(180) NOT NULL,
    tipo_documento VARCHAR(10) NOT NULL,
    numero_documento VARCHAR(30) NOT NULL,
    telefono VARCHAR(30) NULL,
    correo_personal VARCHAR(150) NULL,
    correo_institucional VARCHAR(150) NULL,
    ficha VARCHAR(50) NULL,
    programa_id INT NULL,
    empresa_id INT NULL,
    estado VARCHAR(50) NOT NULL DEFAULT 'Pendiente por iniciar',
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_aprendiz_documento (numero_documento),
    INDEX idx_aprendiz_estado (estado)
);
