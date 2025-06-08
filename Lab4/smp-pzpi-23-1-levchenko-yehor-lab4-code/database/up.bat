@echo off
setlocal

set "DB_FILE=data.sqlite"
set "CREATE_SCRIPT=create.sql"
set "INSERT_SCRIPT=insert-user.php"

if exist "%DB_FILE%" (
    echo Removing existing database file: %DB_FILE%
    del "%DB_FILE%"
)

echo Creating new database file: %DB_FILE%
type nul > "%DB_FILE%"

if not exist "%DB_FILE%" (
    echo Failed to create database file: %DB_FILE%
    exit /b 1
)

if not exist "%CREATE_SCRIPT%" (
    echo SQL script not found: %CREATE_SCRIPT%
    exit /b 1
)

if not exist "%INSERT_SCRIPT%" (
    echo Insert script not found: %INSERT_SCRIPT%
    exit /b 1
)

echo Executing SQL script: %CREATE_SCRIPT%
sqlite3 "%DB_FILE%" < "%CREATE_SCRIPT%"
echo Database schema created.
echo Executing insert script: %INSERT_SCRIPT%
php "%INSERT_SCRIPT%"
echo Insert script executed successfully.
echo Database setup complete.

endlocal
