@echo off
setlocal EnableExtensions

cd /d "%~dp0.."

echo ========================================
echo Masarak - Project Setup
echo ========================================
echo.

call scripts\check-environment.bat
if errorlevel 1 goto FAILED

REM --------------------------------------------------
REM .env
REM --------------------------------------------------

echo.
echo [1/7] Checking environment file...

if not exist .env (
    echo [FAIL] .env does not exist.
    echo.
    echo Run:
    echo   scripts\setup-database.bat
    echo.
    goto FAILED
)

echo [OK] .env exists.

REM --------------------------------------------------
REM Composer
REM --------------------------------------------------

echo.
echo [2/7] Installing PHP dependencies...

call composer install --no-interaction --prefer-dist

if errorlevel 1 goto FAILED

REM --------------------------------------------------
REM npm
REM --------------------------------------------------

echo.
echo [3/7] Installing frontend dependencies...

call npm ci

if errorlevel 1 goto FAILED

REM --------------------------------------------------
REM Laravel
REM --------------------------------------------------

echo.
echo [4/7] Preparing Laravel...

findstr /B /C:"APP_KEY=base64:" .env >nul 2>&1

if errorlevel 1 (
    php artisan key:generate
    if errorlevel 1 goto FAILED
) else (
    echo [SKIP] Application key already exists.
)

php artisan config:clear

if errorlevel 1 goto FAILED

REM --------------------------------------------------
REM Database
REM --------------------------------------------------

echo.
echo [5/7] Checking database and running migrations...

php artisan migrate --force

if errorlevel 1 (
    echo.
    echo [FAIL] Laravel could not prepare the database.
    echo.
    echo If the database has not been configured, run:
    echo   scripts\setup-database.bat
    echo.
    goto FAILED
)

echo [OK] Database migrations.

REM --------------------------------------------------
REM Tests
REM --------------------------------------------------

echo.
echo [6/7] Running Laravel tests...

php artisan test

if errorlevel 1 goto FAILED

REM --------------------------------------------------
REM Frontend
REM --------------------------------------------------

echo.
echo [7/7] Building frontend...

call npm run build

if errorlevel 1 goto FAILED

echo.
echo ========================================
echo Masarak project setup: PASS
echo ========================================
echo.
echo Next:
echo   scripts\verify-project.bat
echo.
exit /b 0

:FAILED

echo.
echo ========================================
echo Masarak project setup: FAIL
echo ========================================
echo.
echo Fix the failed step above and run this script again.
exit /b 1