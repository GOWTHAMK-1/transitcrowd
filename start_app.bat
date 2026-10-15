@echo off
title Public Transport Crowd Predictor - Web Application
echo ===================================================================
echo   Public Transport Crowd Predictor - Local Development Server
echo ===================================================================
cd /d "%~dp0"

echo [1/2] Initializing CouchDB Databases, Indexes, and Seed Data...
C:\xampp\php\php.exe database\init_couchdb.php
if %ERRORLEVEL% NEQ 0 (
    echo [ERROR] Database initialization failed. Please make sure CouchDB is running!
    pause
    exit /b 1
)

echo.
echo [2/2] Starting PHP Development Server on http://localhost:8000 ...
echo Fauxton NoSQL Admin: http://127.0.0.1:5984/_utils/
echo Press Ctrl+C to terminate the web server.
echo.
C:\xampp\php\php.exe -S localhost:8000 -t public
pause
