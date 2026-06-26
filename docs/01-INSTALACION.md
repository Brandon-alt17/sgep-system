## Instalación del Proyecto SGEP

> **Guía principal de entrega e instalación (USB, WAMP, MAMP, errores comunes):**  
> **[GUIA-INSTALACION-ENTREGA.md](./GUIA-INSTALACION-ENTREGA.md)**

Incluye alcance y limitaciones v1.0, pasos con espacio para capturas en `docs/imagenes/`, e instalación desde memoria USB.

### Resumen rápido (desarrollo)

### Requisitos previos

- **PHP 8.2+** (`php -v`)
- **Composer 2.x** (`composer --version`)
- **Node.js 18+ y NPM 9+** (`node -v` y `npm -v`) — solo para compilar CSS
- **MySQL 8.0+**
- **WAMP** (Windows) o **MAMP** (macOS)

### Pasos (equipo de desarrollo)

```bash
composer install
npm install
npm run build:css
cp .env.example .env   # Windows: copy .env.example .env
php database/run_migrations.php
```

- Apache DocumentRoot → carpeta **`public/`**
- URL: `http://localhost/sgep/` (WAMP) o `http://localhost:8888/sgep/` (MAMP)

### Documentación adicional

- [GUIA-INSTALACION-ENTREGA.md](./GUIA-INSTALACION-ENTREGA.md) — instalación final + USB + errores
- [06-despliegue.md](./06-despliegue.md) — empaquetado ZIP para directivos
- [manual-usuario.md](./manual-usuario.md) — uso del sistema
