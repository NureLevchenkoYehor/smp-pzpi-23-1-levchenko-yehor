<?php

// Helper functions
// function array_find(array $array, callable $callback): mixed {
//     foreach ($array as $key => $value) {
//         if ($callback($value, $key)) {
//             return $value;
//         }
//     }
//     return null;
// }

// function array_any(array $array, callable $callback): bool {
//     foreach ($array as $key => $value) {
//         if ($callback($value, $key)) {
//             return true;
//         }
//     }
//     return false;
// }

// Define constants
const DATABASE_DSN = 'sqlite:' . __DIR__ . '/database/data.sqlite';
const DEFAULT_CONTROLLER = 'products-controller';
const ROUTES = [
    '/' => DEFAULT_CONTROLLER,
    '/home' => DEFAULT_CONTROLLER,
    '/products' => DEFAULT_CONTROLLER,
    '/cart' => 'cart-controller',
    '/credential' => 'credential-controller',
    '/profile' => 'profile-controller',
];
const AUTHORIZED_ONLY_CONTROLLERS = [
    'cart-controller',
    'products-controller',
];
const UPLOAD_DIR_BASE = '/assets/uploads/';
const UPLOAD_DIR = __DIR__ . UPLOAD_DIR_BASE;
define('UPLOAD_DIR_URL', 'http://' . $_SERVER['HTTP_HOST'] . UPLOAD_DIR_BASE);

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

// Handle files for PHP server
if (php_sapi_name() === 'cli-server') {
    $path = __DIR__ .  parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    if (is_file($path)) {
        return false;
    }
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
    $stmt = $pdo->prepare('SELECT [user_identity_id] FROM [UserIdentities] WHERE [user_identity_id] = :id LIMIT 1;');
    $stmt->bindParam(':id', $user_id, PDO::PARAM_INT);
    $stmt->execute();
    $user = $stmt->fetch();
    if ($user === false) {
        $_SESSION['user'] = [ 'id' => null ];
    }
}

// Get request method and path
$request_method = $_SERVER['REQUEST_METHOD'];
$request_path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

// Get controller and action
$segments = explode('/', trim($request_path, '/'));
$controller = ROUTES["/$segments[0]"] ?? null;
$action = $segments[1] ?? 'index';
if ($controller === null) {
    http_response_code(404);
    header('Location: /page404.php');
    exit;
}

// Check if controller requires authentication
$require_auth = array_any(AUTHORIZED_ONLY_CONTROLLERS, fn($c) => $c === $controller);
if ($require_auth && !isset($_SESSION['user']['id']) ) {
    http_response_code(401);
    header('Location: /page401.php');
    exit;
}

// Get controller file path
$controller_file = __DIR__ . "/$controller.php";
if (!file_exists($controller_file)) {
    http_response_code(404);
    header('Location: /page404.php');
    exit;
}

// Get controller and action
require $controller_file;

// Check if action exists
if (!function_exists($action)) {
    http_response_code(404);
    header('Location: /page404.php');
    exit;
}

// Call action with parameters
$params = $request_method === 'POST' ? $_POST : $_GET;
$action($params);
exit;
