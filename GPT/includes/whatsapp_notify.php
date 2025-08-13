<?php
function sendAdminWhatsApp($orderId, $name, $total, $method = 'COD') {
    $phone = '971561125320';
    $apikey = '5574813';

    $methodLabel = ($method === 'COD') ? '💵 COD' : '💳 Paid';
    $message = "📦 New Order - AleppoGift%0AOrder ID: #$orderId%0A$methodLabel%0ACustomer: $name%0ATotal: AED $total";

    $url = "https://api.callmebot.com/whatsapp.php?phone=$phone&text=$message&apikey=$apikey";
    file_get_contents($url);
}

