<?php

namespace App\Modules\Orders\Gateways;

use GuzzleHttp\Client as GuzzleClient;
use Illuminate\Validation\ValidationException;
use PayOS\PayOS;
use PayOS\Core\HTTPClient;
use PayOS\Exceptions\ConnectionException;
use PayOS\Exceptions\ConnectionTimeoutError;

class PayOsClient
{
    public function isConfigured(): bool
    {
        return (bool) config('services.payos.enabled');
    }

    /** @param array<string, mixed> $paymentData
     * @return array<string, mixed>
     */
    public function createPayment(array $paymentData): array
    {
        return $this->withNetworkFallback(
            fn (PayOS $client): array => $client->paymentRequests->create($paymentData, [
                'asArray' => true,
                'maxRetries' => 0,
            ]),
        );
    }

    /** @return array<string, mixed> */
    public function getPayment(string|int $id): array
    {
        return $this->withNetworkFallback(
            fn (PayOS $client): array => $client->paymentRequests->get($id, [
                'asArray' => true,
                'maxRetries' => 0,
            ]),
        );
    }

    /** @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    public function verifyWebhook(array $payload): array
    {
        return $this->client()->webhooks->verify($payload, ['asArray' => true]);
    }

    private function client(?int $ipResolve = null): PayOS
    {
        if (! $this->isConfigured()) {
            throw ValidationException::withMessages([
                'payment_method' => 'payOS chưa đủ cấu hình. Cần PAYOS_CLIENT_ID, PAYOS_API_KEY và PAYOS_CHECKSUM_KEY trong .env.',
            ]);
        }

        $httpClient = $ipResolve === null
            ? null
            : new HTTPClient(new GuzzleClient([
                'connect_timeout' => 6,
                'timeout' => 15,
                'curl' => [CURLOPT_IPRESOLVE => $ipResolve],
            ]));

        return new PayOS(
            clientId: (string) config('services.payos.client_id'),
            apiKey: (string) config('services.payos.api_key'),
            checksumKey: (string) config('services.payos.checksum_key'),
            baseURL: (string) config('services.payos.base_url'),
            maxRetries: 0,
            httpClient: $httpClient,
        );
    }

    /**
     * Some local networks expose only one working route to Cloudflare-backed
     * payOS (IPv6 on mobile tethering, IPv4 on most fixed connections).
     * Try the detected/preferred family first, then the other family without
     * making the customer wait through the SDK's repeated identical retries.
     *
     * @template T of array<string, mixed>
     * @param callable(PayOS): T $operation
     * @return T
     */
    private function withNetworkFallback(callable $operation): array
    {
        $lastException = null;

        foreach ($this->ipResolveModes() as $mode) {
            try {
                return $operation($this->client($mode));
            } catch (ConnectionException|ConnectionTimeoutError $exception) {
                $lastException = $exception;
            }
        }

        throw $lastException ?? new ConnectionException('Không thể kết nối tới payOS.');
    }

    /** @return array<int, int|null> */
    private function ipResolveModes(): array
    {
        return match ((string) config('services.payos.ip_resolve', 'auto')) {
            '6' => [CURL_IPRESOLVE_V6, CURL_IPRESOLVE_V4],
            '4' => [CURL_IPRESOLVE_V4, CURL_IPRESOLVE_V6],
            default => [null, CURL_IPRESOLVE_V6, CURL_IPRESOLVE_V4],
        };
    }
}
