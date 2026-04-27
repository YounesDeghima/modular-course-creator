@echo off
:: Start XAMPP Services in the background
start /b "" "C:\xampp\apache\bin\httpd.exe"
start /b "" "C:\xampp\mysql\bin\mysqld.exe"

:: Start Laravel and Ollama (Window closes when process dies)
start cmd /c "php artisan serve"
start cmd /c "php artisan queue:work"
start cmd /c "ollama serve"
