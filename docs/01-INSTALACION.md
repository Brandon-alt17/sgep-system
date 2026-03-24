## Instalación del Proyecto SGEP

### Requisitos previos

- **PHP 8.3+** (verificar con `php -v`)
- **Composer 2.x** (verificar con `composer --version`)
- **Node.js 18+ y NPM 9+** (verificar con `node -v` y `npm -v`)
- **MySQL 8.0+** (incluido en WAMP/MAMP o XAMPP)
- **WAMP** (Windows) o **MAMP** (macOS) instalado

> **Nota:** Entorno recomendado oficial: **WAMP (Windows)** y **MAMP (macOS)**.  
> **XAMPP** puede usarse como alternativa opcional.

### Pasos de instalación

1. **Clonar el repositorio**

```bash
git clone https://github.com/Brandon-alt17/sgep-system.git
cd sgep-system
```

2. **Instalar dependencias PHP**

```bash
composer install
```

3. **Instalar dependencias JavaScript**

```bash
npm install
npm run build
```

4. **Configurar variables de entorno**

```bash
# Windows
copy .env.example .env

# macOS/Linux
cp .env.example .env
```

5. **Generar clave de aplicación**

```bash
php artisan key:generate
```

6. **Configurar base de datos en `.env`**

Editar el archivo `.env` y configurar:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=sgep
DB_USERNAME=root
DB_PASSWORD=
```

7. **Crear base de datos**

Desde phpMyAdmin:
- Abrir `http://localhost/phpmyadmin`
- Click en **Nueva**
- Nombre: `sgep`
- Collation: `utf8mb4_unicode_ci`
- Click en **Crear**

8. **Ejecutar migraciones**

```bash
php artisan migrate
```

9. **Iniciar el servidor**

```bash
php artisan serve
```

10. **Acceder al sistema**

- Opcion A: Usando Laravel (recomendado) -> `http://localhost:8000`
- Opcion B: Usando WAMP/MAMP/XAMPP -> `http://localhost/sgep-system/public`

### Documentación adicional

- Ver carpeta `/docs` para documentacion tecnica
- Ver `README.md` para informacion general del proyecto

### Solución de problemas comunes

- **Error: "PHP version does not satisfy"**
  - Verifica que tengas PHP 8.3+ instalado
  - Configura WAMP para usar PHP 8.3 (`PHP -> Version -> 8.3.x`)
- **Error: "npm run build falla"**
  - Asegurate de tener Node.js 18+
  - Ejecuta: `npm cache clean --force` y luego `npm install`
- **Error: "Table already exists"**
  - Ejecuta: `php artisan migrate:fresh`

### Verificación de instalación

Despues de instalar, verifica:
- `php artisan --version` muestra Laravel 10.x
- `npm list` muestra `tailwindcss`, `alpinejs`, `vite`
- `public/build/manifest.json` existe
- El servidor inicia sin errores en `http://localhost:8000`