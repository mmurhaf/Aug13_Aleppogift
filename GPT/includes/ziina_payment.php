<?php
require_once __DIR__ . '/../../secure_config.php';
require_once 'Database.php';

class ZiinaPayment {
    private $apiUrl = 'https://api.ziina.com/payment_intent'; // Replace with actual endpoint
    private $secretKey = 'Cyhd+lvjI8qiXN50qklhMjtp4g7qQTEHE7w1vvJ5nU1MbO7nAmGzA30SvOi05/VI';

    public function createPaymentIntent($order_id, $amount, $currency = 'AED', $redirect_url = '') {
        $data = [
            'order_id' => $order_id,
            'amount' => $amount * 100, // Convert to fils
            'currency' => $currency,
            'redirect_url' => $redirect_url
        ];

        $headers = [
            "Authorization: Bearer {$this->secretKey}",
            "Content-Type: application/json"
        ];

        $ch = curl_init($this->apiUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        $response = curl_exec($ch);
        $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($status == 200) {
            return json_decode($response, true);
        } else {
            return false;
        }
    }

    public function updatePaymentStatus($payment_id, $status) {
        $db = new Database();
        $stmt = $db->connect()->prepare("UPDATE orders SET payment_status = ? WHERE payment_id = ?");
        return $stmt->execute([$status, $payment_id]);
    }
}
