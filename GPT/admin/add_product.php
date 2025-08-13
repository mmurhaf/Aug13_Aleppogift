<?php
// File: admin/add_product.php
require_once(__DIR__ . '/../../secure_config.php');
require_once('../includes/Database.php');

$db = new Database();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name_en = $_POST['name_en'];
    $name_ar = $_POST['name_ar'];
    $category_id = $_POST['category_id'];
    $brand_id = $_POST['brand_id'];
    $description_en = $_POST['description_en'];
    $description_ar = $_POST['description_ar'];
    $price = $_POST['price'];
    $weight = $_POST['weight'];
    $status = $_POST['status'];

    $db->query("INSERT INTO products (name_en, name_ar, category_id, brand_id, description_en, description_ar, price, weight, status) 
                VALUES (:name_en, :name_ar, :category_id, :brand_id, :description_en, :description_ar, :price, :weight, :status)", [
        'name_en' => $name_en,
        'name_ar' => $name_ar,
        'category_id' => $category_id,
        'brand_id' => $brand_id,
        'description_en' => $description_en,
        'description_ar' => $description_ar,
        'price' => $price,
        'weight' => $weight,
        'status' => $status
    ]);

    $product_id = $db->lastInsertId();

    // Upload images
    $upload_dir = '../uploads/products/';
    if (!file_exists($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }

    if (!empty($_FILES['images']['name'][0])) {
        foreach ($_FILES['images']['tmp_name'] as $index => $tmpName) {
            $fileName = time() . '_' . basename($_FILES['images']['name'][$index]);
            $targetPath = $upload_dir . $fileName;

            if (move_uploaded_file($tmpName, $targetPath)) {
                $is_main = ($index == 0) ? 1 : 0;
                $display_order = $index;

                $db->query("INSERT INTO product_images (product_id, image_path, is_main, display_order)
                            VALUES (:product_id, :image_path, :is_main, :display_order)", [
                    'product_id' => $product_id,
                    'image_path' => $targetPath,
                    'is_main' => $is_main,
                    'display_order' => $display_order
                ]);
            }
        }
    }

    header("Location: products.php");
    exit;
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Add Product</title>
</head>
<body>
<h2>Add Product</h2>
<form method="post" enctype="multipart/form-data">
    Name (EN): <input type="text" name="name_en" required><br><br>
    Name (AR): <input type="text" name="name_ar" required><br><br>
    Category ID: <input type="number" name="category_id" required><br><br>
    Brand ID: <input type="number" name="brand_id"><br><br>
    Description (EN): <textarea name="description_en"></textarea><br><br>
    Description (AR): <textarea name="description_ar"></textarea><br><br>
    Price: <input type="number" step="0.01" name="price" required><br><br>
    Weight: <input type="number" name="weight" value="1"><br><br>
    Status: <select name="status">
        <option value="1" selected>Active</option>
        <option value="0">Inactive</option>
    </select><br><br>

    Upload Product Images:<br>
    <input type="file" name="images[]" multiple accept="image/*"><br><br>

    <input type="submit" value="Add Product">
</form>
</body>
</html>
