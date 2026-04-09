@echo off
setlocal
echo === Actualizacion SGEP ===
php database/run_migrations.php
echo Actualizacion finalizada.
pause
