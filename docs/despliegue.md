# 🚀 Despliegue y Distribución — SGEP

**Documento:** Proceso de empaquetado y distribución a directivos  
**Versión:** 1.0

---

## Índice

- [Entornos de ejecución](#entornos-de-ejecución)
- [Proceso de empaquetado](#proceso-de-empaquetado)
- [Instalación en Windows (WAMP)](#instalación-en-windows-wamp)
- [Instalación en macOS (Herd)](#instalación-en-macos-herd)
- [Actualizaciones](#actualizaciones)
- [Comandos de referencia](#comandos-de-referencia)
- [Solución de problemas](#solución-de-problemas)

---

## Entornos de ejecución

El SGEP es una aplicación web **100% local**. No requiere internet para funcionar. Cada instalación es independiente y sus datos solo existen en ese PC.

| Componente | Qué provee | Quién lo instala |
|-----------|-----------|-----------------|
| **WAMP / XAMPP** (Windows) | PHP 8.2, MySQL 8.0, Apache | El directivo, una sola vez |
| **Laravel Herd + DBngin** (macOS) | PHP 8.2, MySQL 8.0 | El directivo, una sola vez |
| **Carpeta del SGEP** | Laravel + dependencias + código | El equipo, en cada versión |
| **Base de datos MySQL** | Tablas vacías o con datos | El script de instalación |

---

## Proceso de empaquetado

El equipo realiza estos pasos **una vez por versión**, en el PC de desarrollo, antes de distribuir.

### Paso 1 — Preparar dependencias de producción

```bash
# Instalar solo lo necesario para producción (excluye herramientas de testing)
composer install --no-dev --optimize-autoloader

# Compilar Tailwind CSS en un archivo minificado
npm install
npm run build
```

> ✅ Esto genera la carpeta `vendor/` con Laravel y todas las librerías, y `public/build/` con los assets CSS compilados. El directivo no necesita Composer ni Node.js.

### Paso 2 — Optimizar para producción

```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

### Paso 3 — Preparar el archivo `.env.example`

Verificar que `.env.example` tiene los valores correctos para WAMP:

```env
APP_NAME=SGEP
APP_ENV=local
APP_KEY=                    # Se genera con artisan key:generate
APP_DEBUG=false
APP_URL=http://localhost/sgep/public

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=sgep
DB_USERNAME=root
DB_PASSWORD=                # Vacío en WAMP por defecto

SESSION_DRIVER=file
CACHE_STORE=file
QUEUE_CONNECTION=sync
```

### Paso 4 — Crear el script de instalación

**`instalar.bat`** (para Windows con WAMP):

```bat
@echo off
echo ================================================
echo   Instalando SGEP - Sistema de Gestion de
echo   Etapa Productiva - SENA CDITI
echo ================================================
echo.

:: Verificar que PHP está disponible
php --version >nul 2>&1
if %errorlevel% neq 0 (
    echo ERROR: PHP no encontrado. Asegurese de que WAMP este corriendo.
    pause
    exit /b 1
)

echo [1/3] Configurando variables de entorno...
copy .env.example .env >nul
echo      .env creado correctamente.

echo [2/3] Generando clave de aplicacion...
php artisan key:generate --force
echo      Clave generada correctamente.

echo [3/3] Creando base de datos...
php artisan migrate --force
echo      Base de datos lista.

echo.
echo ================================================
echo   INSTALACION COMPLETADA
echo   Abra su navegador y vaya a:
echo   http://localhost/sgep/public
echo ================================================
pause
```

**`actualizar.bat`** (para versiones futuras):

```bat
@echo off
echo Actualizando SGEP...

echo [1/2] Aplicando cambios en base de datos...
php artisan migrate --force

echo [2/2] Limpiando cache...
php artisan config:clear
php artisan config:cache
php artisan view:clear

echo.
echo Actualizacion completada. Recargue el navegador.
pause
```

### Paso 5 — Empaquetar en ZIP

Estructura del ZIP de distribución:

```
SGEP_v1.0.zip
├── sgep/                       ← Carpeta del proyecto completa
│   ├── app/
│   ├── database/
│   ├── public/
│   ├── resources/
│   ├── routes/
│   ├── storage/
│   ├── vendor/                 ← INCLUIDA (Laravel + dependencias)
│   ├── .env.example
│   └── artisan
├── instalar.bat                ← Script de primera instalación
├── actualizar.bat              ← Script de actualizaciones
└── INSTRUCCIONES.pdf           ← Guía de 1 página para el directivo
```

> ⚠️ Verificar que `.env` real **NO** está en el ZIP (solo `.env.example`).

---

## Instalación en Windows (WAMP)

### Prerrequisito: Instalar WAMP

1. Descargar WAMP desde [wampserver.com](https://www.wampserver.com)
2. Instalar con siguiente → siguiente → finalizar (~10 minutos)
3. Iniciar WAMP — el ícono en la barra de tareas debe ponerse **verde**

> Solo se hace una vez. Las próximas versiones del SGEP no requieren reinstalar WAMP.

### Instalación del SGEP

```
1. Descomprimir SGEP_v1.0.zip en el escritorio

2. Copiar la carpeta "sgep/" a:
   C:\wamp64\www\sgep\

3. Abrir phpMyAdmin: http://localhost/phpmyadmin
   → Nueva base de datos
   → Nombre: sgep
   → Cotejamiento: utf8mb4_unicode_ci
   → Crear

4. Ir a la carpeta C:\wamp64\www\sgep\

5. Hacer doble clic en "instalar.bat"
   → Esperar a que aparezca "INSTALACION COMPLETADA"

6. Abrir el navegador:
   → http://localhost/sgep/public
```

---

## Instalación en macOS (Herd)

### Prerrequisito: Instalar Herd y DBngin

```bash
# 1. Instalar Laravel Herd desde https://herd.laravel.com
#    Seguir el wizard de instalación

# 2. Instalar DBngin desde https://dbngin.com
#    → Crear un servidor MySQL 8.0
#    → Iniciar el servidor

# 3. Crear la base de datos con TablePlus o desde terminal:
mysql -u root -e "CREATE DATABASE sgep CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
```

### Instalación del SGEP

```bash
# 1. Descomprimir el ZIP y mover la carpeta a la ruta de Herd
mv sgep/ ~/Herd/sgep/

# 2. Entrar a la carpeta
cd ~/Herd/sgep/

# 3. Copiar y configurar .env
cp .env.example .env
# Editar .env: cambiar APP_URL=http://sgep.test

# 4. Generar clave y migrar
php artisan key:generate
php artisan migrate --force

# 5. Acceder en el navegador:
# http://sgep.test
```

---

## Actualizaciones

Cuando el equipo lanza una nueva versión del SGEP:

### En Windows (WAMP)

```
1. Descargar el nuevo ZIP

2. Reemplazar el contenido de C:\wamp64\www\sgep\
   (conservar el .env actual — NO sobreescribir)

3. Hacer doble clic en "actualizar.bat"
```

### En macOS (Herd)

```bash
# Reemplazar archivos (excepto .env)
rsync -av --exclude='.env' nueva-version/ ~/Herd/sgep/

# Aplicar migraciones nuevas
cd ~/Herd/sgep/
php artisan migrate

# Limpiar caché
php artisan config:clear && php artisan config:cache
```

> ✅ Los datos ya guardados (aprendices, momentos, evaluaciones) se conservan intactos. `php artisan migrate` solo aplica las tablas o columnas nuevas.

---

## Comandos de referencia

### Desarrollo (equipo)

```bash
# Instalar dependencias
composer install

# Instalar con optimización para distribución
composer install --no-dev --optimize-autoloader

# Compilar assets en modo desarrollo (con hot-reload)
npm run dev

# Compilar assets para distribución
npm run build

# Correr migraciones
php artisan migrate

# Correr migraciones + seeders
php artisan migrate --seed

# Revertir última migración
php artisan migrate:rollback

# Ver estado de migraciones
php artisan migrate:status

# Refrescar todo (⚠️ borra todos los datos)
php artisan migrate:fresh --seed
```

### Primera instalación (en PC del directivo)

```bash
copy .env.example .env               # Crear archivo de configuración
php artisan key:generate             # Generar clave única
php artisan migrate --force          # Crear tablas en MySQL
```

### Actualizaciones

```bash
php artisan migrate                  # Aplicar migraciones nuevas
php artisan config:clear             # Limpiar caché de configuración
php artisan config:cache             # Regenerar caché
php artisan route:cache              # Regenerar caché de rutas
php artisan view:clear               # Limpiar caché de vistas
```

### Debugging (solo en desarrollo)

```bash
php artisan tinker                   # REPL interactivo de Laravel
php artisan route:list               # Ver todas las rutas registradas
php artisan about                    # Información del entorno
tail -f storage/logs/laravel.log     # Ver logs en tiempo real
```

---

## Solución de problemas

### La página muestra "403 Forbidden" en WAMP

```
Causa: Apache no puede acceder a la carpeta.
Solución: Clic derecho en el ícono WAMP → Apache → httpd.conf
          Buscar: AllowOverride None
          Cambiar a: AllowOverride All
          Reiniciar WAMP.
```

### La página muestra "500 | Server Error"

```
Causa: Error de PHP o configuración incorrecta.
Solución: Revisar storage/logs/laravel.log para ver el error exacto.
          Asegurarse de que el .env tiene APP_KEY generada.
```

### "php artisan" no se reconoce como comando (Windows)

```
Causa: PHP no está en el PATH del sistema.
Solución: Abrir la terminal desde la carpeta de WAMP:
          C:\wamp64\bin\php\php8.2.x\php.exe artisan migrate
          O agregar PHP al PATH del sistema.
```

### La base de datos no conecta

```
Causa: MySQL no está corriendo o las credenciales son incorrectas.
Solución: Verificar que WAMP está activo (ícono verde).
          Revisar en .env:
          DB_HOST=127.0.0.1
          DB_DATABASE=sgep
          DB_USERNAME=root
          DB_PASSWORD=           ← vacío en WAMP por defecto
```

### Los estilos CSS no cargan

```
Causa: La carpeta public/build/ no está incluida en el ZIP.
Solución: El equipo debe correr "npm run build" antes de empaquetar.
          Verificar que public/build/manifest.json existe en el ZIP.
```

---

*Ver también: [README.md](../README.md) · [ARQUITECTURA.md](./ARQUITECTURA.md)*