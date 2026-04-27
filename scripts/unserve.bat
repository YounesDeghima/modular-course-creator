@echo off


:: 1. Graceful Database Shutdown
"C:\xampp\mysql\bin\mysqladmin.exe" -u root shutdown

:: 2. Stop Apache
"C:\xampp\apache\bin\httpd.exe" -k stop

:: 3. Close the Laravel and Ollama Windows
:: This kills the PHP and Ollama processes which forces their windows to close
taskkill /f /im php.exe
taskkill /f /im ollama.exe

:: 4. Cleanup any lingering CMD windows specifically running your tasks
taskkill /fi "windowtitle eq cmd.exe" /im cmd.exe /f


pause
