<?php
ob_start(); // Start output buffering
require_once(__DIR__ . '/../secure_config.php');
require_once('../includes/bootstrap.php');
require_once('../includes/helpers/cart.php');
require_once('../includes/send_email.php');
require_once('../includes/whatsapp_notify.php');

if (empty($_SESSION['cart'])) {
    header("Location: cart.php");
    exit;
}

$config = require(__DIR__ . '/../secure_config.php');
$db = new Database($config);
$cart = $_SESSION['cart'];

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

list($cartTotal, $totalWeight) = getCartTotalAndWeight($db, $cart);

$country = $_POST['country'] ?? 'United Arab Emirates';
$city = $_POST['city'] ?? '';
$shippingAED = calculateShippingCost($country, $city, $totalWeight);
$grandTotal = $cartTotal + $shippingAED;

$discount = $_SESSION['discount_amount'] ?? 0;
$grandTotal = max(0, ($cartTotal - $discount)) + $shippingAED;

// Include the quotation generation script
// This will generate a quotation PDF if needed
// It can be used to provide a downloadable quotation for the customer  
//require_once '../includes/generate_quotation.php';
 

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF Protection
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die('Invalid CSRF token.');
    }

    // Sanitize input
    $fullname = htmlspecialchars(trim($_POST['fullname']));
    $email = filter_var(trim($_POST['email']), FILTER_VALIDATE_EMAIL);
    $phone = preg_replace('/[^0-9+]/', '', trim($_POST['phone']));
    $address = htmlspecialchars(trim($_POST['address']));
    $city = htmlspecialchars(trim($_POST['city']));
    $country = htmlspecialchars(trim($_POST['country']));
    $payment_method = $_POST['payment_method'];

    if (!$email) {
        die('Invalid email address.');
    }

    if (strlen($phone) < 6) {
        die('Invalid phone number.');
    }

    if (!in_array(strtolower($country), ['uae', 'united arab emirates']) && $payment_method === PAYMENT_METHOD_COD) {
        die("Cash on Delivery is not available for your country.");
    }

    // Save Customer
    $db->query("INSERT INTO customers (fullname, email, phone, address, city, country) VALUES 
        (:fullname, :email, :phone, :address, :city, :country)", compact('fullname', 'email', 'phone', 'address', 'city', 'country'));
    $customer_id = $db->lastInsertId();

    $paymentStatus = ($payment_method === PAYMENT_METHOD_COD) ? PAYMENT_STATUS_PENDING : PAYMENT_STATUS_PAID;

// Prepare coupon data from session
$coupon = $_SESSION['applied_coupon'] ?? null;

// Save Order
$db->query(
    "INSERT INTO orders 
        (customer_id, total_amount, total_weight, shipping_aed, payment_status, payment_method, note, remarks, 
         coupon_code, discount_type, discount_value, discount_amount)
    VALUES 
        (:customer_id, :total, :total_weight, :shipping_aed, :payment_status, :payment_method, :note, :remarks,
         :coupon_code, :discount_type, :discount_value, :discount_amount)",
    [
        'customer_id'     => $customer_id,
        'total'           => $grandTotal,
        'total_weight'    => $totalWeight,
        'shipping_aed'    => $shippingAED,
        'payment_status'  => $paymentStatus,
        'payment_method'  => $payment_method,
        'note'            => $_POST['note'] ?? null,
        'remarks'         => $_POST['remarks'] ?? null,

        // Coupon fields
        'coupon_code'     => $coupon['code'] ?? null,
        'discount_type'   => $coupon['discount_type'] ?? null,
        'discount_value'  => $coupon['discount_value'] ?? null,
        'discount_amount' => $coupon['discount'] ?? null
    ]
);
$order_id = $db->lastInsertId();

    // Save Order Items
    foreach ($cart as $item) {
        $product = $db->query("SELECT price FROM products WHERE id = :id", ['id' => $item['product_id']])->fetch();
        $price = $product['price'];
		if ($item['variation_id']) {
			$variation = $db->query(
				"SELECT additional_price FROM product_variations WHERE id = :id",
				['id' => $item['variation_id']]
			)->fetch(PDO::FETCH_ASSOC);

			if ($variation) {
				$price += $variation['additional_price'];
			}
		}

        $db->query("INSERT INTO order_items (order_id, product_id, variation_id, quantity, price) VALUES 
            (:order_id, :product_id, :variation_id, :qty, :price)", [
            'order_id' => $order_id,
            'product_id' => $item['product_id'],
            'variation_id' => $item['variation_id'] ?? null,
            'qty' => $item['quantity'],
            'price' => $price
        ]);
    }
    // --- Confirmation function ---
    function send_confirmation($order_id, $fullname, $grandTotal, $payment_method, $email) {
        sendAdminWhatsApp($order_id, $fullname, $grandTotal, $payment_method);

        ob_start();
        $invoiceInfo = require('../includes/generate_invoice.php');
        ob_end_clean();
        // ...existing code...
    }

    // --- COD immediate confirmation ---
    if (strtoupper($payment_method) === 'COD') {
        send_confirmation($order_id, $fullname, $grandTotal, $payment_method, $email);
        exit;
    }

