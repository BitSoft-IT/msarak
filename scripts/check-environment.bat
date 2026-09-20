@echo off
setlocal EnableExtensions EnableDelayedExpansion

cd /d "%~dp0.."

echo ========================================
echo Masarak - Environment Check
echo ========================================
echo.

set "FAILED=0"
set "MYSQL_EXE="

REM --------------------------------------------------
REM Git
REM --------------------------------------------------

where git >nul 2>&1

if errorlevel 1 (
    echo [FAIL] Git is not installed or not available in PATH.
    set "FAILED=1"
) else (
    for /f "delims=" %%V in ('git --version') do echo [OK] %%V
)

REM --------------------------------------------------
REM PHP 8.2+
REM --------------------------------------------------

where php >nul 2>&1

if errorlevel 1 (
    echo [FAIL] PHP 8.2 or newer is required.
    set "FAILED=1"
) else (
    php -r "exit(version_compare(PHP_VERSION,'8.2.0','>=') ? 0 : 1);"

    if errorlevel 1 (
        for /f "delims=" %%V in ('php -r "echo PHP_VERSION;"') do (
            echo [FAIL] PHP %%V - PHP 8.2+ is required.
        )
        set "FAILED=1"
    ) else (
        for /f "delims=" %%V in ('php -r "echo PHP_VERSION;"') do (
            echo [OK] PHP %%V
        )
    )
)

REM --------------------------------------------------
REM Required PHP extensions
REM --------------------------------------------------

echo.
echo Checking required PHP extensions...

set "EXT_FAILED=0"

for %%E in (ctype curl dom fileinfo mbstring openssl pdo_mysql pdo_sqlite tokenizer xml) do (
    php -r "exit(extension_loaded('%%E') ? 0 : 1);"

    if errorlevel 1 (
        echo [FAIL] PHP extension: %%E
        set "EXT_FAILED=1"
    ) else (
        echo [OK] PHP extension: %%E
    )
)

if "!EXT_FAILED!"=="1" (
    set "FAILED=1"
)

REM --------------------------------------------------
REM Composer 2.x
REM --------------------------------------------------

echo.

where composer >nul 2>&1

if errorlevel 1 (
    echo [FAIL] Composer 2.x is required.
    set "FAILED=1"
) else (
    composer --version --no-ansi 2>nul | findstr /B /C:"Composer version 2." >nul

    if errorlevel 1 (
        echo [FAIL] Composer 2.x is required.
        set "FAILED=1"
    ) else (
        for /f "delims=" %%V in ('composer --version --no-ansi 2^>nul') do (
            echo [OK] %%V
        )
    )
)

REM --------------------------------------------------
REM Node.js 24.x
REM --------------------------------------------------

where node >nul 2>&1

if errorlevel 1 (
    echo [FAIL] Node.js 24.x is required.
    set "FAILED=1"
) else (
    for /f "delims=" %%V in ('node -p "process.versions.node.split('.')[0]"') do (
        set "NODE_MAJOR=%%V"
    )

    if not "!NODE_MAJOR!"=="24" (
        for /f "delims=" %%V in ('node -v') do (
            echo [FAIL] Node %%V - Node.js 24.x is required.
        )
        set "FAILED=1"
    ) else (
        for /f "delims=" %%V in ('node -v') do echo [OK] Node %%V
    )
)

REM --------------------------------------------------
REM npm
REM --------------------------------------------------

where npm >nul 2>&1

if errorlevel 1 (
    echo [FAIL] npm is required.
    set "FAILED=1"
) else (
    for /f "delims=" %%V in ('npm -v') do echo [OK] npm %%V
)

REM --------------------------------------------------
REM MySQL 8.4.x
REM Prefer official MySQL over XAMPP/MariaDB
REM --------------------------------------------------

if exist "C:\Program Files\MySQL\MySQL Server 8.4\bin\mysql.exe" (
    set "MYSQL_EXE=C:\Program Files\MySQL\MySQL Server 8.4\bin\mysql.exe"
)

if not defined MYSQL_EXE (
    for /f "delims=" %%P in ('where mysql 2^>nul') do (
        if not defined MYSQL_EXE set "MYSQL_EXE=%%P"
    )
)

if not defined MYSQL_EXE (
    echo [FAIL] MySQL 8.4.x was not found.
    set "FAILED=1"
) else (
    for /f "delims=" %%V in ('"!MYSQL_EXE!" --version') do (
        set "MYSQL_VERSION=%%V"
    )

    echo !MYSQL_VERSION! | findstr /I "MariaDB" >nul

    if not errorlevel 1 (
        echo [FAIL] MariaDB was detected instead of MySQL 8.4.
        echo        Found: !MYSQL_VERSION!
        set "FAILED=1"
    ) else (
        echo !MYSQL_VERSION! | findstr /I "Ver 8.4." >nul

        if errorlevel 1 (
            echo [FAIL] MySQL 8.4.x is required.
            echo        Found: !MYSQL_VERSION!
            set "FAILED=1"
        ) else (
            echo [OK] !MYSQL_VERSION!
        )
    )
)

echo.

if "%FAILED%"=="1" (
    echo ========================================
    echo Environment check: FAIL
    echo ========================================
    echo.
    echo Install or fix the failed requirement above,
    echo then run this script again.
    exit /b 1
)

echo ========================================
echo Environment check: PASS
echo ========================================
exit /b 0