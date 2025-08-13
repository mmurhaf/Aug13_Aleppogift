<?php
session_start();
require_once(__DIR__ . '/../../secure_config.php');
require_once('../includes/Database.php');
require_once('../includes/fpdf/fpdf.php');
require_once('../includes/send_email.php');

$db = new Database();

if (!$_SESSION['cart']) {
    header("Location: cart.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $fullname = trim($_POST['fullname']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $address = trim($_POST['address']);
    $city = trim($_POST['city']);

    $country = trim($_POST['country']);
    $payment_method = $_POST['payment_method'];

    // Server-side restriction (security)
    if (strtolower($country) !== 'uae' && strtolower($country) !== 'united arab emirates' && $payment_method === 'COD') {
        die("COD is not available for your country.");
    }



    // Insert customer
    $db->query("INSERT INTO customers (fullname, email, phone, address, city, country) 
        VALUES (:fullname, :email, :phone, :address, :city, :country)", [
        'fullname' => $fullname,
        'email' => $email,
        'phone' => $phone,
        'address' => $address,
        'city' => $city,
        'country' => $country
    ]);
    $customer_id = $db->lastInsertId();

    // Calculate total
    $grandTotal = 0;
    foreach ($_SESSION['cart'] as $item) {
        $product = $db->query("SELECT * FROM products WHERE id = :id", ['id' => $item['product_id']])->fetch(PDO::FETCH_ASSOC);
        $price = $product['price'];
        if ($item['variation_id']) {
            $variation = $db->query("SELECT * FROM product_variations WHERE id = :id", ['id' => $item['variation_id']])->fetch(PDO::FETCH_ASSOC);
            $price += $variation['additional_price'];
        }
        $grandTotal += $price * $item['quantity'];
    }

    // Insert order
$db->query("INSERT INTO orders (customer_id, total_amount, payment_status, payment_method) 
    VALUES (:customer_id, :total, :payment_status, :payment_method)", [
    'customer_id' => $customer_id,
    'total' => $grandTotal,
    'payment_status' => ($payment_method === 'COD') ? 'pending' : 'paid',
    'payment_method' => $payment_method
]);

    $order_id = $db->lastInsertId();

require_once('../includes/whatsapp_notify.php');
sendAdminWhatsApp($order_id, $fullname, $grandTotal, $payment_method);

    // Insert items
    foreach ($_SESSION['cart'] as $item) {
        $product = $db->query("SELECT * FROM products WHERE id = :id", ['id' => $item['product_id']])->fetch(PDO::FETCH_ASSOC);
        $price = $product['price'];
        if ($item['variation_id']) {
            $variation = $db->query("SELECT * FROM product_variations WHERE id = :id", ['id' => $item['variation_id']])->fetch(PDO::FETCH_ASSOC);
            $price += $variation['additional_price'];
        }
        $db->query("INSERT INTO order_items (order_id, product_id, variation_id, quantity, price) 
            VALUES (:order_id, :product_id, :variation_id, :qty, :price)", [
            'order_id' => $order_id,
            'product_id' => $item['product_id'],
            'variation_id' => $item['variation_id'],
            'qty' => $item['quantity'],
            'price' => $price
        ]);
    }

    // Generate PDF invoice
    $fullPath = "../invoice/invoice_$order_id.pdf";
    $pdf = new FPDF();
    $pdf->AddPage();
    $pdf->SetFont('Arial','B',16);
    $pdf->Cell(0,10,'Invoice - AleppoGift',0,1,'C');
    $pdf->SetFont('Arial','',12);
    $pdf->Ln(5);
    $pdf->Cell(100,10,"Order ID: #$order_id",0,1);
    $pdf->Cell(100,10,"Customer: $fullname",0,1);
    $pdf->Cell(100,10,"Email: $email",0,1);
    $pdf->Cell(100,10,"Total: AED $grandTotal",0,1);
    $pdf->Ln(10);

    $pdf->SetFont('Arial','B',12);
    $pdf->Cell(80,10,'Product',1);
    $pdf->Cell(30,10,'Qty',1);
    $pdf->Cell(40,10,'Price',1);
    $pdf->Cell(40,10,'Total',1);
    $pdf->Ln();

    $pdf->SetFont('Arial','',12);
    foreach ($_SESSION['cart'] as $item) {
        $product = $db->query("SELECT * FROM products WHERE id = :id", ['id' => $item['product_id']])->fetch(PDO::FETCH_ASSOC);
        $price = $product['price'];
        if ($item['variation_id']) {
            $variation = $db->query("SELECT * FROM product_variations WHERE id = :id", ['id' => $item['variation_id']])->fetch(PDO::FETCH_ASSOC);
            $price += $variation['additional_price'];
        }
        $total = $price * $item['quantity'];
        $pdf->Cell(80,10,$product['name_en'],1);
        $pdf->Cell(30,10,$item['quantity'],1);
        $pdf->Cell(40,10,$price,1);
        $pdf->Cell(40,10,$total,1);
        $pdf->Ln();
    }

    $pdf->Output('F', $fullPath);

    // Send email
    sendInvoiceEmail($email, $order_id, $fullPath);

    $_SESSION['cart'] = [];
    header("Location: thankyou.php?order=$order_id");
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Checkout - AleppoGift</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>

