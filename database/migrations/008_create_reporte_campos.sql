CREATE TABLE IF NOT EXISTS reporte_campos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    aprendiz_id INT NOT NULL,
    campo VARCHAR(100) NOT NULL,
    valor TEXT NULL,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_reporte_aprendiz_campo (aprendiz_id, campo)
);
