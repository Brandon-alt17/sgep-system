-- Alinea esquema legacy con módulos recientes que persisten updated_at.
-- Si la columna ya existe, run_migrations.php ignora el error MySQL 1060 (duplicada).
ALTER TABLE programas
ADD COLUMN updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP;

