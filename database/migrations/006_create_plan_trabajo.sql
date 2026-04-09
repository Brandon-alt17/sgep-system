CREATE TABLE IF NOT EXISTS plan_trabajo (
    id INT AUTO_INCREMENT PRIMARY KEY,
    momento_id INT NOT NULL,
    competencia TEXT NULL,
    resultado TEXT NULL,
    actividad TEXT NULL,
    evidencia TEXT NULL,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_plan_momento (momento_id)
);
