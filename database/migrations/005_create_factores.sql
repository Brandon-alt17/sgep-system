CREATE TABLE IF NOT EXISTS factores_valoracion (
    id INT AUTO_INCREMENT PRIMARY KEY,
    momento_id INT NOT NULL,
    tipo_factor VARCHAR(20) NOT NULL,
    nombre_factor VARCHAR(180) NOT NULL,
    valoracion ENUM('S','PM') NOT NULL,
    observacion TEXT NULL,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_factores_momento (momento_id)
);
