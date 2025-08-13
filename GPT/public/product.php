<?php
require_once(__DIR__ . '/../../secure_config.php');
require_once('../includes/Database.php');

$db = new Database();

if (!isset($_GET['id'])) {
    header("Location: index.php");
    exit;
}

$product_id = (int)$_GET['id'];

// Fetch product
$product = $db->query("SELECT * FROM products WHERE id = :id", ['id' => $product_id])->fetch(PDO::FETCH_ASSOC);
if (!$product) {
    header("Location: index.php");
    exit;
}

// Fetch images (ordered by display_order)
$images = $db->query("SELECT * FROM product_images WHERE product_id = :id ORDER BY display_order ASC", ['id' => $product_id])->fetchAll(PDO::FETCH_ASSOC);

// Separate main image
$main_image = null;
$gallery_images = [];

foreach ($images as $img) {
    if ($img['is_main']) {
        $main_image = $img['image_path'];
    } else {
        $gallery_images[] = $img['image_path'];
    }
}

// Fetch variations
$variations = $db->query("SELECT * FROM product_variations WHERE product_id = :id", ['id' => $product_id])->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?php echo htmlspecialchars($product['name_en']); ?> - AleppoGift</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .product-gallery img {
            border: 1px solid #ddd;
            padding: 5px;
            margin: 5px;
            width: 100px;
            height: auto;
        }

        .main-image {
            width: 100%;
            max-width: 400px;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>

<h2><?php echo htmlspecialchars($product['name_en']); ?></h2>

<div style="display: flex; gap: 30px;">
    <div style="width: 50%;">
        <?php if ($main_image): ?>
            <img src="<?php echo str_replace("../", "", $main_image); ?>" alt="Main Image" class="main-image">
        <?php endif; ?>

        <?php if ($gallery_images): ?>
            <div class="product-gallery">
                <?php foreach ($gallery_images as $img): ?>
                    <img src="<?php echo str_replace("../", "", $img); ?>" alt="Product Image">
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <div style="width: 50%;">
        <p><strong>Price:</strong> AED <?php echo number_format($product['price'], 2); ?></p>
        <p><?php echo nl2br(htmlspecialchars($product['description_en'])); ?></p>

        <form method="post" action="cart.php">
            <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">

            <?php if ($variations): ?>
                <label><strong>Choose Variation:</strong></label><br>
                <select name="variation_id" required>
                    <?php foreach ($variations as $var): ?>
                        <option value="<?php echo $var['id']; ?>">
                            Size: <?php echo htmlspecialchars($var['size']); ?> / 
                            Color: <?php echo htmlspecialchars($var['color']); ?> /
                            +AED <?php echo number_format($var['additional_price'], 2); ?>
                        </option>
                    <?php endforeach; ?>
                </select><br><br>
            <?php endif; ?>

            Quantity: <input type="number" name="quantity" value="1" min="1"><br><br>
            <input type="submit" name="add_to_cart" value="🛒 Add to Cart">
        </form>
    </div>
</div>

<p><a href="index.php">← Back to Home</a></p>

</body>
</html>
