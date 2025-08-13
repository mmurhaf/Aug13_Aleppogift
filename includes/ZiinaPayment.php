<?php
class ZiinaPayment {
    private $apiUrl = 'https://api-v2.ziina.com/api/payment_intent';
    private $secretKey = 'Cyhd+lvjI8qiXN50qklhMjtp4g7qQTEHE7w1vvJ5nU1MbO7nAmGzA30SvOi05/VI';
	//private $testMode = true;
	private $testMode = true;

    public function createPaymentIntent($order_id, $amountAED, $message = 'AleppoGift Order') {
        $payload = [
            "amount" => (int) round($amountAED * 100),
            "currency_code" => "AED",
            "message" => "$message #$order_id",
            "success_url" => "https://www.aleppogift.com/thankyou.php?order=$order_id",
            "cancel_url"  => "https://www.aleppogift.com/checkout.php?order=$order_id",
            "failure_url" => "https://www.aleppogift.com/checkout.php?order=$order_id",
            "test" => $this->testMode,
            "transaction_source" => "directApi",
            "expiry" => (string)(round(microtime(true) * 1000) + 86400000), // 24h in ms
            "allow_tips" => true
        ];

        $headers = [
            "Authorization: Bearer {$this->secretKey}",
            "Content-Type: application/json"
        ];

        $ch = curl_init($this->apiUrl);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($payload),
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_SSL_VERIFYPEER => true
        ]);

        $response = curl_exec($ch);
        $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            return ['success' => false, 'error' => "cURL Error: $error"];
        }

        $json = json_decode($response, true);

        // ✅ Correct new structure handling
        if ($status === 201 && isset($json['redirect_url'])) {
            return [
                'success' => true,
                'payment_url' => $json['redirect_url'],
                'payment_id' => $json['id'] ?? null
            ];
        }

        return [
            'success' => false,
            'error' => $json['message'] ?? 'Unknown Ziina error',
            'full_response' => $json
        ];
    }
}
// Example usage