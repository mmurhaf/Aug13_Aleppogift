<?php
session_start();

$product_id = (int) $_POST['product_id'];
$quantity   = isset($_POST['quantity']) ? (int) $_POST['quantity'] : 1;

if ($product_id < 1 || $quantity < 1) {
    echo json_encode(['success' => false]);
    exit;
}

// Initialize cart if empty
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// Add or update item
if (isset($_SESSION['cart'][$product_id])) {
    $_SESSION['cart'][$product_id]['quantity'] += $quantity;
} else {
    $_SESSION['cart'][$product_id] = [
        'product_id'   => $product_id,
        'quantity'     => $quantity,
        'variation_id' => isset($_POST['variation_id']) ? (int) $_POST['variation_id'] : 1
    ];
}

// Log input (optional but BEFORE echo)
file_put_contents('test.txt', json_encode($_POST));

// Return clean JSON output
header('Content-Type: application/json');
echo json_encode([
    'success' => true,
    'count'   => array_sum(array_column($_SESSION['cart'], 'quantity'))
]);
