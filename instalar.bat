@echo off
setlocal
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

call "%~dp0scripts\php.cmd" -r "require 'vendor/autoload.php';"
if %errorlevel% neq 0 (
  echo ERROR: No se pudo cargar autoload de Composer.
  pause
  exit /b 1
)

call "%~dp0scripts\php.cmd" database/run_migrations.php
if %errorlevel% neq 0 (
  echo ERROR: Fallaron las migraciones.
  pause
  exit /b 1
)

if exist database\seeds\aprendices_sample.sql (
  mysql --version >nul 2>&1
  if %errorlevel% neq 0 (
    echo AVISO: MySQL CLI no disponible; se omite carga de seed.
    goto :done
  )
  mysql -u root -p%DBPASS% sgep < database\seeds\aprendices_sample.sql
  if %errorlevel% neq 0 (
    echo AVISO: No se pudo cargar seed opcional de aprendices.
  )
)

:done
echo Instalacion finalizada.
pause
