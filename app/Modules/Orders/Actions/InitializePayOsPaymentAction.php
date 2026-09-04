<?php

namespace App\Modules\Orders\Actions;

use App\Modules\Orders\Gateways\PayOsClient;
use App\Modules\Orders\Models\Payment;
use App\Modules\Orders\Models\PaymentStatusHistory;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class InitializePayOsPaymentAction
{
    public function __construct(
        private readonly PayOsClient $client,
        private readonly EnsureCurrentPaymentAttemptAction $ensureCurrentPaymentAttempt,
    ) {}

    public function execute(Payment $payment): Payment
    {
        if ($payment->provider !== 'payos') {
            throw ValidationException::withMessages(['payment' => 'Thanh toán này không thuộc payOS.']);
        }

        return Cache::lock("payos-initialize:{$payment->getKey()}", 20)->block(5, function () use ($payment): Payment {
            $payment = Payment::query()->with('order')->findOrFail($payment->getKey());
            $this->ensureCurrentPaymentAttempt->execute($payment->order, $payment);

            if ($payment->status === 'paid' || (filled($payment->approval_url) && $payment->expires_at?->isFuture())) {
                return $payment;
            }

            try {
                $expiresAt = now()->addSeconds((int) config('checkout.payment.qr_timeout_seconds', 180));
                $orderCode = $this->generateOrderCode($payment);
                $gatewayPayment = $this->client->createPayment([
                    'orderCode' => $orderCode,
                    'amount' => (int) $payment->amount,
                    'description' => 'CLARE '.$payment->order_id,
                    'buyerName' => $payment->order->customer_name,
                    'buyerEmail' => $payment->order->customer_email,
                    'buyerPhone' => $payment->order->customer_phone,
                    'cancelUrl' => route('payments.payos.cancel'),
                    'returnUrl' => route('payments.payos.return'),
                    'expiredAt' => $expiresAt->getTimestamp(),
                ]);

                return DB::transaction(function () use ($payment, $orderCode, $expiresAt, $gatewayPayment): Payment {
                    $lockedPayment = Payment::query()->lockForUpdate()->findOrFail($payment->getKey());
                    $previousStatus = $lockedPayment->status;
                    $lockedPayment->update([
                        'provider_reference' => (string) $orderCode,
                        'approval_url' => (string) ($gatewayPayment['checkoutUrl'] ?? ''),
                        'expires_at' => $expiresAt,
                        'status' => 'pending',
                        'failure_reason' => null,
                        'payload' => [
                            'qr_code' => $gatewayPayment['qrCode'] ?? null,
                            'checkout_url' => $gatewayPayment['checkoutUrl'] ?? null,
                            'payment_link_id' => $gatewayPayment['paymentLinkId'] ?? null,
                            'order_code' => $gatewayPayment['orderCode'] ?? $orderCode,
                            'bank_id' => $gatewayPayment['bin'] ?? null,
                            'account_number' => $gatewayPayment['accountNumber'] ?? null,
                            'account_name' => $gatewayPayment['accountName'] ?? null,
                            'transfer_content' => $gatewayPayment['description'] ?? null,
                            'amount' => (int) ($gatewayPayment['amount'] ?? $lockedPayment->amount),
                            'currency' => $gatewayPayment['currency'] ?? 'VND',
                        ],
                    ]);
                    $lockedPayment->order()->update(['payment_status' => 'pending']);

                    if ($previousStatus !== 'pending') {
                        PaymentStatusHistory::query()->create([
                            'payment_id' => $lockedPayment->getKey(),
                            'from_status' => $previousStatus,
                            'to_status' => 'pending',
                            'changed_by' => null,
                            'note' => 'Khách hàng tạo phiên thanh toán payOS mới.',
                        ]);
                    }

                    return $lockedPayment->fresh('order');
                });
            } catch (\Throwable $exception) {
                $this->recordFailure($payment, $exception->getMessage());
                throw $exception;
            }
        });
    }

    private function generateOrderCode(Payment $payment): int
    {
        return ((int) now()->format('ymdHis') * 1000) + ((int) $payment->getKey() % 1000);
    }

    private function recordFailure(Payment $payment, string $reason): void
    {
        DB::transaction(function () use ($payment, $reason): void {
            $lockedPayment = Payment::query()->lockForUpdate()->findOrFail($payment->getKey());
            $previousStatus = $lockedPayment->status;
            $lockedPayment->update([
                'status' => 'failed',
                'failure_reason' => mb_substr($reason, 0, 2000),
                'approval_url' => null,
            ]);
            $lockedPayment->order()->update(['payment_status' => 'failed']);

            if ($previousStatus !== 'failed') {
                PaymentStatusHistory::query()->create([
                    'payment_id' => $lockedPayment->getKey(),
                    'from_status' => $previousStatus,
                    'to_status' => 'failed',
                    'changed_by' => null,
                    'note' => 'Không thể khởi tạo giao dịch payOS.',
                ]);
            }
        });
    }
}
