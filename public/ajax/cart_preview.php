<?php
session_start();

require_once(__DIR__ . '/../../secure_config.php');

require_once(__DIR__ . '/../../includes/Database.php');


$config = require(__DIR__ . '/../../secure_config.php');
$db = new Database($config);
$cart = $_SESSION['cart'] ?? [];

if (empty($cart)) {
    echo "<p class='text-muted'>Your cart is empty.</p>";
    return;
}

$total = 0;

foreach ($cart as $item):
    $product = $db->query("SELECT name_en, price FROM products WHERE id = :id", ['id' => $item['product_id']])->fetch(PDO::FETCH_ASSOC);
    if (!$product) continue;

    $lineTotal = $item['quantity'] * $product['price'];
    $total += $lineTotal;
?>
<div class="d-flex justify-content-between align-items-center small mb-2">
    <div>
        <strong><?= htmlspecialchars($product['name_en']) ?></strong><br>
        <div class="d-flex align-items-center gap-2 mt-1">
            <button class="btn btn-sm btn-outline-secondary update-qty" data-id="<?= $item['product_id'] ?>" data-action="decrease">➖</button>
            <span><?= $item['quantity'] ?></span>
            <button class="btn btn-sm btn-outline-secondary update-qty" data-id="<?= $item['product_id'] ?>" data-action="increase">➕</button>
        </div>
        <small class="text-muted">AED <?= number_format($lineTotal, 2) ?></small>
    </div>
    <button class="btn btn-sm btn-danger remove-item" data-id="<?= $item['product_id'] ?>">🗑</button>
</div>
<?php endforeach; ?>

<hr>
<div class='d-flex justify-content-between fw-bold mb-2'>
    <span>Total:</span>
    <span>AED <?= number_format($total, 2) ?></span>
</div>
<a href="cart.php" class="btn btn-primary btn-sm w-100">Go to Cart</a>
