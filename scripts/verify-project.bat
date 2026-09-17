@echo off
setlocal EnableExtensions EnableDelayedExpansion

cd /d "%~dp0.."

echo ========================================
echo Masarak - Full Verification
echo ========================================
echo.

set "FAILED=0"
set "DB_VERSION_FILE=%TEMP%\masarak-laravel-db-version-%RANDOM%.txt"

REM --------------------------------------------------
REM Environment
REM --------------------------------------------------

echo [1/7] Environment

call scripts\check-environment.bat

if errorlevel 1 (
    set "FAILED=1"
    goto RESULT
)

REM --------------------------------------------------
REM Required files
REM --------------------------------------------------

echo.
echo [2/7] Project files

if not exist .env (
    echo [FAIL] .env is missing.
    set "FAILED=1"
) else (
    echo [OK] .env
)

if not exist vendor\autoload.php (
    echo [FAIL] Composer dependencies are missing.
    set "FAILED=1"
) else (
    echo [OK] Composer dependencies
)

if not exist node_modules (
    echo [FAIL] npm dependencies are missing.
    set "FAILED=1"
) else (
    echo [OK] npm dependencies
)

if "!FAILED!"=="1" goto RESULT

REM --------------------------------------------------
REM Composer platform requirements
REM --------------------------------------------------

echo.
echo [3/7] PHP dependency requirements

call composer check-platform-reqs

if errorlevel 1 (
    echo [FAIL] Composer platform requirements
    set "FAILED=1"
) else (
    echo [OK] Composer platform requirements
)

REM --------------------------------------------------
REM Laravel -> MySQL verification
REM --------------------------------------------------

echo.
echo [4/7] Laravel database connection

for /f "delims=" %%V in ('php artisan tinker --execute="echo DB::selectOne('select version() as version')->version;" 2^>nul') do (
    set "DB_VERSION=%%V"
)

if not defined DB_VERSION (
    echo [FAIL] Laravel database connection
    set "FAILED=1"
) else (
    echo Laravel database: !DB_VERSION!

    echo !DB_VERSION! | findstr /B "8.4." >nul

    if errorlevel 1 (
        echo [FAIL] Laravel is not connected to MySQL 8.4.x.
        set "FAILED=1"
    ) else (
        echo [OK] Laravel - MySQL 8.4.x
    )
)


REM --------------------------------------------------
REM Migrations
REM --------------------------------------------------

echo.
echo [5/7] Database migrations

php artisan migrate:status >nul

if errorlevel 1 (
    echo [FAIL] Migration status
    set "FAILED=1"
) else (
    echo [OK] Migration status
)

REM --------------------------------------------------
REM Tests
REM --------------------------------------------------

echo.
echo [6/7] Laravel tests

php artisan test

if errorlevel 1 (
    echo [FAIL] Laravel tests
    set "FAILED=1"
) else (
    echo [OK] Laravel tests
)

REM --------------------------------------------------
REM Build
REM --------------------------------------------------

echo.
echo [7/7] Frontend build

call npm run build

if errorlevel 1 (
    echo [FAIL] Frontend build
    set "FAILED=1"
) else (
    echo [OK] Frontend build
)

:RESULT

echo.
echo ========================================

if "%FAILED%"=="0" (
    echo FULL VERIFICATION: PASS
    echo ========================================
    echo.
    echo The project is ready.
    echo.
    echo Start it with:
    echo   scripts\start-project.bat
    exit /b 0
) else (
    echo FULL VERIFICATION: FAIL
    echo ========================================
    echo.
    echo Review the failed check above.
    exit /b 1
)