// --- Ziina payment ---
$_SESSION['payment_method'] = $payment_method;
$_SESSION['order_id'] = $order_id;
$_SESSION['grandTotal'] = $grandTotal;
$_SESSION['$fullname'] = $fullname;
$_SESSION['$email'] = $email;

if ($payment_method === 'Ziina') {
    $ziina = new ZiinaPayment();
    $response = $ziina->createPaymentIntent($order_id, $grandTotal, "AleppoGift Order");

    if ($response['success']) {
        $db->query(
            "UPDATE orders 
             SET payment_status = 'pending', remarks = :resp 
             WHERE id = :id",
            [
                'resp' => json_encode($response, JSON_UNESCAPED_UNICODE),
                'id'   => $order_id
            ]
        );

        // Redirect customer to Ziina payment page
        header("Location: " . $response['payment_url']);
        exit;
    } else {
        echo "<h3>Ziina Payment Error</h3><p>" . htmlspecialchars($response['error']) . "</p>";
        exit;
    }
}

// --- Confirmation function ---
function send_confirmation($order_id, $fullname, $grandTotal, $payment_method, $email) {
    sendAdminWhatsApp($order_id, $fullname, $grandTotal, $payment_method);

    ob_start();
    $invoiceInfo = require('../includes/generate_invoice.php');
    ob_end_clean();

    $fullPath = $invoiceInfo['full_path'] ?? '';

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
    header("Location: thankyou.php?order=$order_id");
    exit;
}

}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout - AleppoGift</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="icon" href="assets/images/favicon.ico" type="image/x-icon">
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/checkout.css">
<body>

