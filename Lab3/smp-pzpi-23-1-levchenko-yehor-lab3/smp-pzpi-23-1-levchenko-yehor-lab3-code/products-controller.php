<?php

const SQL_GET_ALL = '
SELECT 
  p.[product_id], 
  p.[product_name],
  p.[product_price],
  c.[currency_symbol],
  pi.[image_url],
  pi.[image_name]
FROM [Products] as p
JOIN [Images] as pi ON p.[image_id] = pi.[image_id]
JOIN [Currencies] as c ON p.[currency_code] = c.[currency_code];';

// Get products from data source
function get_products(PDO $pdo) {
    $pst = $pdo->query(SQL_GET_ALL);
    $products = $pst->fetchAll();
    return $products;
}

// GET /products
// Gets products from data source and render view
function index() {
    global $pdo;
    $products = get_products($pdo);
    require './products.php';
    exit;
}