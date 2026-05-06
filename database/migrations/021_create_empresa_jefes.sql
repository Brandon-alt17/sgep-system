CREATE TABLE IF NOT EXISTS empresa_jefes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    empresa_id INT NOT NULL,
    nombre VARCHAR(160) NOT NULL,
    cargo VARCHAR(120) NULL,
    correo VARCHAR(150) NULL,
    telefono VARCHAR(30) NULL,
    nombre_contacto2 VARCHAR(160) NULL,
    correo_contacto2 VARCHAR(150) NULL,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_empresa_jefes_empresa_id (empresa_id),
    CONSTRAINT fk_empresa_jefes_empresa FOREIGN KEY (empresa_id) REFERENCES empresas(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
