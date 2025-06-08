<?php

const SQL_CREATE_ORDER = '
INSERT INTO [Orders] ([customer_id], [guest_id])
VALUES (:customer_id, :guest_id);';

const SQL_FIND_USER = '
SELECT COUNT(*)
FROM UserIdentities
WHERE user_identity_id = :id';

const SQL_BULK_INSERT_ORDER_ITEMS_BASE = '
INSERT INTO [OrderItems] ([order_id], [product_id], [order_item_quantity])
VALUES ';
const SQL_BULK_INSERT_ORDER_ITEMS_ITEM = '(:oi{0}, :pi{0}, :q{0})';

// Function that plays a role of a repository function
function create_order(PDO $pdo, array $order_data) {
    // Validate order data
    if (empty($order_data)) {
        throw new InvalidArgumentException('Order data is empty');
    }
    if (!isset($order_data['user_id']) && !isset($order_data['guest_id'])) {
        throw new InvalidArgumentException('Order data is missing user or guest id');
    }
    if (!isset($order_data['order_items']) || empty($order_data['order_items'])) {
        throw new InvalidArgumentException('Order data is missing order items');
    }
    // Check if user id is valid
    if (isset($order_data['user_id'])) {
        $stmt = $pdo->prepare(SQL_FIND_USER);
        $stmt->bindParam(':id', $order_data['user_id']);
        $stmt->execute();
        if ($stmt->fetchColumn() == 0) {
            throw new InvalidArgumentException('User id is invalid');
        }
    }
    $items = $order_data['order_items'];
    // Validate order items
    foreach ($items as $item) {
        if (!isset($item['product_id']) || !isset($item['quantity'])) {
            throw new InvalidArgumentException('Order item is missing product id or quantity');
        }
        if (!is_numeric($item['product_id']) || !is_numeric($item['quantity'])) {
            throw new InvalidArgumentException('Order item product id or quantity is not a number');
        }
        if ($item['quantity'] <= 0) {
            throw new InvalidArgumentException('Order item quantity must be greater than 0');
        }
    }
    // Order creation has 2 stages: order creation and order items creation
    $pdo->beginTransaction();
    // 1. Create order
    $stmt = $pdo->prepare(SQL_CREATE_ORDER);
    $stmt->bindParam(':customer_id', $order_data['user_id']);
    $stmt->bindParam(':guest_id', $order_data['guest_id']);
    $stmt->execute();
    $order_id = $pdo->lastInsertId();
    // Check if order was created
    if ($order_id === false) {
        $pdo->rollBack();
        throw new RuntimeException('Failed to create order');
    }
    // 2. Create order items
    // Prepare bulk insert statement
    $sql = SQL_BULK_INSERT_ORDER_ITEMS_BASE;
    foreach ($items as $index => $item) {
        $sql .= str_replace('{0}', $index, SQL_BULK_INSERT_ORDER_ITEMS_ITEM);
        if ($index < count($items) - 1) {
            $sql .= ',';
        }
    }
    $sql .= ';';
    $stmt = $pdo->prepare($sql);
    // Bind parameters
    foreach ($items as $index => $item) {
        $stmt->bindParam(":oi$index", $order_id, PDO::PARAM_INT);
        $stmt->bindParam(":pi$index", $item['product_id'], PDO::PARAM_INT);
        $stmt->bindParam(":q$index", $item['quantity'], PDO::PARAM_INT);
    }
    // Insert order items into database
    $stmt->execute();
    // Check if order items were created
    if ($stmt->rowCount() != count($items)) {
        $pdo->rollBack();
        throw new RuntimeException('Failed to create order items');
    }
    // Commit transaction
    $pdo->commit();
    return $order_id;
}

// GET /cart
function index() {
    // Check if cart is empty
    $cart_empty = empty($_SESSION['cart']);
    // Get cart items
    $cart_items = $_SESSION['cart'] ?? [];
    // Calculate total price
    $total_sum = 0;
    foreach ($cart_items as $item) {
        $total_sum += $item['total'];
    }
    // Normalize cart items
    foreach ($cart_items as $key => $item) {
        $cart_items[$key]['id'] = htmlspecialchars($item['id']);
        $cart_items[$key]['name'] = htmlspecialchars($item['name']);
        $cart_items[$key]['quantity'] = htmlspecialchars($item['quantity']);
        $cart_items[$key]['price'] = htmlspecialchars($item['price']);
        $cart_items[$key]['total'] = htmlspecialchars($item['total']);
    }
    // Render cart view
    require './cart.php';
    exit;
}

