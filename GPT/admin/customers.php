
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

// Fetch all customers
$sql = "SELECT * FROM customers ORDER BY id DESC";
$customers = $db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Customers - AleppoGift</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>

<h2>Manage Customers</h2>

<p><a href="dashboard.php">Back to Dashboard</a></p>

<table border="1" cellpadding="10">
    <tr>
        <th>ID</th>
        <th>Full Name</th>
        <th>Email</th>
        <th>Phone</th>
        <th>Address</th>
        <th>City</th>
        <th>Country</th>
        <th>Registered At</th>
    </tr>
    <?php foreach ($customers as $customer): ?>
    <tr>
        <td><?php echo $customer['id']; ?></td>
        <td><?php echo htmlspecialchars($customer['fullname']); ?></td>
        <td><?php echo htmlspecialchars($customer['email']); ?></td>
        <td><?php echo htmlspecialchars($customer['phone']); ?></td>
        <td><?php echo htmlspecialchars($customer['address']); ?></td>
        <td><?php echo htmlspecialchars($customer['city']); ?></td>
        <td><?php echo htmlspecialchars($customer['country']); ?></td>
        <td><?php echo $customer['created_at']; ?></td>
    </tr>
    <?php endforeach; ?>
</table>

</body>
</html>
