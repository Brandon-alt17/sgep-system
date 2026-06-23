ALTER TABLE aprendices ADD COLUMN visita_m3_completada TINYINT(1) NOT NULL DEFAULT 0;

CREATE TABLE IF NOT EXISTS visitas_extraordinarias_programadas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    aprendiz_id INT NOT NULL,
    numero_visita INT NOT NULL,
    fecha_programada DATE NULL,
    modalidad VARCHAR(20) NULL,
    completada TINYINT(1) NOT NULL DEFAULT 0,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_visita_ex_aprendiz_num (aprendiz_id, numero_visita),
    INDEX idx_visita_ex_aprendiz (aprendiz_id),
    CONSTRAINT fk_visita_ex_aprendiz FOREIGN KEY (aprendiz_id) REFERENCES aprendices(id) ON DELETE CASCADE
);
