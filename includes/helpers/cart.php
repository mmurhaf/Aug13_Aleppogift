<?php

function getCartTotalAndWeight($db, $cart): array {
    $total = 0;
    $weight = 0;

    foreach ($cart as $item) {
        $product = $db->query("SELECT price, weight FROM products WHERE id = :id", [
            'id' => $item['product_id']
        ])->fetch();

        $quantity = $item['quantity'];
        $total += $product['price'] * $quantity;
        $weight += ($product['weight'] ?? 1) * $quantity;
    }

    return [$total, $weight];
}
