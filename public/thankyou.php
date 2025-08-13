<?php
session_start();
require_once('../secure_config.php');
require_once('../includes/Database.php');
require_once('../includes/email_notifier.php');
require_once('../includes/send_email.php');
require_once('../includes/whatsapp_notify.php');

$config = require(__DIR__ . '/../secure_config.php');
$db = new Database($config);
$order_id = isset($_GET['order']) ? (int)$_GET['order'] : 0;

if (!$order_id) {
    header("Location: index.php");
    exit;
}

// Fetch the order
$order = $db->query("
    SELECT 
        o.*, 
        c.fullname AS customer_name,
        c.email AS customer_email,
        c.phone AS customer_phone,
        c.address AS customer_address,
        c.city AS customer_city,
        c.country AS customer_country
    FROM orders o
    LEFT JOIN customers c ON o.customer_id = c.id
    WHERE o.id = :id
", ['id' => $order_id])->fetch(PDO::FETCH_ASSOC);

if (!$order) {
    header("Location: index.php");
    exit;
}

// Update status to paid if not already
if ($order['payment_status'] !== 'paid') {
    $db->query("UPDATE orders SET payment_status = 'paid', updated_at = NOW() WHERE id = :id", ['id' => $order_id]);
}

// Generate invoice
require_once '../includes/generate_invoice.php';
$invoicePath = "../invoice/invoice_{$order_id}.pdf";
$_SESSION['valid_invoice_' . $order_id] = true;





// In your POST handler, after creating the real order:
if (isset($_SESSION['temp_order_id'])) {
    // Delete the temporary quotation order
    $db->query("DELETE FROM orders WHERE id = :id", ['id' => $_SESSION['temp_order_id']]);
    unset($_SESSION['temp_order_id']);
    
    // Delete the temporary quotation file
    if (file_exists($publicQuotationPath)) {
        unlink($publicQuotationPath);
    }
}

$_SESSION['valid_invoice_' . $order_id] = true;

// --- Ziina payment ---

$payment_method = $_SESSION['payment_method'] ?? '';
$order_id = $_SESSION['order_id'] ?? 0;
$grandTotal = $_SESSION['grandTotal'] ?? 0;
$fullname = $_SESSION['fullname'] ?? '';
$email = $_SESSION['email'] ?? '';

if ($payment_method === 'Ziina' && $order_id && $grandTotal && $fullname && $email) {
    send_confirmation($order_id, $fullname, $grandTotal, $payment_method, $email);
}

// Clear session data
unset($_SESSION['cart']);
unset($_SESSION['temp_order_id']);
unset($_SESSION['discount_amount']);
unset($_SESSION['payment_method']);
unset($_SESSION['order_id']); 
unset($_SESSION['grandTotal']); 
unset($_SESSION['$fullname']);
unset($_SESSION['$email']); 

// Clear any temporary order session data
if (isset($_SESSION['temp_order'])) {
    unset($_SESSION['temp_order']);
}

// --- Confirmation function ---
function send_confirmation($order_id, $fullname, $grandTotal, $payment_method, $email) {
        sendAdminWhatsApp($order_id, $fullname, $grandTotal, $payment_method);

        ob_start();
        $invoiceInfo = require('../includes/generate_invoice.php');
        ob_end_clean();

        $fullPath = $invoiceInfo['full_path'] ?? '';

    // Send order confirmation email
        if (!empty($fullPath) && file_exists($fullPath)) {
            $status = sendInvoiceEmail($email, $order_id, $fullPath);
            if (!$status) {
                error_log("❌ Failed to send email for Order #$order_id to $email.");
            }
        } else {
            error_log("⚠️ PDF path missing or file not found for Order #$order_id.");
        }

        $_SESSION['cart'] = [];
        if (ob_get_level()) ob_end_clean();
    }
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Thank You - AleppoGift</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/thankyou.css">

</head>
<body class="bg-light">
    <?php include('../includes/header.php'); ?>

    <main class="thank-you-container">
        <div class="text-center py-4">
            <div class="confirmation-icon">
                <i class="fas fa-check-circle"></i>
            </div>
            <h1 class="thank-you-header">Thank You For Your Order!</h1>
            <p class="lead">Your payment has been successfully processed. We've sent a confirmation to <strong><?= htmlspecialchars($order['customer_email']); ?></strong></p>
            <p class="text-muted">Order ID: #<?= $order['id']; ?></p>
        </div>

        <div class="order-summary">
            <h3 class="h5">Order Summary</h3>
            <div class="order-detail">
                <span class="order-detail-label">Order Number:</span>
                <span class="order-detail-value">#<?= $order['id']; ?></span>
            </div>
            <div class="order-detail">
                <span class="order-detail-label">Date:</span>
                <span class="order-detail-value"><?= date('F j, Y', strtotime($order['order_date'])); ?></span>
            </div>
            <div class="order-detail">
                <span class="order-detail-label">Customer Name:</span>
                <span class="order-detail-value"><?= htmlspecialchars($order['customer_name']); ?></span>
            </div>
            <div class="order-detail">
                <span class="order-detail-label">Payment Method:</span>
                <span class="order-detail-value"><?= htmlspecialchars($order['payment_method']); ?></span>
            </div>
            <div class="order-detail">
                <span class="order-detail-label">Total Amount:</span>
                <span class="order-detail-value text-success fw-bold">AED <?= number_format($order['total_amount'], 2); ?></span>
            </div>
        </div>

        <div class="whats-next">
            <h4 class="h5">What's Next?</h4>
            <p>Your order is being processed and will be shipped soon. Here's what to expect:</p>
            
            <div class="steps">
                <div class="step">
                    <div class="step-number">1</div>
                    <div class="step-title">Order Processing</div>
                    <div class="step-desc">We're preparing your items for shipment</div>
                </div>
                <div class="step">
                    <div class="step-number">2</div>
                    <div class="step-title">Shipping</div>
                    <div class="step-desc">Your order will be dispatched within 1-2 business days</div>
                </div>
                <div class="step">
                    <div class="step-number">3</div>
                    <div class="step-title">Delivery</div>
                    <div class="step-desc">Expected delivery in 3-5 business days</div>
                </div>
            </div>
        </div>

        <div class="text-center mt-5">
            <?php $invoicePath = "../invoice/invoice_{$order_id}.pdf"; ?>
            <?php if (file_exists($invoicePath)): ?>
                <a href="download_invoice.php?id=<?= $order_id ?>" target="_blank" class="btn btn-download mb-3">
                    <i class="fas fa-file-pdf me-2"></i> Download Invoice
                </a>
            <?php else: ?>
                <p class="text-danger mb-3">Invoice PDF not found for this order.</p>
            <?php endif; ?>
            <div class="mt-4">
                <p class="mb-3">Need help with your order?</p>
                <a href="contact.php" class="btn btn-outline-secondary me-2">
                    <i class="fas fa-headset me-2"></i> Contact Support
                </a>
                <a href="index.php" class="btn btn-primary">
                    <i class="fas fa-home me-2"></i> Back to Home
                </a>
            </div>
        </div>
    </main>

    <?php include('../includes/footer.php'); ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>