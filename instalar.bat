@echo off
setlocal
echo === Instalador SGEP (PHP puro) ===

php --version >nul 2>&1
if %errorlevel% neq 0 (
  echo ERROR: No se encontro PHP en PATH.
  pause
  exit /b 1
)

if not exist .env (
  copy .env.example .env >nul
  echo Archivo .env creado desde .env.example
)

set /p DBPASS=Ingrese la contrasena de MySQL (root, enter si vacia): 

php -r "require 'vendor/autoload.php';"
if %errorlevel% neq 0 (
  echo ERROR: No se pudo cargar autoload de Composer.
  pause
  exit /b 1
)

php database/run_migrations.php
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
echo.
echo Instalacion finalizada.
echo.
echo Abra el SGEP en el navegador:
echo   http://localhost/sgep/public
echo.
echo Atajo: doble clic en abrir_sgep.bat
echo        o copie SGEP.url al escritorio.
echo.
set /p OPEN=Abrir en el navegador ahora? (S/N):
if /i "%OPEN%"=="S" start "" "http://localhost/sgep/public"
pause