<div class="checkout-container">
        <div class="checkout-header">
            <h2><i class="fas fa-shopping-cart me-2"></i>Checkout</h2>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="index.php">Home</a></li>
                    <li class="breadcrumb-item"><a href="cart.php">Cart</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Checkout</li>
                </ol>
            </nav>
        </div>
    
    <div class="row">
        <div class="col-lg-8">
            <div class="checkout-card">
                <form method="post" class="checkout-form">
                    <!-- CSRF token -->
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">

                    <div class="form-section">
                        <h3><i class="fas fa-user"></i> Contact Information</h3>
                        <div class="form-group">
                            <label for="fullname">Full Name</label>
                            <input type="text" id="fullname" name="fullname" required>
                        </div>
                        <div class="form-group">
                            <label for="email">Email</label>
                            <input type="email" id="email" name="email" required>
                        </div>
                        <div class="form-group">
                            <label for="phone">Phone Number</label>
                            <input type="text" id="phone" name="phone" required>
                        </div>
                        <div class="form-group">
                            <label for="note">Note (Optional)</label>
                            <input type="text" id="note" name="note" >
                        </div>
                    </div>

                    <div class="form-section">
                        <h3><i class="fas fa-truck"></i> Shipping Address</h3>
                        <div class="form-group">
                            <label for="address">Address</label>
                            <input type="text" id="address" name="address" required>
                        </div>
                        <div class="form-group">
                            <label for="country">Country</label>
                            <select id="country" name="country" required>
                                <?php 
                                require_once 'ajax/countries.php'; // Load countries array
                                // Ensure countries are sorted alphabetically   
                                foreach ($countries as $name => $flag): ?>
                                    <option value="<?= htmlspecialchars($name) ?>" <?= $name === 'United Arab Emirates' ? 'selected' : '' ?>>
                                        <?= $flag ?> <?= htmlspecialchars($name) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="city">City</label>
                            <input type="text" id="city" name="city" required>
                        </div>
                        <div class="total-row">
                            <span>Shipping cost:</span>
                            <span id="shipping-cost"><?= number_format($shippingAED, 2) ?> AED</span>
                        </div>
                    </div>

                    <div class="form-section">
                        <h3><i class="fas fa-credit-card"></i> Payment Method</h3>
                        <div class="form-group">
                            <label for="payment_method">Select Payment Method</label>
                            <select id="payment_method" name="payment_method" required>
                                <option value="COD">  💵 Cash on Delivery</option>
                                <option value="Ziina">💳 Credit Card (Online Payment)</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-actions">
                        <button type="submit" class="submit-btn">Place Order</button>
                    </div>
                </form>
            </div>
        </div>
        
        <div class="col-lg-4">
            <div class="order-summary-card">
                <div class="summary-header">
                    <h3><i class="fas fa-receipt"></i> Order Summary</h3>
                </div>
        
                <div class="summary-body">
                    <ul class="cart-items-list">
                        <?php foreach ($_SESSION['cart'] as $item):
                            $product = $db->query(
                                "SELECT name_en, price FROM products WHERE id = :id",
                                ['id' => $item['product_id']]
                            )->fetch(PDO::FETCH_ASSOC);
                            if (!$product) {
									echo "<tr><td colspan='4'>Product not found (ID: {$item['product_id']})</td></tr>";
									continue;
								}
								$price = $product['price'];
								if ($item['variation_id']) {
									$variation = $db->query(
										"SELECT additional_price FROM product_variations WHERE id = :id",
										['id' => $item['variation_id']]
									)->fetch(PDO::FETCH_ASSOC);

									if ($variation) {
										$price += $variation['additional_price'];
									}
								}
                            ?>
                            <li class="cart-item">
                                <span class="item-name"><?= htmlspecialchars($product['name_en']) ?></span>
                                <span class="item-quantity"><?= htmlspecialchars($item['quantity']) ?> × </span>
                                <span class="item-price"><?= number_format($price, 2) ?> AED</span>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                    
                    <div class="summary-totals">
                        <div class="total-row">
                            <span>Items Total:</span>
                            <span id="item-total" data-value="<?= $cartTotal ?>"><?= number_format($cartTotal, 2) ?> AED</span>
                        </div>
                        <div class="total-row">
                            <span>Shipping:</span>
                            <!-- <span id="shipping-cost"><?= number_format($shippingAED, 2) ?> AED</span> -->
                            <span id="shipping-cost2"> <?= number_format($shippingAED, 2) ?> AED</span>
                        </div>
                        <div class="total-row" id="discount-row" style="display:none;">
                            <span>Discount:</span>
                            <span id="discount-amount" style="color: green;"></span>
                        </div>

                        <div class="total-row grand-total">
                            <span><strong>Grand Total:</strong></span>
                            <span id="grand-total"><strong><?= number_format($cartTotal + $shippingAED, 2) ?> AED</strong></span>
                        </div>
                    </div>
                </div>
				<div class="coupon-section">
				  <h4 class="coupon-title">Have a Coupon Code?</h4>
				  <div class="coupon-input-group">
					<input 
					  type="text" 
					  id="coupon-code" 
					  class="coupon-input" 
					  placeholder="Enter your code here"
					  aria-label="Coupon code"
					>
					<button 
					  type="button" 
					  class="coupon-button" 
					  onclick="applyCoupon()"
					  aria-label="Apply coupon"
					>
					  Apply
					</button>
			  </div>
			  <p id="coupon-message" class="coupon-message"></p>
			</div>

            </div>
            
            <div class="quotation-card">
                <div class="quotation-header">
                    <h3><i class="fas fa-file-download"></i> Download Quotation</h3>
                </div>
                <div class="quotation-body">
                    <p>If you need a formal quotation, you can download it here:</p>
                    <a href="/download_quotation.php" target="_blanck" class="btn btn-download">
                        <i class="fas fa-download"></i> Download Quotation PDF
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Loading Overlay -->
<div class="loading-overlay" id="loadingOverlay" style="display: none;">
    <div class="spinner-border loading-spinner" role="status">
        <span class="visually-hidden">Loading...</span>
    </div>
    <h4 class="mt-3">Processing your order...</h4>
    <p class="text-muted">Please wait while we confirm your payment</p>
</div>




<footer class="footer mt-5">
    <div class="container">
        <?php require_once('../includes/footer.php'); ?>
    </div>
</footer>

<!-- <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script> -->
    
