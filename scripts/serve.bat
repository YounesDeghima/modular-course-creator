@echo off
setlocal enabledelayedexpansion

:: Get the directory where serve.bat lives
set "SCRIPT_DIR=%~dp0"

:: Jump one level up to the true project root
cd /d "%SCRIPT_DIR%.."
set "PROJECT_ROOT=%cd%"

:: ── CLEAN ENV FETCHING SYSTEM ──
if exist ".env" (
    echo [Env] Reading Laravel configurations...
    for /f "usebackq tokens=1* delims==" %%a in (".env") do (
        set "KEY=%%a"
        set "VAL=%%b"

        :: Strip spaces from key names
        set "KEY=!KEY: =!"

        :: Target exactly what we need securely without piping strings
        if "!KEY!"=="PTY_SECRET" (
            set "RAW_VAL=%%b"
            :: Strip trailing spaces and quotes
            for /f "tokens=*" %%x in ("!RAW_VAL!") do set "RAW_VAL=%%x"
            if "!RAW_VAL:~0,1!"=="""" set "RAW_VAL=!RAW_VAL:~1,-1!"
            if "!RAW_VAL:~0,1!"=="'" set "RAW_VAL=!RAW_VAL:~1,-1!"
            set "PTY_SECRET=!RAW_VAL!"
        )
    )
) else (
    echo [⚠️ WARNING] No root .env file found!
)

:: Check if XAMPP exists before attempting to start services
if exist "C:\xampp" (
    echo [XAMPP] Found! Starting Apache and MySQL...
    start "XAMPP Apache" /b "C:\xampp\apache\bin\httpd.exe"
    start "XAMPP MySQL" /b "C:\xampp\mysql\bin\mysqld.exe"
) else (
    echo [XAMPP] Not installed on this machine. Skipping...
)

echo [Services] Spawning Laravel Core Ecosystem...
:: Explicit titles allow unserve.bat to target them perfectly
start "PTY_Laravel_Server" cmd /k "php artisan serve"
start "PTY_Laravel_Queue" cmd /k "php artisan queue:work"
start "PTY_Vite_Dev" cmd /k "npm run dev"

:: Only start Ollama if it isn't already running in the background
tasklist /fi "imagename eq ollama.exe" | findstr /i "ollama.exe" >nul
if %errorlevel% neq 0 (
    echo [Ollama] Starting background AI engine...
    start "PTY_Ollama_AI" cmd /k "ollama serve"
) else (
    echo [Ollama] Already running in system tray. Skipping daemon spawn...
)

echo [PTY Server] Spawning Node Sandbox Engine...
start "PTY_Secure_Code_Server" cmd /k "cd /d "%PROJECT_ROOT%\pty-server" && set PTY_HOST=127.0.0.1&& set PTY_SECRET=%PTY_SECRET%&& node server.js"

echo All services initiated!
