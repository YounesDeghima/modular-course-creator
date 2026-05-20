@echo off

:: Check if XAMPP exists before attempting to start services [cite: 1]
if exist "C:\xampp" (
    echo [XAMPP] Found! Starting Apache and MySQL... [cite: 1]
    start /b "" "C:\xampp\apache\bin\httpd.exe" [cite: 1]
    start /b "" "C:\xampp\mysql\bin\mysqld.exe" [cite: 1]
) else (
    echo [XAMPP] Not installed on this machine. Skipping... [cite: 1]
)

:: Start Laravel, Queue, Ollama, and Vite (Windows close when process dies) [cite: 1]
start "Laravel Server" cmd /c "php artisan serve" [cite: 1]
start "Laravel Queue" cmd /c "php artisan queue:work" [cite: 1]
start "Ollama AI" cmd /c "ollama serve" [cite: 1]
start "Vite Dev Server" cmd /c "npm run dev" [cite: 1]

:: Start the Sandboxed Secure PTY Code Execution Server natively on Windows
echo [PTY Server] Booting service natively from Windows project tree...
start "PTY Secure Code Server" cmd /c "cd pty-server && if not exist node_modules (npm install) && set PTY_HOST=127.0.0.1 && node server.js"
