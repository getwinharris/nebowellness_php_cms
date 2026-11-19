<?php
namespace App\Integrations\Razorpay;
final class RazorpayClient {
    public function __construct(private string $keyId, private string $keySecret){}
    public function fetchPayment(string $paymentId): array {
        return $this->get('/payments/' . rawurlencode($paymentId));
    }

    /** Razorpay requires a unique receipt; a lost order-creation response can be recovered by receipt. */
    public function findOrderByReceipt(string $receipt): ?array {
        $list = $this->get('/orders?' . http_build_query(['receipt'=>$receipt, 'count'=>2]));
        if (!isset($list['items']) || !is_array($list['items'])) throw new \RuntimeException('Invalid order lookup response.');
        foreach ($list['items'] as $order) {
            if (($order['receipt'] ?? '') === $receipt) return $order;
        }
        return null;
    }

    private function get(string $path): array {
        $ch = curl_init('https://api.razorpay.com/v1' . $path);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_USERPWD => $this->keyId . ':' . $this->keySecret,
            CURLOPT_CONNECTTIMEOUT => 5, CURLOPT_TIMEOUT => 20,
        ]);
        $body = curl_exec($ch);
        $status = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $decoded = is_string($body) ? json_decode($body, true) : null;
        if ($status < 200 || $status >= 300 || !is_array($decoded)) throw new \RuntimeException('Razorpay lookup failed.', $status ?: 503);
        return $decoded;
    }

    public function createOrder(int $amountPaise, string $receipt): array {
        $payload = json_encode(['amount'=>$amountPaise,'currency'=>'INR','receipt'=>$receipt]);
        $ch = curl_init('https://api.razorpay.com/v1/orders');
        curl_setopt_array($ch,[
            CURLOPT_RETURNTRANSFER=>true,
            CURLOPT_POST=>true,
            CURLOPT_POSTFIELDS=>$payload,
            CURLOPT_HTTPHEADER=>['Content-Type: application/json'],
            CURLOPT_USERPWD=>$this->keyId.':'.$this->keySecret,
            CURLOPT_TIMEOUT=>20,
        ]);
        $body = curl_exec($ch);
        $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);

        $decoded = is_string($body) && $body !== '' ? json_decode($body, true) : null;
        if ($status === 401) {
            throw new \RuntimeException('Razorpay authentication failed', 401);
        }
        if ($status >= 300 || !is_array($decoded)) {
            $message = $decoded['error']['description'] ?? $error ?: 'Razorpay order creation failed';
            throw new \RuntimeException($message, $status ?: 500);
        }
        return $decoded;
    }
}
