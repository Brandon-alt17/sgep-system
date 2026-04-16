# SGEP (PHP puro)

Sistema de Gestión de Etapa Productiva para SENA CDITI, migrado a PHP puro con arquitectura MVC propia.

## Stack

- PHP 8.2+
- MySQL 8.0+
- PDO
- PhpSpreadsheet
- PHPWord
- DOMPDF

## Ejecución local

1. Instalar dependencias:
   - `composer install`
2. Configurar variables:
   - copiar `.env.example` a `.env`
3. Crear base de datos `sgep`.
4. Ejecutar migraciones SQL:
   - `php database/run_migrations.php`
5. Configurar Apache DocumentRoot a `public/`.
6. Abrir `http://localhost/sgep`.

## Estructura clave

- `public/index.php` y `router.php`: front controller y ruteo (el `index.php` en la raíz solo delega a `public/` por compatibilidad).
- `app/controllers`: controladores HTTP.
- `app/models`: modelos con consultas PDO.
- `app/views`: vistas PHP.
- `database/migrations/*.sql`: scripts SQL versionados.
- `storage/documents`: documentos generados (F-023 y reportes).

## Documentación de UI

- Guía de iconos SVG: `docs/icons.md`

## Comandos de distribución

- Instalación rápida Windows: `instalar.bat`
- Actualización incremental: `actualizar.bat`