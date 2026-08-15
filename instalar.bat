@echo off
setlocal EnableDelayedExpansion
echo === Instalador SGEP (PHP puro) ===

call "%~dp0scripts\php.cmd" --version >nul 2>&1
if %errorlevel% neq 0 (
  echo ERROR: No se encontro PHP 8.3+ en PATH ni en WAMP.
  pause
  exit /b 1
)

if not exist .env (
  copy .env.example .env >nul
  echo Archivo .env creado desde .env.example
)

set /p DBPASS=Ingrese la contrasena de MySQL (root, enter si vacia): 

call "%~dp0scripts\configure_env_wamp.bat" "%DBPASS%"
if %errorlevel% neq 0 (
  echo ERROR: No se pudo configurar .env para WAMP.
  pause
  exit /b 1
)

call "%~dp0scripts\php.cmd" -r "require 'vendor/autoload.php';"
if %errorlevel% neq 0 (
  echo ERROR: No se pudo cargar autoload de Composer.
  pause
  exit /b 1
)

echo Creando base de datos sgep (si no existe)...
call "%~dp0scripts\php.cmd" database/ensure_database.php
if %errorlevel% neq 0 (
  echo ERROR: No se pudo crear la base de datos. Verifique WAMP en verde.
  pause
  exit /b 1
)

call "%~dp0scripts\php.cmd" database/run_migrations.php
if %errorlevel% neq 0 (
  echo ERROR: Fallaron las migraciones.
  pause
  exit /b 1
)

call "%~dp0scripts\php.cmd" database/seed_sample_if_empty.php
if %errorlevel% neq 0 (
  echo AVISO: No se pudo cargar el aprendiz de ejemplo.
)

:done
echo.
echo Instalacion finalizada.
echo.
echo Abra el SGEP en el navegador:
echo   http://localhost/sgep/
echo.
echo Atajo: doble clic en abrir_sgep.bat
echo        o copie SGEP.url al escritorio.
echo.
set /p OPEN=Abrir en el navegador ahora? (S/N):
if /i "%OPEN%"=="S" start "" "http://localhost/sgep/"
pause
