@echo off
setlocal EnableExtensions EnableDelayedExpansion

cd /d "%~dp0.."

echo ========================================
echo Masarak - Database Setup
echo ========================================
echo.

call scripts\check-environment.bat
if errorlevel 1 goto FAILED

REM --------------------------------------------------
REM Locate MySQL
REM --------------------------------------------------

set "MYSQL_EXE="

if exist "C:\Program Files\MySQL\MySQL Server 8.4\bin\mysql.exe" (
    set "MYSQL_EXE=C:\Program Files\MySQL\MySQL Server 8.4\bin\mysql.exe"
)

if not defined MYSQL_EXE (
    for /f "delims=" %%P in ('where mysql 2^>nul') do (
        if not defined MYSQL_EXE set "MYSQL_EXE=%%P"
    )
)

if not defined MYSQL_EXE (
    echo [FAIL] MySQL 8.4 client could not be located.
    goto FAILED
)

REM --------------------------------------------------
REM Ensure MySQL84 service is running
REM --------------------------------------------------

echo.
echo [1/5] Checking MySQL service...

sc query MySQL84 >nul 2>&1

if errorlevel 1 (
    echo [FAIL] Windows service MySQL84 was not found.
    echo.
    echo Install MySQL Server 8.4 using the standard Windows installer
    echo and make sure the MySQL84 service is created.
    goto FAILED
)

sc query MySQL84 | findstr /I "RUNNING" >nul

if errorlevel 1 (
    echo MySQL84 is stopped. Starting it...

    net start MySQL84 >nul 2>&1

    if errorlevel 1 (
        echo [FAIL] MySQL84 could not be started.
        echo Run this terminal as Administrator and try again.
        goto FAILED
    )
)

echo [OK] MySQL84 is running.

REM --------------------------------------------------
REM Prepare .env
REM --------------------------------------------------

echo.
echo [2/5] Preparing .env...

if not exist .env (
    if not exist .env.example (
        echo [FAIL] .env.example was not found.
        goto FAILED
    )

    copy /Y .env.example .env >nul
    echo [OK] .env created.
) else (
    echo [SKIP] .env already exists.
)

REM --------------------------------------------------
REM Administrator account
REM --------------------------------------------------

echo.
echo [3/5] Preparing project database...
echo.

set "MYSQL_ADMIN="
set /p "MYSQL_ADMIN=MySQL administrator username [root]: "

if not defined MYSQL_ADMIN set "MYSQL_ADMIN=root"

REM Generate local project password.
REM It is stored only in the local .env and MySQL account.

for /f "delims=" %%P in ('powershell -NoProfile -Command "[Guid]::NewGuid().ToString('N')"') do (
    set "PROJECT_DB_PASSWORD=%%P"
)

if not defined PROJECT_DB_PASSWORD (
    echo [FAIL] Could not generate the local database password.
    goto FAILED
)

set "SQL_FILE=%TEMP%\masarak-db-%RANDOM%%RANDOM%.sql"

(
    echo CREATE DATABASE IF NOT EXISTS `masarak` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
    echo CREATE USER IF NOT EXISTS 'masarak_user'@'localhost' IDENTIFIED BY '!PROJECT_DB_PASSWORD!';
    echo ALTER USER 'masarak_user'@'localhost' IDENTIFIED BY '!PROJECT_DB_PASSWORD!';
    echo GRANT ALL PRIVILEGES ON `masarak`.* TO 'masarak_user'@'localhost';
    echo CREATE USER IF NOT EXISTS 'masarak_user'@'127.0.0.1' IDENTIFIED BY '!PROJECT_DB_PASSWORD!';
    echo ALTER USER 'masarak_user'@'127.0.0.1' IDENTIFIED BY '!PROJECT_DB_PASSWORD!';
    echo GRANT ALL PRIVILEGES ON `masarak`.* TO 'masarak_user'@'127.0.0.1';
    echo FLUSH PRIVILEGES;
) > "!SQL_FILE!"

echo.
echo MySQL will now ask for the password of:
echo   !MYSQL_ADMIN!
echo.
echo This password is used only to create the local project database.
echo It will NOT be saved by this script.
echo.

"!MYSQL_EXE!" --default-character-set=utf8mb4 -u "!MYSQL_ADMIN!" -p < "!SQL_FILE!"

set "MYSQL_RESULT=!ERRORLEVEL!"

del /Q "!SQL_FILE!" >nul 2>&1

if not "!MYSQL_RESULT!"=="0" (
    echo.
    echo [FAIL] Database creation failed.
    echo Verify the MySQL administrator username/password and try again.
    goto FAILED
)

echo [OK] Database and project user created.

REM --------------------------------------------------
REM Update .env
REM --------------------------------------------------

echo.
echo [4/5] Updating local .env...

set "MASARAK_DB_PASSWORD=!PROJECT_DB_PASSWORD!"

php -r "$f='.env';$c=file_get_contents($f);$v=['DB_CONNECTION'=>'mysql','DB_HOST'=>'127.0.0.1','DB_PORT'=>'3306','DB_DATABASE'=>'masarak','DB_USERNAME'=>'masarak_user','DB_PASSWORD'=>getenv('MASARAK_DB_PASSWORD')];foreach($v as $k=>$x){$p='/^'.preg_quote($k,'/').'=.*/m';if(preg_match($p,$c)){$c=preg_replace($p,$k.'='.$x,$c);}else{$c.=PHP_EOL.$k.'='.$x;}}file_put_contents($f,$c);"

if errorlevel 1 (
    set "MASARAK_DB_PASSWORD="
    echo [FAIL] Could not update .env.
    goto FAILED
)

REM --------------------------------------------------
REM Verify project user directly against MySQL
REM --------------------------------------------------

echo.
echo [5/5] Verifying database access...

set "MYSQL_PWD=!PROJECT_DB_PASSWORD!"

"!MYSQL_EXE!" ^
    --default-character-set=utf8mb4 ^
    -h 127.0.0.1 ^
    -P 3306 ^
    -u masarak_user ^
    -D masarak ^
    -N ^
    -e "SELECT VERSION();" > "%TEMP%\masarak-db-version.txt" 2>nul

set "DB_TEST=!ERRORLEVEL!"

set "MYSQL_PWD="
set "MASARAK_DB_PASSWORD="
set "PROJECT_DB_PASSWORD="

if not "!DB_TEST!"=="0" (
    del /Q "%TEMP%\masarak-db-version.txt" >nul 2>&1
    echo [FAIL] The project database user could not connect.
    goto FAILED
)

set /p "DB_VERSION="<"%TEMP%\masarak-db-version.txt"
del /Q "%TEMP%\masarak-db-version.txt" >nul 2>&1

echo !DB_VERSION! | findstr /B "8.4." >nul

if errorlevel 1 (
    echo [FAIL] Expected MySQL 8.4.x but server returned !DB_VERSION!.
    goto FAILED
)

echo [OK] MySQL !DB_VERSION!
echo [OK] Database: masarak
echo [OK] User: masarak_user
echo.

echo ========================================
echo Database setup: PASS
echo ========================================
echo.
echo Database credentials were written only to the local .env.
echo Do not commit .env to Git.
exit /b 0

:FAILED

if defined SQL_FILE del /Q "!SQL_FILE!" >nul 2>&1

set "MYSQL_PWD="
set "MASARAK_DB_PASSWORD="
set "PROJECT_DB_PASSWORD="

echo.
echo ========================================
echo Database setup: FAIL
echo ========================================
exit /b 1