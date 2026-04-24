CREATE TABLE IF NOT EXISTS programa_importaciones_pdf (
    id INT AUTO_INCREMENT PRIMARY KEY,
    programa_id INT NULL,
    nombre_archivo VARCHAR(255) NOT NULL,
    codigo_programa VARCHAR(80) NULL,
    nombre_programa VARCHAR(220) NULL,
    estado VARCHAR(30) NOT NULL DEFAULT 'borrador',
    advertencias_json LONGTEXT NULL,
    resumen_json LONGTEXT NULL,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_import_programa_id (programa_id),
    CONSTRAINT fk_import_programa FOREIGN KEY (programa_id) REFERENCES programas(id) ON DELETE SET NULL
);

CREATE TABLE IF NOT EXISTS programa_competencias (
    id INT AUTO_INCREMENT PRIMARY KEY,
    programa_id INT NOT NULL,
    codigo VARCHAR(80) NULL,
    nombre VARCHAR(255) NOT NULL,
    orden INT NOT NULL DEFAULT 0,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_competencias_programa (programa_id),
    CONSTRAINT fk_competencias_programa FOREIGN KEY (programa_id) REFERENCES programas(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS programa_resultados_aprendizaje (
    id INT AUTO_INCREMENT PRIMARY KEY,
    programa_id INT NOT NULL,
    competencia_id INT NOT NULL,
    codigo VARCHAR(80) NULL,
    descripcion TEXT NOT NULL,
    orden INT NOT NULL DEFAULT 0,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_resultados_programa (programa_id),
    INDEX idx_resultados_competencia (competencia_id),
    CONSTRAINT fk_resultados_programa FOREIGN KEY (programa_id) REFERENCES programas(id) ON DELETE CASCADE,
    CONSTRAINT fk_resultados_competencia FOREIGN KEY (competencia_id) REFERENCES programa_competencias(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS programa_enlaces_pendientes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    numero_documento VARCHAR(30) NOT NULL,
    nombre_aprendiz VARCHAR(180) NOT NULL,
    programa_fuente VARCHAR(220) NOT NULL,
    nivel_fuente VARCHAR(80) NULL,
    modalidad_fuente VARCHAR(80) NULL,
    candidatos_json LONGTEXT NULL,
    motivo VARCHAR(30) NOT NULL DEFAULT 'not_found',
    estado VARCHAR(30) NOT NULL DEFAULT 'pendiente',
    programa_id_destino INT NULL,
    resolved_at DATETIME NULL,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_pendientes_estado (estado),
    INDEX idx_pendientes_documento (numero_documento),
    INDEX idx_pendientes_destino (programa_id_destino),
    CONSTRAINT fk_pendientes_destino FOREIGN KEY (programa_id_destino) REFERENCES programas(id) ON DELETE SET NULL
);