// POST /cart/remove
// Removes a product from the session's cart
function remove(array $params) {
    // Get product id from params
    $product_id = $params['id'] ?? null;
    if ($product_id === null) {
        http_response_code(400);
        echo json_encode(['error' => 'Product id is required']);
        exit;
    }
    // Validate product id
    if (!is_numeric($product_id)) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid product id', 'data' => "Expected number, got " . var_dump($product_id)]);
        exit;
    }
    // Remove product from cart
    unset($_SESSION['cart'][$product_id]);
    // Redirect to cart view
    header('Location: /cart');
    exit;
}

// POST /cart/add_batch
// Adds multiple products to the session's cart
function add_batch(array $params) {
    // Validate params
    if (!isset($params['products']) || !is_array($params['products'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid products data']);
        exit;
    }
    $products = $params['products'];
    foreach ($products as $product) {
        // Validate product data
        if (!isset($product['id'], $product['quantity'], $product['name'], $product['price'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid product data: missing fields', 'data' => [
                'id' => var_dump($product['id']),
                'quantity' => var_dump($product['quantity']),
                'name' => var_dump($product['name']),
                'price' => var_dump($product['price'])
            ]]);
            exit;
        }
        if (!is_numeric($product['id']) || !is_numeric($product['quantity']) || !is_numeric($product['price'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid product data: invalid data types', 'data' => [
                'id' => "Expected number, got " . var_dump($product['id']),
                'quantity' => "Expected number, got " . var_dump($product['quantity']),
                'price' => "Expected number, got " . var_dump($product['price'])
            ]]);
            exit;
        }
        if ($product['quantity'] < 0) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid product data: negative quantity']);
            exit;
        }
        // Update existing product in cart or add new one
        $existing_product = array_find($_SESSION['cart'], fn($item) => $item['id'] == $product['id']);
        if (isset($existing_product)) {
            $existing_product['quantity'] += $product['quantity'];
            $existing_product['total'] = $existing_product['quantity'] * $existing_product['price'];
            if ($existing_product['quantity'] == 0) {
                unset($_SESSION['cart'][$existing_product['id']]);
            } else {
                $_SESSION['cart'][$existing_product['id']] = $existing_product;
            }
        } else {
            if ($product['quantity'] == 0) {
                continue;
            }
            $_SESSION['cart'][$product['id']] = [
                'id' => $product['id'],
                'name' => $product['name'],
                'quantity' => $product['quantity'],
                'price' => $product['price'],
                'total' => $product['quantity'] * $product['price']
            ];
        }
    }
    // Redirect to cart view
    header('Location: /cart');
    exit;
}

// POST /cart/clear
// Clears the session's cart
function clear() {
    // Clear cart
    unset($_SESSION['cart']);
    // Redirect to cart view
    header('Location: /cart');
    exit;
}

// POST /cart/create
// Creates an order from the session's cart
function create() {
    // Validate cart
    if (!isset($_SESSION['cart'])) {
        http_response_code(400);
        echo json_encode(['error' => 'No cart found']);
        exit;
    }
    if (empty($_SESSION['cart'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Cart is empty']);
        exit;
    }
    // Prepare order data
    $cart = $_SESSION['cart'];
    $order_data = [
        'user_id' => $_SESSION['user']['id'] ?? null,
        'guest_id' => $_SESSION['guest']['id'] ?? null
    ];
    foreach ($cart as $item) {
        $order_data['order_items'][] = [
            'product_id' => $item['id'],
            'quantity' => $item['quantity']
        ];
    }
    global $pdo;
    try {
        create_order($pdo, $order_data);
        // Clear cart
        unset($_SESSION['cart']);
        // Redirect to cart view
        header('Location: /cart');
    } catch (\Throwable $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to create order', 'message' => $e->getMessage()]);
        echo "<p>" . var_dump($order_data) . "</p>";
    }
    exit;
}