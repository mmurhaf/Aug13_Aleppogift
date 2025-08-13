<?php
// File: admin/products.php
require_once(__DIR__ . '/../../secure_config.php');
require_once('../includes/Database.php');

$db = new Database();

// Handle delete request
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $db->query("DELETE FROM product_images WHERE product_id = :id", ['id' => $id]);
    $db->query("DELETE FROM products WHERE id = :id", ['id' => $id]);
    header("Location: products.php");
    exit;
}

$products = $db->query("SELECT * FROM products ORDER BY id DESC")->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html>
<head>
    <title>All Products</title>
</head>
<body>
<h2>Product List</h2>
<p><a href="add_product.php">+ Add New Product</a></p>

<table border="1" cellpadding="6" cellspacing="0">
    <thead>
        <tr>
            <th>ID</th>
            <th>Name (EN)</th>
            <th>Category ID</th>
            <th>Price (AED)</th>
            <th>Status</th>
            <th>Actions</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($products as $p): ?>
            <tr>
                <td><?php echo $p['id']; ?></td>
                <td><?php echo htmlspecialchars($p['name_en']); ?></td>
                <td><?php echo $p['category_id']; ?></td>
                <td><?php echo number_format($p['price'], 2); ?></td>
                <td><?php echo $p['status'] ? 'Active' : 'Inactive'; ?></td>
                <td>
                    <a href="edit_product.php?id=<?php echo $p['id']; ?>">Edit</a> |
                    <a href="products.php?delete=<?php echo $p['id']; ?>" onclick="return confirm('Are you sure?')">Delete</a>
                </td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>

</body>
</html>