<h2>Checkout</h2>

<form method="post">
        <script>
        document.getElementById('country').addEventListener('change', function() {
            const country = this.value.toLowerCase();
            const codOption = document.querySelector('option[value="COD"]');

            if (country !== 'united arab emirates') {
                codOption.disabled = true;
                if (document.getElementById('payment_method').value === 'COD') {
                    document.getElementById('payment_method').value = 'Ziina';
                }
            } else {
                codOption.disabled = false;
            }
        });
        </script>


    Full Name: <input type="text" name="fullname" required><br><br>
    Email: <input type="email" name="email" required><br><br>
    Phone: <input type="text" name="phone" required><br><br>
    Address: <input type="text" name="address" required><br><br>
    City: <input type="text" name="city" required><br><br>
    <label>Country:</label>
<select id="country" name="country" required>
  <option value="United Arab Emirates" selected>🇦🇪 United Arab Emirates</option>
  <option value="Saudi Arabia">🇸🇦 Saudi Arabia</option>
  <option value="Kuwait">🇰🇼 Kuwait</option>
  <option value="Qatar">🇶🇦 Qatar</option>
  <option value="Bahrain">🇧🇭 Bahrain</option>
  <option value="Oman">🇴🇲 Oman</option>
  <option value="Egypt">🇪🇬 Egypt</option>
  <option value="Jordan">🇯🇴 Jordan</option>
  <option value="Lebanon">🇱🇧 Lebanon</option>
  <option value="Turkey">🇹🇷 Turkey</option>
  <option value="India">🇮🇳 India</option>
  <option value="Pakistan">🇵🇰 Pakistan</option>
  <option value="Philippines">🇵🇭 Philippines</option>
  <option value="United States">🇺🇸 United States</option>
  <option value="United Kingdom">🇬🇧 United Kingdom</option>
  <option value="Germany">🇩🇪 Germany</option>
  <option value="France">🇫🇷 France</option>
  <option value="Italy">🇮🇹 Italy</option>
  <option value="Spain">🇪🇸 Spain</option>
  <option value="Canada">🇨🇦 Canada</option>
  <option value="Australia">🇦🇺 Australia</option>
  <option value="Russia">🇷🇺 Russia</option>
  <option value="China">🇨🇳 China</option>
  <option value="Japan">🇯🇵 Japan</option>
  <option value="South Korea">🇰🇷 South Korea</option>
  <option value="Indonesia">🇮🇩 Indonesia</option>
  <option value="Malaysia">🇲🇾 Malaysia</option>
  <option value="Thailand">🇹🇭 Thailand</option>
  <option value="Brazil">🇧🇷 Brazil</option>
  <option value="Mexico">🇲🇽 Mexico</option>
  <option value="South Africa">🇿🇦 South Africa</option>
  <option value="Nigeria">🇳🇬 Nigeria</option>
  <option value="Other">🌍 Other</option>
</select>
<br><br>

<label>Payment Method:</label><br>
<select id="payment_method" name="payment_method" required>
    <option value="COD">💵 Cash on Delivery</option>
    <option value="Ziina">💳 Ziina (Online Payment)</option>
</select><br><br>


    <input type="submit" value="Place Order">
</form>

</body>
</html>
