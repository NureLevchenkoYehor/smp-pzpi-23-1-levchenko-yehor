<?php

// This script is required to insert a new user into the database for this specific lab.

// Declare global variables
const DATABASE_DSN = 'sqlite:' . __DIR__ . '/data.sqlite';
const SQL_INSERT_USER = '
INSERT INTO [UserIdentities] ([user_identity_email], [user_identity_password_hash])
VALUES (:email, :password);';
CONST SQL_INSERT_CUSTOMER = '
INSERT INTO [CustomerProfiles] ([customer_identity_id])
VALUES (:identity_id);';

// Get the database connection
try {
    $pdo = new PDO(DATABASE_DSN);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (\Throwable $th) {
    throw $th;
}

$stmt = $pdo->prepare(SQL_INSERT_USER);
$stmt->bindValue(':email', 'Test');
$stmt->bindValue(':password', password_hash('123123', PASSWORD_DEFAULT));
$stmt->execute();
$identity_id = $pdo->lastInsertId();
$stmt = $pdo->prepare(SQL_INSERT_CUSTOMER);
$stmt->bindValue(':identity_id', $identity_id);
$stmt->execute();