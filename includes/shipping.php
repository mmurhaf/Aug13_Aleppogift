<?php
// File: includes/shipping.php  

require_once(__DIR__ . '/../secure_config.php');
require_once(__DIR__ . '/../includes/Database.php');

$db = new Database();

$country = strtolower(trim($_POST['country'] ?? 'united arab emirates'));
$city = strtolower(trim($_POST['city'] ?? '_'));
$totalWeight = isset($_POST['totalWeight']) ? floatval($_POST['totalWeight']) : 1;

// File: includes/shipping.php  

function calculateShippingCost($country, $city = '', $totalWeight = 1) {
    $cost = 0;

    $country = strtolower(trim($country));
    $city = strtolower(trim($city));

    if ($country === 'united arab emirates') {
        $cost = ($city === 'al gharbia') ? 50 : 30;
    } elseif ($country === 'oman') {
        if ($city === 'muscat') {
            $cost = 60 + max(0, $totalWeight - 5) * 5;
        } elseif ($city === 'salalah') {
            $cost = 70 + max(0, $totalWeight - 5) * 7;
        } else {
            $cost = 60 + max(0, $totalWeight - 5) * 5;
        }
    } elseif (in_array($country, ['kuwait', 'saudi arabia', 'qatar', 'bahrain'])) {
        $cost = 120 + max(0, $totalWeight - 1) * 30;
    } elseif (in_array($country, ['germany', 'france', 'italy', 'spain', 'united kingdom'])) {
        $cost = 220 + max(0, $totalWeight - 1) * 70;
    } else {
        $cost = 300 + max(0, $totalWeight - 1) * 80;
    }

    if (!is_numeric($cost)) {
    echo json_encode(['success' => false, 'error' => 'Invalid shipping cost']);
    $cost = 0;
    exit;
}
    return $cost;
}
