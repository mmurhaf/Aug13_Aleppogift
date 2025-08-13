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

// Get total orders
$totalOrders = $db->query("SELECT COUNT(*) FROM orders")->fetchColumn();

// Get total products
$totalProducts = $db->query("SELECT COUNT(*) FROM products")->fetchColumn();

// Get total customers
$totalCustomers = $db->query("SELECT COUNT(*) FROM customers")->fetchColumn();

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Dashboard - AleppoGift</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <h2>Welcome to Admin Dashboard</h2>

    <p><a href="products.php">Manage Products</a></p>
    <p><a href="categories.php">Manage Categories</a></p>
    <p><a href="brands.php">Manage Brands</a></p>
    <p><a href="orders.php">Manage Orders</a></p>
    <p><a href="customers.php">Manage Customers</a></p>
    <p><a href="logout.php">Logout</a></p>

    <hr>

    <h3>Statistics</h3>
    <p>Total Orders: <?php echo $totalOrders; ?></p>
    <p>Total Products: <?php echo $totalProducts; ?></p>
    <p>Total Customers: <?php echo $totalCustomers; ?></p>
</body>
</html>
