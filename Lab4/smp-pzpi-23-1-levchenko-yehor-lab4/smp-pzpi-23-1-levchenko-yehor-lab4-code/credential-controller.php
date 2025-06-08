<?php

const SQL_FIND_IDENTITY_BY_EMAIL = '
SELECT * FROM [UserIdentities]
WHERE [user_identity_email] = :email
LIMIT 1;';

function authorize(PDO $pdo, array $params) {
    // Validate the input parameters
    if (!isset($params['email']) || !isset($params['password'])) {
        throw new InvalidArgumentException('Missing email or password');
    }
    // Use this to validate the email format
    // if (!filter_var($params['email'], FILTER_VALIDATE_EMAIL)) {
    //     throw new InvalidArgumentException('Invalid email format');
    // }
    // Find the user identity in the database
    $stmt = $pdo->prepare(SQL_FIND_IDENTITY_BY_EMAIL);
    $stmt->bindValue(':email', $params['email']);
    $stmt->execute();
    $user = $stmt->fetch();
    if (!$user) {
        return -1;
    }
    // Verify the password
    $verified = password_verify($params['password'], $user->user_identity_password_hash);
    if (!$verified) {
        return -1;
    }
    return $user->user_identity_id;
}

// GET /credential
function index(array $params) {
    // Check if the user is already logged in
    if (isset($_SESSION['user']['id'])) {
        // Redirect to the home page
        header('Location: /');
        exit;
    }
    // Check if there is an error message
    $error = $params['error'] ?? null;
    require './credential.php';
    exit;
}

// POST /credential/login
// This function is called when the user submits the login form
function login(array $params) {
    // Validate the input parameters
    if (!isset($params['email']) || !isset($params['password'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid params array. Email or password are missing']);;
    }
    global $pdo;
    // Check if the user is authorized
    $authorized = authorize($pdo, [
        'email' => $params['email'],
        'password' => $params['password'],
    ]);
    if ($authorized > 0) {
        // Set the session variables
        $_SESSION['user']['id'] = $authorized;
        $_SESSION['user']['login_timestamp'] = time();
        // Redirect to the home page
        header('Location: /');
    } else {
        // Redirect to the login page with an error message
        header('Location: /credential?error=invalid_credentials');
    }
    exit;
}

// POST /credential/logout
function logout() {
    // Unset the session variables
    unset($_SESSION['user']['id']);
    unset($_SESSION['user']['login_timestamp']);
    // Redirect to the home page
    header('Location: /');
    exit;
}