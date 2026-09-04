<?php

namespace App\Modules\Orders\Gateways;

use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;
use RuntimeException;

class MomoClient
{
    public function isConfigured(): bool
    {
        return (bool) config('services.momo.enabled');
    }

    /** @return array<string, mixed> */
    public function createPayment(
        string $orderId,
        string $requestId,
        int $amount,
        string $orderInfo,
        string $redirectUrl,
        string $ipnUrl,
    ): array {
        if (! $this->isConfigured()) {
            throw ValidationException::withMessages([
                'payment_method' => 'MoMo chưa đủ cấu hình sandbox. Cần MOMO_PARTNER_CODE, MOMO_ACCESS_KEY và MOMO_SECRET_KEY trong .env.',
            ]);
        }

        $partnerCode = (string) config('services.momo.partner_code');
        $accessKey = (string) config('services.momo.access_key');
        $extraData = base64_encode(json_encode(['order_id' => $orderId], JSON_THROW_ON_ERROR));
        $payload = [
            'partnerCode' => $partnerCode,
            'accessKey' => $accessKey,
            'requestId' => $requestId,
            'amount' => (string) $amount,
            'orderId' => $orderId,
            'orderInfo' => $orderInfo,
            'redirectUrl' => $redirectUrl,
            'ipnUrl' => $ipnUrl,
            'requestType' => 'captureWallet',
            'extraData' => $extraData,
            'lang' => 'vi',
        ];

        $payload['signature'] = hash_hmac('sha256', $this->signaturePayload($payload), (string) config('services.momo.secret_key'));

        $response = Http::acceptJson()
            ->asJson()
            ->timeout((int) config('services.momo.timeout_seconds', 20))
            ->post((string) config('services.momo.endpoint'), $payload);

        $responsePayload = $response->json();

        if (! $response->successful() || ! is_array($responsePayload) || (int) ($responsePayload['resultCode'] ?? -1) !== 0) {
            $message = is_array($responsePayload) ? ($responsePayload['message'] ?? null) : null;
            throw new RuntimeException(trim('Không thể khởi tạo thanh toán MoMo. '.(is_string($message) ? $message : '')));
        }

        if (! is_string($responsePayload['payUrl'] ?? null) || $responsePayload['payUrl'] === '') {
            throw new RuntimeException('MoMo không trả về liên kết thanh toán hợp lệ.');
        }

        return $responsePayload;
    }

    /** @param array<string, mixed> $payload */
    public function verifiesCallback(array $payload): bool
    {
        $signature = $payload['signature'] ?? null;

        if (! is_string($signature) || ! $this->isConfigured()) {
            return false;
        }

        $keys = ['accessKey', 'amount', 'extraData', 'message', 'orderId', 'orderInfo', 'orderType', 'partnerCode', 'payType', 'requestId', 'responseTime', 'resultCode', 'transId'];
        $raw = collect($keys)
            ->map(fn (string $key): string => $key.'='.(string) ($key === 'accessKey' ? config('services.momo.access_key') : ($payload[$key] ?? '')))
            ->implode('&');

        return hash_equals(hash_hmac('sha256', $raw, (string) config('services.momo.secret_key')), $signature);
    }

    /** @param array<string, mixed> $payload */
    private function signaturePayload(array $payload): string
    {
        return collect(['accessKey', 'amount', 'extraData', 'ipnUrl', 'orderId', 'orderInfo', 'partnerCode', 'redirectUrl', 'requestId', 'requestType'])
            ->map(fn (string $key): string => $key.'='.(string) ($payload[$key] ?? ''))
            ->implode('&');
    }
}
