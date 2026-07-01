## Despliegue y distribución — SGEP

**Documento:** Proceso de empaquetado y distribución a directivos  
**Versión:** 1.0

---

### Índice

- [Entornos de ejecución](#entornos-de-ejecución)
- [Guía de instalación para el cliente](#guía-de-instalación-para-el-cliente)
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

## Guía de instalación para el cliente

Documento principal para entrega en **memoria USB**, con alcance/limitaciones, capturas (`docs/imagenes/`) y solución de problemas (WAMP sin servicios verdes, MAMP `mod_rewrite`, etc.):

**[GUIA-INSTALACION-ENTREGA.md](./GUIA-INSTALACION-ENTREGA.md)**

Incluir en la USB:

- `LEEME.txt` (raíz de la memoria)
- `01_WAMP/` — instalador WAMP + `vcredist_*.exe`
- `02_LibreOffice/` — instalador LibreOffice (opcional)
- `03_SGEP/sgep/` — proyecto completo
- `04_MAMP/MAMP-MAMP-PRO-Downloader.zip` — instalador MAMP

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

**`abrir_sgep.bat`** (abrir la aplicación en el navegador):

```bat
@echo off
set "SGEP_URL=http://localhost/sgep/public"
start "" "%SGEP_URL%"
```

Doble clic después de instalar. Si WAMP no está en verde, la página no cargará; el icono WAMP debe estar activo.

**`SGEP.url`** — acceso directo de Windows (Internet Shortcut). Copiar al escritorio o anclar a la barra de tareas; al hacer doble clic abre `http://localhost/sgep/public` en el navegador predeterminado.

### Paso 5 — Armar la memoria USB

Estructura de la **memoria USB** de distribución:

```
USB/
├── LEEME.txt
├── 01_WAMP/
│   ├── (instalador WAMP)
│   ├── vcredist_2010_sp1_x64.exe
│   ├── vcredist_2010_sp1_x86.exe
│   ├── vcredist_2012_upd4_x64.exe
│   ├── vcredist_2012_upd4_x86.exe
│   ├── vcredist_2013_upd5_x64.exe
│   ├── vcredist_2013_upd5_x86.exe
│   ├── vcredist_V14_x64.exe
│   └── vcredist_V14_x86.exe
├── 02_LibreOffice/
│   └── (instalador LibreOffice)
├── 03_SGEP/
│   └── sgep/                   ← Proyecto completo (ver abajo)
└── 04_MAMP/
    └── MAMP-MAMP-PRO-Downloader.zip
```

Contenido mínimo de **`03_SGEP/sgep/`**:

```
sgep/
├── app/
├── database/
├── public/
├── config/
├── docs/
├── storage/
├── vendor/
├── .env.example
├── router.php
├── instalar.bat
├── actualizar.bat
├── abrir_sgep.bat
├── SGEP.url
├── LEEME.txt
└── scripts/macos/
```

> Verificar que `.env` real **NO** está en la USB (solo `.env.example`).

**Actualizaciones:** en entregas posteriores basta con reemplazar **`03_SGEP/sgep/`**; el directivo conserva `.env` en su PC local y ejecuta `actualizar.bat` o `actualizar.sh`.

---

## Instalación en Windows (WAMP)

### Prerrequisito: Instalar WAMP

1. Abrir **`01_WAMP/`** en la USB.
2. Instalar **todos** los `vcredist_*.exe` (Visual C++).
3. Ejecutar el instalador de WAMP en la misma carpeta (~10 minutos).
4. Iniciar WAMP — el ícono en la bandeja de tareas debe ponerse **verde**.

> Solo se hace una vez. Las próximas versiones del SGEP solo actualizan **`03_SGEP/`**.

### Instalación del SGEP

```
1. Abrir 03_SGEP/ en la USB

2. Copiar la carpeta "sgep/" a:
   C:\wamp64\www\sgep\

3. (Opcional) phpMyAdmin: http://localhost/phpmyadmin → crear BD "sgep"
   El script instalar.bat también puede crearla.

4. Doble clic en C:\wamp64\www\sgep\instalar.bat
   → Responder S para abrir el navegador, o usar abrir_sgep.bat / SGEP.url

5. Abrir: http://localhost/sgep/
```

**Uso diario:** WAMP en verde → doble clic en `SGEP.url` o `abrir_sgep.bat` (no hace falta volver a ejecutar `instalar.bat`).

> **Exportación PDF:** instale LibreOffice desde **`02_LibreOffice/`** en la USB (ver [LibreOffice para exportar PDF](#libreoffice-para-exportar-pdf-opcional)). Word (.docx) funciona sin LibreOffice.

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

1. Abrir **`04_MAMP/`** en la USB.
2. Descomprimir **`MAMP-MAMP-PRO-Downloader.zip`** e instalar MAMP.
3. Iniciar MAMP → Apache y MySQL en verde.
4. Activar `mod_rewrite` en `/Applications/MAMP/conf/apache/httpd.conf` (ver [GUIA-INSTALACION-ENTREGA.md §6.2](./GUIA-INSTALACION-ENTREGA.md)).

### Instalación del SGEP

```bash
# 1. Copiar desde 03_SGEP/sgep/ a htdocs
#    /Applications/MAMP/htdocs/sgep/

# 2. Instalar
cd /Applications/MAMP/htdocs/sgep/scripts/macos
bash instalar.sh

# 3. Abrir: http://localhost:8888/sgep/
```

> **Exportación PDF:** instalar LibreOffice en el Mac y verificar con `php scripts/check_pdf_converter.php` ([detalle](#libreoffice-para-exportar-pdf-opcional)).

---

## Actualizaciones

Cuando el equipo lanza una nueva versión del SGEP:

### En Windows (WAMP)

```
1. Recibir USB con 03_SGEP/sgep/ actualizado

2. Copiar sobre C:\wamp64\www\sgep\ (conservar .env)

3. Doble clic en actualizar.bat
```

### En macOS (MAMP)

```bash
# Copiar desde 03_SGEP/sgep/ (conservar .env)
cd /Applications/MAMP/htdocs/sgep/scripts/macos
bash actualizar.sh
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