<?php
require_once(__DIR__ . '/../../secure_config.php');
require_once('../includes/Database.php');

$db = new Database();

// Fetch categories
$categories = $db->query("SELECT * FROM categories WHERE status=1")->fetchAll(PDO::FETCH_ASSOC);

// Fetch brands
$brands = $db->query("SELECT * FROM brands WHERE status=1")->fetchAll(PDO::FETCH_ASSOC);

// Handle filters
$where = [];
$params = [];

if (isset($_GET['category']) && is_numeric($_GET['category'])) {
    $where[] = "p.category_id = :category_id";
    $params['category_id'] = $_GET['category'];
}

if (isset($_GET['brand']) && is_numeric($_GET['brand'])) {
    $where[] = "p.brand_id = :brand_id";
    $params['brand_id'] = $_GET['brand'];
}

$whereSQL = $where ? "WHERE " . implode(" AND ", $where) : "";

// Fetch products
$sql = "SELECT p.*, 
        (SELECT image_path FROM product_images WHERE product_id = p.id LIMIT 1) as main_image 
        FROM products p $whereSQL ORDER BY p.id DESC";
$products = $db->query($sql, $params)->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>AleppoGift - Home</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>

<h1>Welcome to AleppoGift</h1>

<!-- Filters -->
<form method="get" action="">
    <select name="category">
        <option value="">All Categories</option>
        <?php foreach ($categories as $cat): ?>
            <option value="<?php echo $cat['id']; ?>" <?php if(isset($_GET['category']) && $_GET['category'] == $cat['id']) echo 'selected'; ?>>
                <?php echo $cat['name_en']; ?>
            </option>
        <?php endforeach; ?>
    </select>

    <select name="brand">
        <option value="">All Brands</option>
        <?php foreach ($brands as $brand): ?>
            <option value="<?php echo $brand['id']; ?>" <?php if(isset($_GET['brand']) && $_GET['brand'] == $brand['id']) echo 'selected'; ?>>
                <?php echo $brand['name_en']; ?>
            </option>
        <?php endforeach; ?>
    </select>

    <input type="submit" value="Filter">
</form>

<hr>

<!-- Products -->
<div style="display: flex; flex-wrap: wrap;">
<?php foreach ($products as $product): ?>
    <div style="width: 200px; padding: 10px; border: 1px solid #ccc; margin: 10px;">
        <img src="<?php echo str_replace("../", "", $product['main_image']); ?>" width="100%" height="150" alt="">
        <h3><?php echo $product['name_en']; ?></h3>
        <p>Price: AED <?php echo $product['price']; ?></p>
        <a href="product.php?id=<?php echo $product['id']; ?>">View</a>
    </div>
<?php endforeach; ?>
</div>

</body>
</html>
