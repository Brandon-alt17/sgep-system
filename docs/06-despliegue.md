## Despliegue y distribución — SGEP

**Documento:** Proceso de empaquetado y distribución a directivos  
**Versión:** 1.0

---

### Índice

- [Entornos de ejecución](#entornos-de-ejecución)
- [Proceso de empaquetado](#proceso-de-empaquetado)
- [Instalación en Windows (WAMP)](#instalación-en-windows-wamp)
- [Instalación en macOS (MAMP)](#instalación-en-macos-mamp)
- [Actualizaciones](#actualizaciones)
- [Comandos de referencia](#comandos-de-referencia)
- [Solución de problemas](#solución-de-problemas)

---

## Entornos de ejecución

El SGEP es una aplicación web **100% local**. No requiere internet para funcionar. Cada instalación es independiente y sus datos solo existen en ese PC.

> **Nota:** Entorno recomendado oficial: **WAMP (Windows)** y **MAMP (macOS)**. **XAMPP** puede usarse como alternativa en Windows si el directivo ya lo tiene instalado.

| Componente | Qué provee | Quién lo instala |
|-----------|-----------|-----------------|
| **WAMP** (Windows) | PHP 8.3+, MySQL 8.0+, Apache | El directivo, una sola vez |
| **MAMP** (macOS) | PHP 8.3+, MySQL 8.0+, Apache | El directivo, una sola vez |
| **Carpeta del SGEP** | Código PHP + dependencias + assets | El equipo, en cada versión |
| **Base de datos MySQL** | Tablas vacías o con datos | El script de instalación o migraciones |

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

> Esto genera `vendor/` con librerías PHP y `public/build/` con assets compilados. El directivo no necesita Composer ni Node.js.

### Paso 2 — Verificar configuración

- Confirmar que `.env.example` tiene variables correctas.
- Confirmar que `database/run_migrations.php` existe en el paquete.

### Paso 3 — Preparar el archivo `.env.example`

Verificar que `.env.example` tiene los valores correctos para WAMP:

```env
APP_NAME=SGEP
APP_ENV=local
APP_KEY=
APP_DEBUG=false
APP_URL=http://localhost/sgep/public

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=sgep
DB_USERNAME=root
DB_PASSWORD=                # Vacío en WAMP por defecto

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

echo [2/3] Ejecutando migraciones...
php database/run_migrations.php
if %errorlevel% neq 0 (
  echo ERROR: Fallaron las migraciones.
  pause
  exit /b 1
)
echo      Base de datos lista.

echo [3/3] Finalizando instalacion...
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
php database/run_migrations.php

echo [2/2] Finalizando...

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
│   ├── config/
│   ├── docs/
│   ├── storage/
│   ├── vendor/                 ← INCLUIDA (dependencias PHP)
│   ├── .env.example
│   ├── router.php
│   └── database/run_migrations.php
├── instalar.bat                ← Script de primera instalación
├── actualizar.bat              ← Script de actualizaciones
└── INSTRUCCIONES.pdf           ← Guía de 1 página para el directivo
```

> Verificar que `.env` real **NO** está en el ZIP (solo `.env.example`).

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

## Instalación en macOS (MAMP)

### Prerrequisito: Instalar MAMP

1. Descargar **MAMP** desde [mamp.info](https://www.mamp.info) e instalarlo.
2. Iniciar MAMP y poner **Apache** y **MySQL** en verde.
3. Por defecto, el sitio web sirve desde `http://localhost:8888` y la carpeta de documentos suele ser `/Applications/MAMP/htdocs/`.

**Crear la base de datos** (terminal o cliente MySQL; el puerto puede ser `8889` en MAMP):

```bash
# Ajusta -P si tu MySQL usa otro puerto (MAMP suele usar 8889)
mysql -u root -p -P 8889 -e "CREATE DATABASE sgep CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
```

### Instalación del SGEP

```bash
# 1. Descomprimir el ZIP y colocar la carpeta del proyecto en htdocs
#    Ejemplo: /Applications/MAMP/htdocs/sgep/

# 2. Entrar a la carpeta del proyecto
cd /Applications/MAMP/htdocs/sgep

# 3. Copiar y configurar .env
cp .env.example .env
# Editar .env: APP_URL=http://localhost:8888/sgep/public (ajusta la ruta si cambia el puerto)

# 4. Ejecutar migraciones
php database/run_migrations.php

# 5. Abrir en el navegador (ejemplo con puerto 8888):
# http://localhost:8888/sgep/public
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

### En macOS (MAMP)

```bash
# Reemplazar archivos (excepto .env); ajusta la ruta a tu instalación
rsync -av --exclude='.env' nueva-version/ /Applications/MAMP/htdocs/sgep/

cd /Applications/MAMP/htdocs/sgep
php database/run_migrations.php
```

> Los datos ya guardados se conservan. `php database/run_migrations.php` aplica migraciones SQL nuevas.

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
php database/run_migrations.php
```

### Primera instalación (en PC del directivo)

```bash
copy .env.example .env               # Crear archivo de configuración
php database/run_migrations.php      # Crear/actualizar tablas en MySQL
```

### Actualizaciones

```bash
php database/run_migrations.php      # Aplicar migraciones nuevas
```

### Debugging (solo en desarrollo)

```bash
php -S localhost:8000 -t public
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
Solución: Revisar logs de PHP/Apache (WAMP o MAMP) y validar el archivo .env.
```

### "php" no se reconoce como comando (Windows)

```
Causa: PHP no está en el PATH del sistema.
Solución: Abrir la terminal desde la carpeta de WAMP:
          C:\wamp64\bin\php\php8.3.x\php.exe database\run_migrations.php
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

*Ver también: [README.md](../README.md) · [Arquitectura](./architecture.md)*