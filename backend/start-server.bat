@echo off
echo ========================================
echo Starting Laravel Backend Server
echo ========================================
echo.
echo Using PHP built-in server (bypasses Laravel serve command)
echo Server will be available at: http://localhost:5001
echo.
echo Press Ctrl+C to stop the server
echo ========================================
echo.
cd /d %~dp0
php -S localhost:5001 -t public server.php
pause
