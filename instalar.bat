@echo off
setlocal
echo === Instalador SGEP (PHP puro) ===

if not exist .env (
  copy .env.example .env >nul
  echo Archivo .env creado desde .env.example
)

set /p DBPASS=Ingrese la contrasena de MySQL (root): 

php -r "require 'vendor/autoload.php';"
php database/run_migrations.php

if exist database\seeds\aprendices_sample.sql (
  mysql -u root -p%DBPASS% sgep < database\seeds\aprendices_sample.sql
)

echo Instalacion finalizada.
pause
