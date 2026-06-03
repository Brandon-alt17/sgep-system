## Despliegue y distribución — SGEP

**Documento:** Proceso de empaquetado y distribución a directivos  
**Versión:** 1.0

---

### Índice

- [Entornos de ejecución](#entornos-de-ejecución)
- [Proceso de empaquetado](#proceso-de-empaquetado)
- [Instalación en Windows (WAMP)](#instalación-en-windows-wamp)
- [LibreOffice para exportar PDF (opcional)](#libreoffice-para-exportar-pdf-opcional)
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
| **LibreOffice** (opcional) | Exportación F-023 a PDF | El directivo o el equipo, si se usa PDF |
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
npm run build:css
```

> Esto genera `vendor/` con librerías PHP y `public/css/app.css` minificado. El directivo no necesita Composer ni Node.js.

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

# Exportación F-023 a PDF (LibreOffice headless)
# Ruta absoluta a soffice.exe; vacío = rutas típicas de instalación (ver sección LibreOffice)
F023_LIBREOFFICE_PATH=
# Solo desarrollo sin LibreOffice: true permite fallback Dompdf (baja fidelidad). Producción: false
F023_PDF_ALLOW_DOMPDF_FALLBACK=false

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

> **Exportación PDF:** solo si el directivo va a generar F-023 en PDF, instale LibreOffice en el mismo PC (ver [LibreOffice para exportar PDF](#libreoffice-para-exportar-pdf-opcional)). Word (.docx) funciona sin LibreOffice.

---

## LibreOffice para exportar PDF (opcional)

LibreOffice **no viene** con WAMP ni MAMP. Solo hace falta si se usa la opción **PDF** al generar el GFPI-F-023. La exportación **Word (.docx)** no lo requiere.

### Paso a paso (Windows)

1. **Descargar** el instalador desde [https://www.libreoffice.org/download](https://www.libreoffice.org/download) (versión Windows, 64 bits).
2. **Ejecutar el instalador** hasta finalizar (no basta con tener el archivo `.msi` o `.exe` descargado).
3. **Comprobar** que existe el ejecutable (Explorador de archivos → buscar `soffice.exe`). Ruta habitual:
   ```
   C:\Program Files\LibreOffice\program\soffice.exe
   ```
4. **Verificar desde la carpeta del SGEP** (con WAMP en verde):
   ```bat
   cd C:\wamp64\www\sgep
   php scripts\check_pdf_converter.php
   ```
   Debe mostrar: `OK: motor PDF disponible.`
5. En el SGEP, ir a **Documentos → Generar F-023**. La alerta amarilla *“La exportación PDF requiere LibreOffice…”* **no debe aparecer** y la opción PDF debe estar habilitada.

### ¿Hay que editar el `.env`?

| Situación | ¿Configurar `.env`? |
|-----------|----------------------|
| Instalación estándar en `C:\Program Files\LibreOffice\...` | **No** — SGEP la detecta solo |
| Instalación en otra carpeta (portable, unidad `D:\`, etc.) | **Sí** — ver abajo |
| Copia incluida en el proyecto en `tools/libreoffice/` | **No** — SGEP la busca ahí |

Si hace falta indicar la ruta manualmente, editar `.env`:

```env
F023_LIBREOFFICE_PATH=C:\Program Files\LibreOffice\program\soffice.exe
```

(Ajustar la ruta real si LibreOffice está en otro sitio.)

**Después de cambiar `.env`:** reiniciar WAMP (Apache) para que PHP cargue la variable.

> En Windows, SGEP **no usa el PATH del sistema**; busca rutas fijas conocidas o la variable `F023_LIBREOFFICE_PATH`.

### Paso a paso (macOS)

1. Descargar e instalar LibreOffice desde [libreoffice.org](https://www.libreoffice.org).
2. Ruta habitual del ejecutable:
   ```
   /Applications/LibreOffice.app/Contents/MacOS/soffice
   ```
3. Si la detección automática falla, en `.env`:
   ```env
   F023_LIBREOFFICE_PATH=/Applications/LibreOffice.app/Contents/MacOS/soffice
   ```
4. Verificar: `php scripts/check_pdf_converter.php` desde la carpeta del proyecto.

### Copia portable (equipo de desarrollo)

Opcionalmente se puede empaquetar LibreOffice en `tools/libreoffice/` dentro del ZIP (~300 MB). SGEP lo detecta sin configurar `.env`. No es obligatorio para el directivo si instala LibreOffice normalmente.

### Variable de respaldo (solo desarrollo)

`F023_PDF_ALLOW_DOMPDF_FALLBACK=true` permite generar PDF sin LibreOffice con calidad inferior. **En producción debe permanecer en `false`.**

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

> **Exportación PDF:** instalar LibreOffice en el Mac y verificar con `php scripts/check_pdf_converter.php` ([detalle](#libreoffice-para-exportar-pdf-opcional)).

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

# Observar cambios de CSS en desarrollo
npm run watch:css

# Compilar CSS para distribución
npm run build:css

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
Causa: El archivo public/css/app.css no está actualizado o no está incluido en el ZIP.
Solución: El equipo debe correr "npm run build:css" antes de empaquetar.
          Verificar que public/css/app.css existe en el ZIP.
```

### Aparece la alerta “La exportación PDF requiere LibreOffice…”

```
Causa: SGEP no encuentra soffice.exe en el PC donde corre Apache/PHP.
Solución:
  1. Confirmar que LibreOffice está INSTALADO (no solo descargado el instalador).
  2. Buscar soffice.exe y comprobar la ruta (Windows: C:\Program Files\LibreOffice\program\soffice.exe).
  3. Si está en otra carpeta, añadir en .env:
     F023_LIBREOFFICE_PATH=C:\ruta\completa\a\program\soffice.exe
     Reiniciar WAMP/MAMP después de guardar .env.
  4. Ejecutar: php scripts/check_pdf_converter.php
     → debe responder "OK: motor PDF disponible."
  5. Recargar la página de generar documento.
```

### La exportación PDF del F-023 falla al generar

```
Causa: LibreOffice está instalado pero la conversión headless falló (permisos, ruta incorrecta, antivirus).
Solución:
  1. Repetir php scripts/check_pdf_converter.php desde la carpeta del proyecto.
  2. Verificar F023_LIBREOFFICE_PATH en .env (ruta exacta al .exe, sin comillas).
  3. Probar exportar solo Word (.docx); si Word funciona, el problema es solo LibreOffice/PDF.
  4. Revisar logs de PHP/Apache por mensajes "F023 PDF LibreOffice".
  5. Alternativa: exportar .docx y guardar como PDF desde LibreOffice Writer o Word.
```

### Instalé LibreOffice pero SGEP sigue sin detectarlo

```
Causas frecuentes:
  • Solo se descargó el instalador, no se ejecutó hasta el final.
  • LibreOffice portable o en carpeta personalizada → configurar F023_LIBREOFFICE_PATH.
  • Se editó .env pero no se reinició Apache (WAMP/MAMP).
  • PHP de la terminal y PHP de Apache son distintos → probar el script desde la misma
    carpeta del proyecto con el php de WAMP:
    C:\wamp64\bin\php\php8.3.x\php.exe scripts\check_pdf_converter.php
```

### Linux (servidor o desarrollo en Arch/Ubuntu)

```
Rutas que SGEP prueba automáticamente: /usr/bin/libreoffice, /usr/bin/soffice, etc.
Instalación ejemplo (Arch): sudo pacman -S libreoffice-fresh
Si no se detecta: F023_LIBREOFFICE_PATH=/usr/bin/libreoffice
Verificar: php scripts/check_pdf_converter.php
```

---

*Ver también: [README.md](../README.md) · [Arquitectura](./architecture.md)*