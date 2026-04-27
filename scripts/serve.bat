@echo off
:: Start XAMPP Services in the background
start /b "" "C:\xampp\apache\bin\httpd.exe"
start /b "" "C:\xampp\mysql\bin\mysqld.exe"

:: Start Laravel and Ollama in visible windows
start cmd /k "php artisan serve"
start cmd /k "php artisan queue:work"
start cmd /k "ollama serve"
