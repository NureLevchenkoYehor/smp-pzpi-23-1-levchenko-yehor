#!/bin/bash

# This script sets up the SQLite database for the application.
# It removes any existing database file, creates a new one,
# and executes the SQL script to create the database schema.
# Usage: ./up.sh in the src/database directory

DB_FILE="data.sqlite"
CREATE_SCRIPT="create.sql"
INSERT_SCRIPT="insert-user.php"

if [ -f "$DB_FILE" ]; then
    echo "Removing existing database file: $DB_FILE"
    rm "$DB_FILE"
fi

echo "Creating new database file: $DB_FILE"
touch "$DB_FILE"

if [ ! -f "$DB_FILE" ]; then
    echo "Failed to create database file: $DB_FILE"
    exit 1
fi

if [ ! -f "$CREATE_SCRIPT" ]; then
    echo "SQL script not found: $CREATE_SCRIPT"
    exit 1
fi

if [ ! -f "$INSERT_SCRIPT" ]; then
    echo "Insert script not found: $INSERT_SCRIPT"
    exit 1
fi

echo "Executing SQL script: $CREATE_SCRIPT"
if ! sqlite3 "$DB_FILE" < "$CREATE_SCRIPT"; then
    echo "Failed to execute SQL script: $CREATE_SCRIPT"
    exit 1
fi
echo "Database schema created."
echo "Executing insert script: $INSERT_SCRIPT"
if ! php "$INSERT_SCRIPT"; then
    echo "Failed to execute insert script: $INSERT_SCRIPT"
    exit 1
fi
echo "Insert script executed successfully."

echo "Database setup complete."