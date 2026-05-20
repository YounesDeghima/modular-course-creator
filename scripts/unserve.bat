@echo off

:: 1. Graceful Database Shutdown (Only if XAMPP exists) [cite: 3]
if exist "C:\xampp\mysql\bin\mysqladmin.exe" (
    "C:\xampp\mysql\bin\mysqladmin.exe" -u root shutdown 2>nul [cite: 3]
)

:: 2. Stop Apache (Only if XAMPP exists) [cite: 3]
if exist "C:\xampp\apache\bin\httpd.exe" (
    "C:\xampp\apache\bin\httpd.exe" -k stop 2>nul [cite: 3]
)

:: 3. Close Laravel, Ollama, Vite, and the Windows-native PTY Server
:: 2>nul keeps the console clean if the tasks are already closed [cite: 3]
taskkill /f /im php.exe 2>nul [cite: 3]
taskkill /f /im ollama.exe 2>nul [cite: 3]
taskkill /f /im node.exe 2>nul [cite: 3]

:: 4. Cleanup any lingering CMD windows specifically running your tasks [cite: 3]
taskkill /fi "windowtitle eq cmd.exe" /im cmd.exe /f 2>nul [cite: 3]

echo All services stopped successfully! [cite: 4]
pause [cite: 4]
