#!/bin/bash

# This script sets up the SQLite database for the application.
# It removes any existing database file, creates a new one,
# and executes the SQL script to create the database schema.
# Usage: ./up.sh in the src/database directory

DB_FILE="data.sqlite"
CREATE_SCRIPT="create.sql"

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

echo "Executing SQL script: $CREATE_SCRIPT"
sqlite3 "$DB_FILE" < "$CREATE_SCRIPT"
echo "Database schema created."

echo "Database setup complete."