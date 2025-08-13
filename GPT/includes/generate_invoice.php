<?php
require_once(__DIR__ . '/../../secure_config.php');
require_once('../includes/Database.php');
require_once('../includes/fpdf/fpdf.php');

$db = new Database();

if (!isset($_GET['order'])) {
    die("Order ID missing.");
}

$order_id = (int)$_GET['order'];
$order = $db->query("SELECT o.*, c.fullname, c.email, c.address, c.city, c.country 
    FROM orders o 
    JOIN customers c ON o.customer_id = c.id 
    WHERE o.id = :id", ['id' => $order_id])->fetch(PDO::FETCH_ASSOC);

if (!$order) {
    die("Order not found.");
}

$items = $db->query("SELECT oi.*, p.name_en FROM order_items oi 
    JOIN products p ON oi.product_id = p.id 
    WHERE oi.order_id = :id", ['id' => $order_id])->fetchAll(PDO::FETCH_ASSOC);

// Create PDF
$pdf = new FPDF();
$pdf->AddPage();
$pdf->SetFont('Arial','B',16);

$pdf->Cell(0,10,'Invoice - AleppoGift',0,1,'C');
$pdf->SetFont('Arial','',12);
$pdf->Ln(5);

$pdf->Cell(100,10,'Customer: ' . $order['fullname'],0,1);
$pdf->Cell(100,10,'Email: ' . $order['email'],0,1);
$pdf->Cell(100,10,'Address: ' . $order['address'] . ', ' . $order['city'] . ', ' . $order['country'],0,1);
$pdf->Cell(100,10,'Order ID: #' . $order_id,0,1);
$pdf->Cell(100,10,'Date: ' . $order['order_date'],0,1);
$pdf->Ln(10);

$pdf->SetFont('Arial','B',12);
$pdf->Cell(80,10,'Product',1);
$pdf->Cell(30,10,'Qty',1);
$pdf->Cell(40,10,'Price (AED)',1);
$pdf->Cell(40,10,'Total (AED)',1);
$pdf->Ln();

$pdf->SetFont('Arial','',12);
foreach ($items as $item) {
    $lineTotal = $item['price'] * $item['quantity'];
    $pdf->Cell(80,10,$item['name_en'],1);
    $pdf->Cell(30,10,$item['quantity'],1);
    $pdf->Cell(40,10,$item['price'],1);
    $pdf->Cell(40,10,$lineTotal,1);
    $pdf->Ln();
}

$pdf->Ln();
$pdf->Cell(100,10,'Total Paid: AED ' . $order['total_amount'],0,1);

$pdf->Output();
