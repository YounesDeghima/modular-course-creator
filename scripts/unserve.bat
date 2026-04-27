@echo off
echo Initiating graceful shutdown...

:: Safely shut down MySQL
"C:\xampp\mysql\bin\mysqladmin.exe" -u root shutdown

:: Apache is generally safe to taskkill, but you can do it gracefully too
"C:\xampp\apache\bin\httpd.exe" -k stop

:: Kill the remaining Laravel/Ollama processes
taskkill /im php.exe /f
taskkill /im ollama.exe /f

echo All services shut down safely.
pause
