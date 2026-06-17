@echo off
setlocal
echo === Actualizacion SGEP ===

set "PHP_EXE=php"
php --version >nul 2>&1
if %errorlevel% neq 0 (
  if exist "C:\wamp64\bin\php\php8.3.28\php.exe" set "PHP_EXE=C:\wamp64\bin\php\php8.3.28\php.exe"
  if exist "C:\wamp64\bin\php\php8.2.29\php.exe" set "PHP_EXE=C:\wamp64\bin\php\php8.2.29\php.exe"
  if exist "C:\wamp64\bin\php\php8.4.15\php.exe" set "PHP_EXE=C:\wamp64\bin\php\php8.4.15\php.exe"
)

"%PHP_EXE%" --version >nul 2>&1
if %errorlevel% neq 0 (
  echo ERROR: No se encontro PHP. Verifique que WAMP este en verde.
  pause
  exit /b 1
)

"%PHP_EXE%" database/run_migrations.php
if %errorlevel% neq 0 (
  echo ERROR: Fallaron las migraciones durante la actualizacion.
  pause
  exit /b 1
)

echo Actualizacion finalizada.
pause
