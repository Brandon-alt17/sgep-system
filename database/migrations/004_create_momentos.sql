CREATE TABLE IF NOT EXISTS momentos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    aprendiz_id INT NOT NULL,
    tipo ENUM('M1','M2','M3','EX') NOT NULL,
    numero_visita INT NULL,
    fecha_visita DATE NOT NULL,
    modalidad VARCHAR(20) NULL,
    proxima_visita DATE NULL,
    obs_instructor TEXT NULL,
    obs_aprendiz TEXT NULL,
    obs_coformador TEXT NULL,
    juicio_final VARCHAR(50) NULL,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_momento_aprendiz (aprendiz_id),
    INDEX idx_momento_tipo (aprendiz_id, tipo),
    INDEX idx_momento_proxima (proxima_visita)
);
