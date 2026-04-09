CREATE TABLE IF NOT EXISTS documentos_generados (
    id INT AUTO_INCREMENT PRIMARY KEY,
    aprendiz_id INT NOT NULL,
    partes JSON NOT NULL,
    formato VARCHAR(10) NOT NULL,
    ruta_archivo VARCHAR(255) NOT NULL,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_doc_aprendiz (aprendiz_id)
);
