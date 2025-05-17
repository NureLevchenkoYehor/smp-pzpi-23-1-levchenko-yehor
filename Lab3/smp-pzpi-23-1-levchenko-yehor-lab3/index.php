<?php

// Helper function
function array_find(array $array, callable $callback): mixed {
    foreach ($array as $key => $value) {
        if ($callback($value, $key)) {
            return $value;
        }
    }
    return null;
}

// Handle files for PHP server
if (php_sapi_name() === 'cli-server') {
    $path = __DIR__ .  parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    if (is_file($path)) {
        return false;
    }
}

// Define constants
const DATABASE_DSN = 'sqlite:' . __DIR__ . '/database/data.sqlite';
const DEFAULT_CONTROLLER = 'products-controller';
const ROUTES = [
    '/' => DEFAULT_CONTROLLER,
    '/home' => DEFAULT_CONTROLLER,
    '/products' => DEFAULT_CONTROLLER,
    '/cart' => 'cart-controller',
];

// Start session if not started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Init session variables if not set
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}
if (!isset($_SESSION['user'])) {
    $_SESSION['user'] = [ 'id' => null ];
}
if (!isset($_SESSION['user']['id']) && !isset($_SESSION['guest'])) {
    $guest_id = bin2hex(random_bytes(16));
    $_SESSION['guest'] = [ 'id' => $guest_id ];
}

// Get PDO instance
try {
    $pdo = new PDO(DATABASE_DSN);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_OBJ);

} catch (\Throwable $th) {
    http_response_code(500);
    throw $th;
}

// Reset user id if user was deleted
if (isset($_SESSION['user']['id'])) {
    $user_id = $_SESSION['user']['id'];
    $stmt = $pdo->prepare('SELECT id FROM UserIdentities WHERE user_identity_id = :id');
    $stmt->bindParam(':id', $user_id, PDO::PARAM_INT);
    $stmt->execute();
    if ($stmt->rowCount() === 0) {
        $_SESSION['user'] = [ 'id' => null ];
    }
}

// Get request method and path
$request_method = $_SERVER['REQUEST_METHOD'];
$request_path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

// Get controller and action
$segments = explode('/', trim($request_path, '/'));
$controller = ROUTES["/$segments[0]"];
$action = $segments[1] ?? 'index';
if ($controller === null) {
    http_response_code(404);
}

// Get controller file path
$controller_file = __DIR__ . "/$controller.php";
if (!file_exists($controller_file)) {
    http_response_code(404);
    exit;
}

// Get controller and action
require $controller_file;

// Check if action exists
if (!function_exists($action)) {
    http_response_code(404);
    exit;
}

// Call action with parameters
$params = $request_method === 'POST' ? $_POST : $_GET;
$action($params);
exit;
