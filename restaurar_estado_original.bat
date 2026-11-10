@echo off
chcp 65001 >nul
echo ========================================================
echo Restaurando archivos originales de Moratalla-Murcia-2026
echo ========================================================
c:\xampp_2023\php\php.exe "%~dp0restaurar_estado_original.php"
echo.
echo Proceso completado. La web ha vuelto exactamente a su estado previo.
pause
