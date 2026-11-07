<?php

namespace App\Services;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class PayWayService
{
    private const QR_ENDPOINT = '/api/payment-gateway/v1/payments/generate-qr';
    private const CHECK_ENDPOINT = '/api/payment-gateway/v1/payments/check-transaction-2';

    public function isConfigured(): bool
    {
        return filled(config('services.payway.merchant_id'))
            && filled(config('services.payway.api_key'));
    }

    /**
     * Request an ABA KHQR. Hash field order follows ABA's QR API documentation.
     * All credentials are read from config/services.php (never from a request).
     */
    public function generateQr(array $input): array
    {
        $this->assertConfigured();

        $config = config('services.payway');
        $items = $input['items'] ?? [];
        $itemsEncoded = is_string($items)
            ? $items
            : base64_encode((string) json_encode($items, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

        $callbackUrl = (string) ($input['callback_url'] ?? '');
        $callbackParts = parse_url($callbackUrl);
        $callbackHost = strtolower((string) ($callbackParts['host'] ?? ''));
        if (($callbackParts['scheme'] ?? '') !== 'https'
            || $callbackHost === ''
            || in_array($callbackHost, ['localhost', '127.0.0.1', '::1'], true)
            || str_ends_with($callbackHost, '.local')) {
            throw new RuntimeException('Set PAYWAY_CALLBACK_URL in .env to your public HTTPS callback URL (for local testing, use your ngrok HTTPS URL).');
        }

        $payload = [
            'req_time' => gmdate('YmdHis'),
            'merchant_id' => (string) $config['merchant_id'],
            'tran_id' => (string) $input['tran_id'],
            'first_name' => (string) ($input['first_name'] ?? ''),
            'last_name' => (string) ($input['last_name'] ?? ''),
            'email' => (string) ($input['email'] ?? ''),
            'phone' => (string) ($input['phone'] ?? ''),
            'amount' => number_format((float) $input['amount'], 2, '.', ''),
            'purchase_type' => 'purchase',
            'payment_option' => (string) ($config['payment_option'] ?? 'abapay_khqr'),
            'items' => $itemsEncoded,
            'currency' => strtoupper((string) ($input['currency'] ?? $config['currency'] ?? 'USD')),
            'callback_url' => base64_encode($callbackUrl),
            'return_deeplink' => null,
            'custom_fields' => null,
            'return_params' => json_encode($input['return_params'] ?? [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'payout' => null,
            'lifetime' => (int) ($config['lifetime'] ?? 15),
            'qr_image_template' => (string) ($config['qr_image_template'] ?? 'template3_color'),
        ];

        $hashFields = [
            'req_time', 'merchant_id', 'tran_id', 'amount', 'items', 'first_name',
            'last_name', 'email', 'phone', 'purchase_type', 'payment_option',
            'callback_url', 'return_deeplink', 'currency', 'custom_fields',
            'return_params', 'payout', 'lifetime', 'qr_image_template',
        ];

        $payload['hash'] = $this->makeHash($payload, $hashFields);
        $response = $this->post(self::QR_ENDPOINT, $payload);
        $json = $response->json();
        $this->assertPayWaySuccess($json, 'QR generation');

        if (empty($json['qrString'])) {
            throw new RuntimeException('ABA PayWay returned a success response without a QR string.');
        }

        return $json;
    }

    /** Check a transaction server-to-server; a browser callback is never proof of payment. */
    public function checkTransaction(string $transactionId): array
    {
        $this->assertConfigured();

        if ($transactionId === '' || strlen($transactionId) > 20) {
            throw new RuntimeException('Invalid PayWay transaction ID.');
        }

        $payload = [
            'req_time' => gmdate('YmdHis'),
            'merchant_id' => (string) config('services.payway.merchant_id'),
            'tran_id' => $transactionId,
        ];

        // ABA Check Transaction hash: req_time + merchant_id + tran_id, signed with the API key.
        $payload['hash'] = $this->makeHash($payload, ['req_time', 'merchant_id', 'tran_id']);

        $response = $this->post(self::CHECK_ENDPOINT, $payload);
        $json = $response->json();
        $this->assertPayWaySuccess($json, 'transaction status check');

        if (! isset($json['data']) || ! is_array($json['data'])) {
            throw new RuntimeException('ABA PayWay did not return transaction data.');
        }

        return $json;
    }

    private function post(string $endpoint, array $payload): Response
    {
        $baseUrl = rtrim((string) config('services.payway.base_url'), '/');
        if (! str_starts_with($baseUrl, 'https://')) {
            throw new RuntimeException('PayWay base URL must use HTTPS.');
        }

        $response = Http::acceptJson()
            ->asJson()
            ->connectTimeout(8)
            ->timeout(20)
            ->post($baseUrl.$endpoint, $payload);

        if (! $response->successful()) {
            throw new RuntimeException('ABA PayWay HTTP error: '.$response->status());
        }

        return $response;
    }

    private function assertPayWaySuccess(mixed $json, string $operation): void
    {
        if (! is_array($json)) {
            throw new RuntimeException('ABA PayWay returned an invalid JSON response during '.$operation.'.');
        }

        $code = (string) data_get($json, 'status.code', '');
        if (! in_array($code, ['0', '00'], true)) {
            $message = (string) data_get($json, 'status.message', 'Unknown PayWay error');
            throw new RuntimeException('ABA PayWay '.$operation.' failed: '.$message.' (code '.$code.').');
        }
    }

    private function makeHash(array $payload, array $fields): string
    {
        $plain = '';
        foreach ($fields as $field) {
            $value = $payload[$field] ?? '';
            if (is_array($value) || is_object($value)) {
                $value = json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            }
            $plain .= (string) $value;
        }

        return base64_encode(hash_hmac(
            'sha512',
            $plain,
            (string) config('services.payway.api_key'),
            true
        ));
    }

    private function assertConfigured(): void
    {
        if (! $this->isConfigured()) {
            throw new RuntimeException('ABA PayWay is not configured. Add PAYWAY_MERCHANT_ID and PAYWAY_API_KEY to your .env file.');
        }
    }
}
