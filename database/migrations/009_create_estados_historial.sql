CREATE TABLE IF NOT EXISTS estados_historial (
    id INT AUTO_INCREMENT PRIMARY KEY,
    aprendiz_id INT NOT NULL,
    estado_anterior VARCHAR(50) NULL,
    estado_nuevo VARCHAR(50) NOT NULL,
    motivo TEXT NULL,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_hist_aprendiz (aprendiz_id)
);
