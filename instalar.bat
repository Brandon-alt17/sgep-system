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

set "MYSQL_EXE=mysql"
mysql --version >nul 2>&1
if %errorlevel% neq 0 (
  if exist "C:\wamp64\bin\mysql\mysql8.4.7\bin\mysql.exe" set "MYSQL_EXE=C:\wamp64\bin\mysql\mysql8.4.7\bin\mysql.exe"
  if exist "C:\wamp64\bin\mariadb\mariadb11.4.9\bin\mysql.exe" set "MYSQL_EXE=C:\wamp64\bin\mariadb\mariadb11.4.9\bin\mysql.exe"
)

if exist database\seeds\aprendices_sample.sql (
  "%MYSQL_EXE%" --version >nul 2>&1
  if %errorlevel% neq 0 (
    echo AVISO: MySQL CLI no disponible; se omite carga de seed.
    goto :done
  )
  "%MYSQL_EXE%" -u root -p%DBPASS% sgep < database\seeds\aprendices_sample.sql
  if %errorlevel% neq 0 (
    echo AVISO: No se pudo cargar seed opcional de aprendices.
  )
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
