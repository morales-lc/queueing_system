@echo off
echo ========================================
echo Receipt Print Server - Windows 11
echo ========================================
echo.

REM Check if Node.js is installed
where node >nul 2>nul
if %ERRORLEVEL% NEQ 0 (
    echo [ERROR] Node.js is not installed!
    echo Please download and install Node.js from: https://nodejs.org/
    echo.
    pause
    exit /b 1
)

echo [OK] Node.js is installed
node --version
echo.

REM Check if we're in the right directory
if not exist "print-server.js" (
    echo [ERROR] print-server.js not found!
    echo Please run this script from the print-server directory
    echo.
    pause
    exit /b 1
)

echo [OK] Found print-server.js
echo.

REM Check if node_modules exists
if not exist "node_modules\" (
    echo [INFO] Installing dependencies...
    echo This may take a few minutes...
    echo.
    call npm install
    if %ERRORLEVEL% NEQ 0 (
        echo [ERROR] Failed to install dependencies
        pause
        exit /b 1
    )
    echo.
    echo [OK] Dependencies installed successfully
    echo.
)

echo ========================================
echo Starting Print Server...
echo ========================================
echo.
echo The server will start on port 3000
echo.
echo Test URLs:
echo   - http://localhost:3000
echo   - http://localhost:3000/status
echo   - http://localhost:3000/test
echo.
echo Press Ctrl+C to stop the server
echo ========================================
echo.

node print-server.js
