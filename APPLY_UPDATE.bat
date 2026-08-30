@echo off
setlocal
cd /d "%~dp0"
echo [CGOne] Applying master workspace update...
php artisan migrate --force && php artisan optimize:clear
if errorlevel 1 (
  echo.
  echo UPDATE FAILED. Check the error above.
  pause
  exit /b 1
)
echo.
echo UPDATE COMPLETE.
pause
