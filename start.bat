@echo off
echo Starting School Queueing System...
echo.
echo This will open 3 terminals:
echo   1. Laravel Reverb (WebSocket server)
echo   2. Queue Worker (broadcasting)
echo   3. Laravel Dev Server
echo.
pause

start "Reverb Server" cmd /k "cd /d %~dp0 && php artisan reverb:start"
timeout /t 2 /nobreak >nul
start "Queue Worker" cmd /k "cd /d %~dp0 && php artisan queue:work"
timeout /t 2 /nobreak >nul
start "Laravel Server" cmd /k "cd /d %~dp0 && php artisan serve"

echo.
echo All services started!
echo.
echo Open in browser:
echo   Kiosk:    http://localhost:8000/kiosk
echo   Monitor:  http://localhost:8000/monitor
echo   Counter:  http://localhost:8000/counter
echo.
pause
