@echo off
setlocal

cd /d "%~dp0"

set "CLARE_PHP=C:\Users\trung\.config\herd\bin\php84\php.exe"

if not exist "%CLARE_PHP%" (
    echo [Clare] Khong tim thay PHP 8.4 tai:
    echo %CLARE_PHP%
    exit /b 1
)

where npm.cmd >nul 2>nul
if errorlevel 1 (
    echo [Clare] Khong tim thay npm trong PATH.
    exit /b 1
)

where powershell.exe >nul 2>nul
if errorlevel 1 (
    echo [Clare] Khong tim thay PowerShell.
    exit /b 1
)

if not exist "node_modules\.bin\concurrently.cmd" (
    echo [Clare] Chua co node_modules. Hay chay npm install truoc.
    exit /b 1
)

if not exist "paypal.ps1" (
    echo [Clare] Khong tim thay paypal.ps1.
    exit /b 1
)

if /I "%~1"=="--check" (
    "%CLARE_PHP%" artisan about --only=environment
    if errorlevel 1 exit /b 1
    echo [Clare] Laravel, Vite, scheduler va tunnel thanh toan da san sang de khoi dong.
    exit /b 0
)

echo [Clare] Dang khoi dong Laravel, Vite, scheduler va tunnel thanh toan...
echo [Clare] Nhan Ctrl+C de dung cac tien trinh foreground.
echo.

call npx.cmd --no-install concurrently ^
    --kill-others-on-fail ^
    --names "LARAVEL,VITE,SCHEDULER,TUNNEL" ^
    --prefix-colors "magenta,cyan,yellow,green" ^
    "\"%CLARE_PHP%\" artisan serve --host=localhost --port=8000" ^
    "npm run dev -- --host=localhost" ^
    "\"%CLARE_PHP%\" artisan schedule:work" ^
    "powershell.exe -NoProfile -ExecutionPolicy Bypass -File \"%~dp0paypal.ps1\" -Port 8000 -NoStartLaravel -Watch"

endlocal
