ALTER TABLE aprendices ADD COLUMN jefe_id INT NULL;
ALTER TABLE aprendices ADD INDEX idx_aprendices_jefe_id (jefe_id);
ALTER TABLE aprendices ADD CONSTRAINT fk_aprendices_empresa_jefe FOREIGN KEY (jefe_id) REFERENCES empresa_jefes(id) ON DELETE SET NULL;
