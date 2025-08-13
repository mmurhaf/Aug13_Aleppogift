
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

// Handle status update
if (isset($_POST['update_status'])) {
    $order_id = (int)$_POST['order_id'];
    $status = $_POST['payment_status'];
    $db->query("UPDATE orders SET payment_status = :status WHERE id = :id", [
        'status' => $status,
        'id' => $order_id
    ]);
    $message = "Order payment status updated successfully!";
}

// Fetch orders with customer info
$sql = "SELECT o.*, c.fullname, c.email FROM orders o
        LEFT JOIN customers c ON o.customer_id = c.id
        ORDER BY o.id DESC";
$orders = $db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Orders - AleppoGift</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>

<h2>Manage Orders</h2>

<p><a href="dashboard.php">Back to Dashboard</a></p>

<?php if($message): ?>
    <p style="color:green;"><?php echo $message; ?></p>
<?php endif; ?>

<table border="1" cellpadding="10">
    <tr>
        <th>Order ID</th>
        <th>Customer</th>
        <th>Email</th>
        <th>Order Date</th>
        <th>Total (AED)</th>
        <th>Payment Method</th>
        <th>Reference</th>
        <th>Status</th>
        <th>Update</th>
    </tr>
    <?php foreach ($orders as $order): ?>
    <tr>
        <td><?php echo $order['id']; ?></td>
        <td><?php echo htmlspecialchars($order['fullname']); ?></td>
        <td><?php echo htmlspecialchars($order['email']); ?></td>
        <td><?php echo $order['order_date']; ?></td>
        <td><?php echo $order['total_amount']; ?></td>
        <td><?php echo $order['payment_method']; ?></td>
        <td><?php echo $order['payment_reference']; ?></td>
        <td><?php echo ucfirst($order['payment_status']); ?></td>
<td>
    <form method="post" style="display:inline-block;">
        <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
        <select name="payment_status">
            <option value="pending" <?php if($order['payment_status']=='pending') echo "selected"; ?>>Pending</option>
            <option value="paid" <?php if($order['payment_status']=='paid') echo "selected"; ?>>Paid</option>
            <option value="failed" <?php if($order['payment_status']=='failed') echo "selected"; ?>>Failed</option>
        </select>
        <input type="submit" name="update_status" value="Save">
    </form>

    <?php if ($order['payment_status'] == 'paid'): ?>
        <br>
        <a href="download_invoice.php?id=<?php echo $order['id']; ?>" target="_blank">📄 Download Invoice</a>
    <?php endif; ?>
</td>

    </tr>
    <?php endforeach; ?>
</table>

</body>
</html>
