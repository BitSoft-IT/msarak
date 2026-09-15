@echo off
setlocal EnableExtensions

cd /d "%~dp0.."

echo ========================================
echo Masarak - Start Project
echo ========================================
echo.

call scripts\check-environment.bat
if errorlevel 1 goto FAILED

if not exist .env (
    echo [FAIL] .env does not exist.
    echo Run the setup steps first.
    goto FAILED
)

if not exist vendor\autoload.php (
    echo [FAIL] PHP dependencies are missing.
    echo Run:
    echo   scripts\setup-project.bat
    goto FAILED
)

if not exist node_modules (
    echo [FAIL] Frontend dependencies are missing.
    echo Run:
    echo   scripts\setup-project.bat
    goto FAILED
)

echo Starting Laravel...

start "Masarak - Laravel" cmd /k "cd /d ""%CD%"" && php artisan serve --host=127.0.0.1 --port=8000"

echo Starting Vite...

start "Masarak - Vite" cmd /k "cd /d ""%CD%"" && npm run dev"

echo.
echo Waiting for Laravel...

set /a COUNT=0

:WAIT_FOR_APP

powershell -NoProfile -Command ^
    "try { $r=Invoke-WebRequest -UseBasicParsing -Uri 'http://127.0.0.1:8000/up' -TimeoutSec 2; if($r.StatusCode -ge 200 -and $r.StatusCode -lt 400){exit 0}else{exit 1} } catch { exit 1 }" >nul 2>&1

if not errorlevel 1 goto READY

set /a COUNT+=1

if %COUNT% GEQ 30 (
    echo.
    echo [FAIL] Laravel did not become ready within 30 seconds.
    echo Check the Laravel terminal for the actual error.
    goto FAILED
)

timeout /t 1 /nobreak >nul
goto WAIT_FOR_APP

:READY

echo.
echo ========================================
echo Masarak is running
echo ========================================
echo.
echo Application:
echo   http://127.0.0.1:8000
echo.
echo Health:
echo   http://127.0.0.1:8000/up
echo.

start "" http://127.0.0.1:8000

exit /b 0

:FAILED

echo.
echo ========================================
echo Masarak start: FAIL
echo ========================================
exit /b 1