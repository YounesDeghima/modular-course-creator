@echo off

echo [Shutdown] Terminating all environment processes and windows...

:: 1. Graceful Database Shutdown (Only if XAMPP exists)
if exist "C:\xampp\mysql\bin\mysqladmin.exe" (
    "C:\xampp\mysql\bin\mysqladmin.exe" -u root shutdown 2>nul
)

:: 2. Stop Apache (Only if XAMPP exists)
if exist "C:\xampp\apache\bin\httpd.exe" (
    "C:\xampp\apache\bin\httpd.exe" -k stop 2>nul
)

:: 3. Close Laravel, Ollama, and Vite background runners
taskkill /f /im php.exe 2>nul
taskkill /f /im ollama.exe 2>nul
taskkill /f /im node.exe 2>nul

:: 4. THE ABSOLUTE FIX: Forcibly close all open CMD terminal windows instantly
taskkill /f /im cmd.exe 2>nul

echo All services stopped successfully!
