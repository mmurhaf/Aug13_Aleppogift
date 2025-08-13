<?php
session_start();

$product_id = (int) $_POST['product_id'];
if (isset($_SESSION['cart'][$product_id])) {
    unset($_SESSION['cart'][$product_id]);
}

$count = array_sum(array_column($_SESSION['cart'], 'quantity'));
echo json_encode(['success' => true, 'count' => $count]);
