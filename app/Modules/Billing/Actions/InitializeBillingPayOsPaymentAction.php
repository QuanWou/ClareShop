<?php

namespace App\Modules\Billing\Actions;

use App\Modules\Billing\Models\BillingPaymentAttempt;
use App\Modules\Orders\Gateways\PayOsClient;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\ValidationException;

class InitializeBillingPayOsPaymentAction
{
    public function __construct(private readonly PayOsClient $client) {}

    public function execute(BillingPaymentAttempt $attempt): BillingPaymentAttempt
    {
        if ($attempt->provider !== 'payos') {
            throw ValidationException::withMessages(['payment_method' => 'Giao dịch này không thuộc payOS.']);
        }

        return Cache::lock("billing-payos-initialize:{$attempt->getKey()}", 20)->block(5, function () use ($attempt): BillingPaymentAttempt {
            $attempt = BillingPaymentAttempt::query()->with(['user', 'payLaterPurchase.order'])->findOrFail($attempt->getKey());
            if ($attempt->status === 'paid' || (filled($attempt->approval_url) && $attempt->expires_at?->isFuture())) {
                return $attempt;
            }

            $expiresAt = now()->addSeconds((int) config('checkout.payment.qr_timeout_seconds', 180));
            $orderCode = ((int) now()->format('ymdHis') * 1000) + ((int) $attempt->getKey() % 1000);
            $description = $attempt->purpose === 'clare_pay_top_up'
                ? 'NAP CLARE '.$attempt->getKey()
                : 'TRA SAU '.$attempt->payLaterPurchase?->order_id;

            try {
                $gateway = $this->client->createPayment([
                    'orderCode' => $orderCode,
                    'amount' => (int) $attempt->amount,
                    'description' => mb_substr($description, 0, 25),
                    'buyerName' => $attempt->user->name,
                    'buyerEmail' => $attempt->user->email,
                    'buyerPhone' => $attempt->user->phone,
                    'cancelUrl' => route('billing.payos.cancel'),
                    'returnUrl' => route('billing.payos.return'),
                    'expiredAt' => $expiresAt->getTimestamp(),
                ]);

                $attempt->update([
                    'provider_reference' => (string) $orderCode,
                    'approval_url' => (string) ($gateway['checkoutUrl'] ?? ''),
                    'expires_at' => $expiresAt,
                    'status' => 'pending',
                    'failure_reason' => null,
                    'payload' => [
                        'payment_link_id' => $gateway['paymentLinkId'] ?? null,
                        'qr_code' => $gateway['qrCode'] ?? null,
                        'checkout_url' => $gateway['checkoutUrl'] ?? null,
                        'order_code' => $gateway['orderCode'] ?? $orderCode,
                        'amount' => (int) ($gateway['amount'] ?? $attempt->amount),
                    ],
                ]);

                return $attempt->fresh();
            } catch (\Throwable $exception) {
                $attempt->update(['status' => 'failed', 'failure_reason' => mb_substr($exception->getMessage(), 0, 2000)]);
                $attempt->clarePayTransaction?->update(['status' => 'failed']);
                if ($attempt->payLaterPurchase) {
                    $attempt->payLaterPurchase->update(['status' => 'failed', 'latest_failure_reason' => 'Không thể tạo phiên payOS.']);
                }
                throw $exception;
            }
        });
    }
}
