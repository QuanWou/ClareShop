<?php

namespace App\Modules\Orders\Gateways;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;
use RuntimeException;

class PayPalClient
{
    public function isConfigured(): bool
    {
        return filled(config('services.paypal.client_id'))
            && filled(config('services.paypal.client_secret'));
    }

    /** @return array<string, mixed> */
    public function createOrder(
        string $orderNumber,
        int $paymentId,
        string $amount,
        string $currency,
        string $returnUrl,
        string $cancelUrl,
    ): array {
        $response = $this->request()
            ->withHeaders(['PayPal-Request-Id' => "clare-create-{$paymentId}"])
            ->post($this->baseUrl().'/v2/checkout/orders', [
                'intent' => 'CAPTURE',
                'purchase_units' => [[
                    'reference_id' => $orderNumber,
                    'custom_id' => (string) $paymentId,
                    'description' => "Đơn hàng Clare {$orderNumber}",
                    'amount' => [
                        'currency_code' => $currency,
                        'value' => $amount,
                    ],
                ]],
                'payment_source' => [
                    'paypal' => [
                        'experience_context' => [
                            'brand_name' => 'CLARE',
                            'shipping_preference' => 'NO_SHIPPING',
                            'user_action' => 'PAY_NOW',
                            'return_url' => $returnUrl,
                            'cancel_url' => $cancelUrl,
                        ],
                    ],
                ],
            ]);

        $this->ensureSuccessful($response->successful(), $response->json(), 'Không thể tạo giao dịch PayPal.');

        return $response->json();
    }

    /** @return array<string, mixed> */
    public function captureOrder(string $providerOrderId, int $paymentId): array
    {
        $response = $this->request()
            ->withHeaders(['PayPal-Request-Id' => "clare-capture-{$paymentId}"])
            ->withBody('{}', 'application/json')
            ->post($this->baseUrl()."/v2/checkout/orders/{$providerOrderId}/capture");

        $this->ensureSuccessful($response->successful(), $response->json(), 'PayPal chưa thể xác nhận giao dịch.');

        return $response->json();
    }

    /** @param array<string, string|null> $headers @param array<string, mixed> $event */
    public function verifyWebhook(array $headers, array $event): bool
    {
        $webhookId = config('services.paypal.webhook_id');

        if (blank($webhookId)) {
            throw ValidationException::withMessages([
                'paypal' => 'PAYPAL_WEBHOOK_ID chưa được cấu hình.',
            ]);
        }

        $response = $this->request()->post($this->baseUrl().'/v1/notifications/verify-webhook-signature', [
            'auth_algo' => $headers['paypal-auth-algo'] ?? null,
            'cert_url' => $headers['paypal-cert-url'] ?? null,
            'transmission_id' => $headers['paypal-transmission-id'] ?? null,
            'transmission_sig' => $headers['paypal-transmission-sig'] ?? null,
            'transmission_time' => $headers['paypal-transmission-time'] ?? null,
            'webhook_id' => $webhookId,
            'webhook_event' => $event,
        ]);

        $this->ensureSuccessful($response->successful(), $response->json(), 'Không thể xác minh webhook PayPal.');

        return $response->json('verification_status') === 'SUCCESS';
    }

    private function request(): PendingRequest
    {
        return Http::acceptJson()
            ->asJson()
            ->withToken($this->accessToken())
            ->timeout((int) config('services.paypal.timeout_seconds', 20));
    }

    private function accessToken(): string
    {
        if (! $this->isConfigured()) {
            throw ValidationException::withMessages([
                'payment_method' => 'PayPal chưa được cấu hình. Hãy đặt PAYPAL_CLIENT_ID và PAYPAL_CLIENT_SECRET trong .env.',
            ]);
        }

        $clientId = (string) config('services.paypal.client_id');
        $cacheKey = 'paypal-access-token:'.hash('sha256', $this->mode().$clientId);

        return Cache::remember($cacheKey, now()->addMinutes(8), function () use ($clientId): string {
            $response = Http::asForm()
                ->acceptJson()
                ->withBasicAuth($clientId, (string) config('services.paypal.client_secret'))
                ->timeout((int) config('services.paypal.timeout_seconds', 20))
                ->post($this->baseUrl().'/v1/oauth2/token', ['grant_type' => 'client_credentials']);

            $this->ensureSuccessful($response->successful(), $response->json(), 'Không thể đăng nhập PayPal API.');

            $token = $response->json('access_token');

            if (! is_string($token) || $token === '') {
                throw new RuntimeException('PayPal không trả về access token hợp lệ.');
            }

            return $token;
        });
    }

    private function mode(): string
    {
        return strtolower((string) config('services.paypal.mode', 'sandbox'));
    }

    private function baseUrl(): string
    {
        return $this->mode() === 'live'
            ? 'https://api-m.paypal.com'
            : 'https://api-m.sandbox.paypal.com';
    }

    /** @param array<string, mixed>|null $payload */
    private function ensureSuccessful(bool $successful, ?array $payload, string $message): void
    {
        if ($successful) {
            return;
        }

        $debugId = is_array($payload) ? ($payload['debug_id'] ?? null) : null;
        $detail = is_array($payload) ? data_get($payload, 'details.0.description') : null;

        throw new RuntimeException(trim($message.' '.($detail ?: '').($debugId ? " Mã đối soát: {$debugId}." : '')));
    }
}
