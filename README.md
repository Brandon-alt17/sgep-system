# SGEP (PHP puro)

Sistema de Gestión de Etapa Productiva para SENA CDITI, migrado a PHP puro con arquitectura MVC propia.

## Stack

- PHP 8.2+
- MySQL 8.0+
- PDO
- PhpSpreadsheet
- PHPWord
- DOMPDF

## Ejecución local (desarrollo)

1. Instalar dependencias:
   - `composer install`
   - `npm install && npm run build:css`
2. Configurar variables:
   - copiar `.env.example` a `.env`
3. Ejecutar migraciones:
   - `php database/run_migrations.php` (crea BD `sgep` si no existe)
4. Configurar Apache DocumentRoot a `public/`.
5. Abrir `http://localhost/sgep/` (WAMP) o `http://localhost:8888/sgep/` (MAMP).

## Instalación en PC del cliente (memoria USB)

**Guía completa:** [docs/GUIA-INSTALACION-ENTREGA.md](docs/GUIA-INSTALACION-ENTREGA.md)

Incluye alcance y limitaciones, pasos Windows/Mac, placeholders para capturas en `docs/imagenes/`, y errores frecuentes (WAMP sin servicios verdes, `mod_rewrite` en MAMP, etc.).

Resumen: descomprimir ZIP → copiar a `www` / `htdocs` → ejecutar `instalar.bat` (Windows) o `scripts/macos/instalar.sh` (Mac) → abrir navegador.

Archivo de texto en la USB: `LEEME.txt`

## Estructura clave

- `public/index.php` y `router.php`: front controller y ruteo (el `index.php` en la raíz solo delega a `public/` por compatibilidad).
- `app/controllers`: controladores HTTP.
- `app/models`: modelos con consultas PDO.
- `app/views`: vistas PHP.
- `database/migrations/*.sql`: scripts SQL versionados.
- `storage/documents`: documentos generados (F-023 y reportes).
- `storage/templates`: plantillas Word vacías del F-023 (versionadas; ver `storage/templates/README.md`).

## Documentación de UI

- Guía de iconos SVG: `docs/icons.md`

## Comandos de distribución

- Instalación rápida Windows: `instalar.bat`
- Actualización incremental: `actualizar.bat`
- Abrir SGEP en el navegador: `abrir_sgep.bat` o `SGEP.url` (copiar al escritorio)
- Mac: `scripts/macos/instalar.sh`, `actualizar.sh`, `abrir_sgep.sh`
- **Guía de entrega e instalación (USB):** [docs/GUIA-INSTALACION-ENTREGA.md](docs/GUIA-INSTALACION-ENTREGA.md)