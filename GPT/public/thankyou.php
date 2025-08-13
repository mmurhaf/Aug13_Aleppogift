<?php
require_once(__DIR__ . '/../../secure_config.php');
require_once('../includes/Database.php');
require_once('../includes/email_notifier.php');

$db = new Database();


$order_id = isset($_GET['order']) ? (int)$_GET['order'] : 0;
$order = null;

if ($order_id) {
    $order = $db->query("SELECT * FROM orders WHERE id = :id", ['id' => $order_id])->fetch(PDO::FETCH_ASSOC);
}


if ($order) {
    $customer_name = $order['customer_name'];
    $customer_email = $order['customer_email'];

    // Generate invoice
    include 'generate_invoice.php';

    // Send email
    $invoicePath = "invoice/invoice_{$order_id}.pdf";
    sendOrderConfirmationEmail($customer_email, $customer_name, $order_id, $invoicePath);

    
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Thank You - AleppoGift</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>

<h2>Thank You!</h2>

<?php if ($order): ?>
    <p>Your order (Order ID: <?php echo $order['id']; ?>) has been placed successfully.</p>
    <p>Total Amount: AED <?php echo $order['total_amount']; ?></p>
<?php else: ?>
    <p>Order not found!</p>
<?php endif; ?>

<p><a href="index.php">Back to Home</a></p>

</body>
</html>
