# SGEP en macOS (MAMP) — Guía rápida

> **Guía completa (USB, alcance, errores WAMP/MAMP, placeholders de imágenes):**  
> [docs/GUIA-INSTALACION-ENTREGA.md](../../docs/GUIA-INSTALACION-ENTREGA.md)

## Archivos en esta carpeta

| Archivo | Uso |
|---------|-----|
| `instalar.sh` | Primera instalación (BD + migraciones) |
| `actualizar.sh` | Después de copiar una versión nueva del SGEP |
| `abrir_sgep.sh` | Abrir el SGEP en el navegador cada día |
| `INSTRUCCIONES-MAC.md` | Esta guía |

## Copiar solo estos scripts en la memoria USB

Copie **toda la carpeta** `scripts/macos/` a la USB:

```
USB/
└── macos/
    ├── instalar.sh
    ├── actualizar.sh
    ├── abrir_sgep.sh
    └── INSTRUCCIONES-MAC.md
```

En el Mac del directivo, pegue los `.sh` dentro de la carpeta del proyecto:

```
/Applications/MAMP/htdocs/sgep/
├── instalar.sh      ← pegados aquí
├── actualizar.sh
├── abrir_sgep.sh
└── ... (resto del proyecto)
```

> También pueden ejecutarse **sin moverlos** desde `scripts/macos/` si el proyecto completo ya está instalado.

## Requisito previo (una sola vez)

1. Instalar **MAMP** desde [mamp.info](https://www.mamp.info)
2. **Activar reescritura de URLs en Apache** (obligatorio para que funcionen `/dashboard`, `/aprendices`, etc.):
   1. Cierre MAMP (Stop).
   2. Abra `/Applications/MAMP/conf/apache/httpd.conf` (TextEdit u otro editor).
   3. Busque `rewrite_module` y deje la línea **sin** `#` al inicio:
      ```apache
      LoadModule rewrite_module modules/mod_rewrite.so
      ```
   4. Busque el bloque `<Directory "/Applications/MAMP/htdocs">` y confirme:
      ```apache
      AllowOverride All
      ```
      (Si dice `AllowOverride None`, cámbielo a `All`.)
   5. Guarde el archivo y vuelva a abrir MAMP → **Start**.
3. Iniciar MAMP → Apache y MySQL en verde

## Si copió los scripts desde Windows (USB)

Error `env: bash\r: No such file or directory` → en Terminal:

```bash
cd /Applications/MAMP/htdocs/sgep/scripts/macos
sed -i '' 's/\r$//' instalar.sh actualizar.sh abrir_sgep.sh
bash instalar.sh
```

## Archivo `.env` no visible en Finder

En Mac los archivos que empiezan con `.` están ocultos. Use **Cmd + Shift + .** en Finder, o en Terminal:

```bash
cd /Applications/MAMP/htdocs/sgep
cp scripts/macos/env.ejemplo.mamp .env
```

(`env.ejemplo.mamp` es la plantilla visible para MAMP, sin punto al inicio.)

## Primera instalación

```bash
# 1. Colocar el proyecto en htdocs
#    /Applications/MAMP/htdocs/sgep/

# 2. Corregir scripts si vienen de USB Windows (ver arriba)

# 3. Instalar
cd /Applications/MAMP/htdocs/sgep/scripts/macos
bash instalar.sh
```

4. El script `instalar.sh` configura `.env` para MAMP automáticamente. Si Firefox muestra **Connection refused**, edite `.env` manualmente:

```env
APP_URL=http://localhost:8888/sgep
APP_BASE_PATH=/sgep
DB_PORT=8889
DB_PASSWORD=root
```

5. Abrir: **http://localhost:8888/sgep/**

6. Compruebe que las rutas internas cargan (no solo la portada):
   - **http://localhost:8888/sgep/dashboard**
   - **http://localhost:8888/sgep/aprendices**

## Error 404 en `/dashboard` u otras rutas

La portada (`/sgep/`) puede abrir, pero rutas como `/sgep/dashboard` devuelven **404 de Apache** si el módulo `rewrite` está desactivado (línea comentada con `#` en `httpd.conf`).

**Solución:** active `mod_rewrite` y `AllowOverride All` como se indica en [Requisito previo](#requisito-previo-una-sola-vez), reinicie MAMP y recargue el navegador (`Cmd + Shift + R`).

## Uso diario

1. MAMP en verde
2. Terminal: `./abrir_sgep.sh`  
   O crear un alias en el Dock apuntando a esa URL.

## Actualizar versión

```bash
# Reemplazar archivos del proyecto (NO borrar .env)
./actualizar.sh
```

## PDF (opcional)

Instale LibreOffice y en `.env`:

```env
F023_LIBREOFFICE_PATH=/Applications/LibreOffice.app/Contents/MacOS/soffice
```

Verificar:

```bash
ls /Applications/MAMP/bin/php/
/Applications/MAMP/bin/php/php8.3.30/bin/php scripts/check_pdf_converter.php
```

(Ajuste la carpeta de versión según el resultado de `ls`; por ejemplo `php8.3.30`, `php8.4.17`, etc.)
