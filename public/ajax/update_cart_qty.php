<?php
session_start();

$product_id = (int) $_POST['product_id'];
$action     = $_POST['action'] ?? '';

if (isset($_SESSION['cart'][$product_id])) {
    if ($action === 'increase') {
        $_SESSION['cart'][$product_id]['quantity']++;
    } elseif ($action === 'decrease' && $_SESSION['cart'][$product_id]['quantity'] > 1) {
        $_SESSION['cart'][$product_id]['quantity']--;
    }
}

$count = array_sum(array_column($_SESSION['cart'], 'quantity'));
echo json_encode(['success' => true, 'count' => $count]);
