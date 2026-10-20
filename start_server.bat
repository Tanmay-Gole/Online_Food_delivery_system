@echo off
title FoodieExpress - Online Food Ordering System
echo ===================================================
echo   FoodieExpress - Online Food Ordering System
echo   Developer: Tanmay Gole (2430030098)
echo   Manipal University Jaipur, BTech IT
echo ===================================================
echo.
echo Starting local web server at http://127.0.0.1:8080 ...
echo Press Ctrl+C anytime to stop the server.
echo.

cd /d "%~dp0"
start http://127.0.0.1:8080

php -S 127.0.0.1:8080
if %ERRORLEVEL% NEQ 0 (
    echo.
    echo Trying fallback PHP path...
    "%LOCALAPPDATA%\Microsoft\WinGet\Packages\PHP.PHP.8.2_Microsoft.Winget.Source_8wekyb3d8bbwe\php.exe" -S 127.0.0.1:8080
)
pause
