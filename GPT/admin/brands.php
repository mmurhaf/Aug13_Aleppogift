<?php
session_start();
require_once(__DIR__ . '/../../secure_config.php');
require_once('../includes/Database.php');

// Check if admin is logged in
if (!isset($_SESSION['admin_logged_in'])) {
    header("Location: login.php");
    exit;
}

$db = new Database();
$message = "";

// Handle Add Brand
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_brand'])) {
    $name_ar = trim($_POST['name_ar']);
    $name_en = trim($_POST['name_en']);

    $db->query("INSERT INTO brands (name_ar, name_en, status) VALUES (:name_ar, :name_en, 1)", [
        'name_ar' => $name_ar,
        'name_en' => $name_en
    ]);

    $message = "Brand added successfully!";
}

// Handle Delete Brand
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $db->query("DELETE FROM brands WHERE id = :id", ['id' => $id]);
    header("Location: brands.php");
    exit;
}

// Fetch brands
$brands = $db->query("SELECT * FROM brands ORDER BY id DESC")->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Brands - AleppoGift</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>

<h2>Manage Brands</h2>

<p><a href="dashboard.php">Back to Dashboard</a></p>

<?php if($message): ?>
    <p style="color:green;"><?php echo $message; ?></p>
<?php endif; ?>

<h3>Add New Brand</h3>
<form method="post">
    <label>Brand Name (Arabic):</label><br>
    <input type="text" name="name_ar" required><br><br>

    <label>Brand Name (English):</label><br>
    <input type="text" name="name_en" required><br><br>

    <input type="submit" name="add_brand" value="Add Brand">
</form>

<hr>

<h3>Existing Brands</h3>
<table border="1" cellpadding="10">
    <tr>
        <th>ID</th>
        <th>Name Arabic</th>
        <th>Name English</th>
        <th>Actions</th>
    </tr>
    <?php foreach ($brands as $brand): ?>
    <tr>
        <td><?php echo $brand['id']; ?></td>
        <td><?php echo htmlspecialchars($brand['name_ar']); ?></td>
        <td><?php echo htmlspecialchars($brand['name_en']); ?></td>
        <td><a href="brands.php?delete=<?php echo $brand['id']; ?>" onclick="return confirm('Are you sure?');">Delete</a></td>
    </tr>
    <?php endforeach; ?>
</table>

</body>
</html>