<script>
    document.addEventListener('DOMContentLoaded', () => {
        const countrySelect = document.getElementById('country');
        const cityInput = document.getElementById('city');
        const paymentMethodSelect = document.getElementById('payment_method');
        const codOption = paymentMethodSelect.querySelector('option[value="COD"]');

        function handleCODAvailability() {
            const selectedCountry = countrySelect.value.trim().toLowerCase();

            if (selectedCountry !== 'united arab emirates') {
                codOption.disabled = true;

                if (paymentMethodSelect.value === 'COD') {
                    paymentMethodSelect.value = 'Ziina';
                }
            } else {
                codOption.disabled = false;
            }
        }

        function updateShippingCost() {
            const country = countrySelect.value.trim().toLowerCase();
            const totalWeight = <?= json_encode($totalWeight) ?>; // Use PHP variable directly
            let city = cityInput.value.trim().toLowerCase();
            if (!city) {
                city = '_'; // Default city if not provided
            }
				console.log(`Calculating shipping for country: ${country}, city: ${city}`);
				if (!country || !city) {
                document.getElementById('shipping-cost').textContent = '30.00 AED';
                return;
            }
            else {
                document.getElementById('shipping-cost').textContent = 'Calculating...';
            fetch('ajax/calculate_shipping.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                    body: `country=${encodeURIComponent(country)}&city=${encodeURIComponent(city)}&totalWeight=${encodeURIComponent(totalWeight)}`
                })
                .then(response => response.text()) // Read as plain text
                .then(text => {
                    console.log('Raw shipping response:', text); // ðŸ‘ˆ Watch this in the browser console
                    const data = JSON.parse(text); // Try to parse manually
					console.log('392 shippingAED response:',data.shippingAED);
                    if (data.shippingAED !== undefined) {
                        document.getElementById('shipping-cost').textContent = `${data.shippingAED} AED`;
                        document.getElementById('shipping-cost2').textContent = `${data.shippingAED} AED`;
						updateGrandTotal(data.shippingCost);
                    }
                })
                .catch(error => {
                    console.error('Shipping cost update failed:', error);
                });
          }
        }

        function updateGrandTotal(shipping) {
			console.log('406 updateGrandTotal: shipping :',shipping);
            const itemTotal = parseFloat(document.getElementById('item-total').dataset.value || 0);
            const grandTotal = itemTotal + parseFloat(shipping);
			console.log('409 grandTotal=',grandTotal, ' itemTotal=', itemTotal ,' shipping=', parseFloat(shipping));
            document.getElementById('grand-total').textContent = `${grandTotal.toFixed(2)} AED`;
            document.getElementById('shipping-cost').textContent = `${parseFloat(shipping).toFixed(2)} AED`;
        }

        // Bind events
        countrySelect.addEventListener('change', () => {
            handleCODAvailability();
            updateShippingCost();
        });

        cityInput.addEventListener('input', updateShippingCost);

        // Initial run
        handleCODAvailability();
        updateShippingCost();
    });
    

    document.addEventListener('DOMContentLoaded', function () {
        const form = document.getElementById('checkout-form');
        const overlay = document.getElementById('loadingOverlay');

        if (form && overlay) {
            form.addEventListener('submit', function () {
                overlay.style.display = 'flex'; // Or block, depending on your CSS
            });
        }
    });
	
	function applyCoupon() {
    const code = document.getElementById('coupon-code').value.trim();
    if (!code) return;

    fetch('ajax/apply_coupon.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'code=' + encodeURIComponent(code)
    })
    .then(response => response.text())
    .then(text => {
        console.log('Coupon raw response:', text);
        try {
            const data = JSON.parse(text);
            if (data.success) {
                document.getElementById('coupon-message').textContent = "Coupon applied!";
                updateGrandTotalWithDiscount(data.discountAmount);
            } else {
                document.getElementById('coupon-message').style.color = "red";
                document.getElementById('coupon-message').textContent = data.message;
            }
        } catch (err) {
            console.error("JSON parse error:", err, text);
            document.getElementById('coupon-message').style.color = "red";
            document.getElementById('coupon-message').textContent = "Coupon system error. Please contact support.";
        }
    });
    }

    function updateGrandTotalWithDiscount(discountAmount) {
        discountAmount = parseFloat(discountAmount) || 0;

        const itemTotal = parseFloat(document.getElementById('item-total').dataset.value || 0);
        const shipping = parseFloat(document.getElementById('shipping-cost2').textContent) || 0;

        const grandBeforeDiscount = itemTotal + shipping;
        const grandTotal = Math.max(grandBeforeDiscount - discountAmount, 0);

        document.getElementById('grand-total').textContent = grandTotal.toFixed(2) + ' AED';

        // Show discount visually
        const discountRow = document.getElementById('discount-row');
        const discountDisplay = document.getElementById('discount-amount');
        if (discountRow && discountDisplay) {
            discountDisplay.textContent = "- " + discountAmount.toFixed(2) + ' AED';
            discountRow.style.display = 'flex';
        }
    }

</script>

</body>
</html>
