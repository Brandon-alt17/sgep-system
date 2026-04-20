## Instalación del Proyecto SGEP

### Requisitos previos

- **PHP 8.2+** (`php -v`)
- **Composer 2.x** (`composer --version`)
- **Node.js 18+ y NPM 9+** (`node -v` y `npm -v`)
- **MySQL 8.0+**
- **WAMP** (Windows) o **MAMP** (macOS)

> Entorno recomendado: **WAMP** en Windows y **MAMP** en macOS.

### Pasos de instalación

1. **Clonar el repositorio**

```bash
git clone https://github.com/Brandon-alt17/sgep-system.git
cd sgep-system
```

2. **Instalar dependencias**

```bash
composer install
npm install
npm run build
```

3. **Crear archivo `.env`**

```bash
# Windows
copy .env.example .env

# macOS/Linux
cp .env.example .env
```

4. **Configurar base de datos en `.env`**

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=sgep
DB_USERNAME=root
DB_PASSWORD=
```

5. **Crear base de datos `sgep`**

Desde phpMyAdmin:
- Abrir `http://localhost/phpmyadmin`
- Crear base de datos `sgep`
- Collation `utf8mb4_unicode_ci`

6. **Ejecutar migraciones SQL**

```bash
php database/run_migrations.php
```

7. **Iniciar la aplicación**

- Con Apache (WAMP/MAMP): abrir `http://localhost/sgep/public`
- Opcional con servidor embebido PHP:

```bash
php -S localhost:8000 -t public
```

### Verificación rápida

- `composer install` finaliza sin errores.
- `php database/run_migrations.php` ejecuta los scripts de `database/migrations`.
- La URL local abre correctamente.

### Solución de problemas comunes

- **Error de versión de PHP**
  - Verificar que sea 8.2+ y seleccionar esa versión en WAMP/MAMP.
- **Falla `npm run build`**
  - Ejecutar `npm install` y repetir build.
- **Error de conexión a MySQL**
  - Revisar `.env` (`DB_HOST`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`).

### Documentación adicional

- `README.md` para visión general.
- Carpeta `docs/` para guías técnicas.