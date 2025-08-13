<?php
session_start();
require_once(__DIR__ . '/../../secure_config.php');
require_once('../includes/Database.php');

$db = new Database();

if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// Handle Add to Cart
if (isset($_POST['add_to_cart'])) {
    $product_id = (int)$_POST['product_id'];
    $variation_id = isset($_POST['variation_id']) ? (int)$_POST['variation_id'] : null;
    $quantity = (int)$_POST['quantity'];

    $item = [
        'product_id' => $product_id,
        'variation_id' => $variation_id,
        'quantity' => $quantity
    ];

    $_SESSION['cart'][] = $item;
    header("Location: cart.php");
    exit;
}

// Handle Remove item
if (isset($_GET['remove'])) {
    unset($_SESSION['cart'][$_GET['remove']]);
    $_SESSION['cart'] = array_values($_SESSION['cart']);
    header("Location: cart.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Shopping Cart - AleppoGift</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>

<h2>Your Shopping Cart</h2>

<p><a href="index.php">Back to Home</a></p>

<?php if (!$_SESSION['cart']): ?>
    <p>Your cart is empty.</p>
<?php else: ?>

<table border="1" cellpadding="10">
    <tr>
        <th>Product</th>
        <th>Variation</th>
        <th>Qty</th>
        <th>Price</th>
        <th>Total</th>
        <th>Remove</th>
    </tr>
    <?php
    $grandTotal = 0;
    foreach ($_SESSION['cart'] as $key => $item):
        $product = $db->query("SELECT * FROM products WHERE id = :id", ['id' => $item['product_id']])->fetch(PDO::FETCH_ASSOC);
        $price = $product['price'];
        $variationText = "";
        if ($item['variation_id']) {
            $variation = $db->query("SELECT * FROM product_variations WHERE id = :id", ['id' => $item['variation_id']])->fetch(PDO::FETCH_ASSOC);
            $variationText = "Size: {$variation['size']} / Color: {$variation['color']}";
            $price += $variation['additional_price'];
        }
        $total = $price * $item['quantity'];
        $grandTotal += $total;
    ?>
    <tr>
        <td><?php echo $product['name_en']; ?></td>
        <td><?php echo $variationText; ?></td>
        <td><?php echo $item['quantity']; ?></td>
        <td><?php echo $price; ?> AED</td>
        <td><?php echo $total; ?> AED</td>
        <td><a href="?remove=<?php echo $key; ?>">Remove</a></td>
    </tr>
    <?php endforeach; ?>
</table>

<h3>Total: AED <?php echo $grandTotal; ?></h3>

<p><a href="checkout.php">Proceed to Checkout</a></p>

<?php endif; ?>

</body>
</html>
