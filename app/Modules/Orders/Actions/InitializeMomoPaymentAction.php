<?php

namespace App\Modules\Orders\Actions;

use App\Modules\Orders\Gateways\MomoClient;
use App\Modules\Orders\Models\Payment;
use App\Modules\Orders\Models\PaymentStatusHistory;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class InitializeMomoPaymentAction
{
    public function __construct(
        private readonly MomoClient $client,
        private readonly EnsureCurrentPaymentAttemptAction $ensureCurrentPaymentAttempt,
    ) {}

    public function execute(Payment $payment): Payment
    {
        if ($payment->provider !== 'momo') {
            throw ValidationException::withMessages(['payment' => 'Thanh toán này không thuộc MoMo.']);
        }

        return Cache::lock("momo-initialize:{$payment->getKey()}", 20)->block(5, function () use ($payment): Payment {
            $payment = Payment::query()->with('order')->findOrFail($payment->getKey());
            $this->ensureCurrentPaymentAttempt->execute($payment->order, $payment);

            if ($payment->status === 'paid' || (filled($payment->approval_url) && $payment->expires_at?->isFuture())) {
                return $payment;
            }

            try {
                $requestId = 'CLR-MOMO-'.$payment->getKey().'-'.Str::lower(Str::random(12));
                $gatewayPayment = $this->client->createPayment(
                    orderId: $payment->order->number,
                    requestId: $requestId,
                    amount: (int) $payment->amount,
                    orderInfo: "Đơn hàng Clare {$payment->order->number}",
                    redirectUrl: route('payments.momo.return'),
                    ipnUrl: route('webhooks.momo'),
                );

                return DB::transaction(function () use ($payment, $requestId, $gatewayPayment): Payment {
                    $lockedPayment = Payment::query()->lockForUpdate()->findOrFail($payment->getKey());
                    $previousStatus = $lockedPayment->status;

                    $lockedPayment->update([
                        'provider_reference' => (string) ($gatewayPayment['orderId'] ?? $payment->order->number),
                        'approval_url' => $gatewayPayment['payUrl'],
                        'expires_at' => now()->addSeconds((int) config('checkout.payment.qr_timeout_seconds', 180)),
                        'status' => 'pending',
                        'failure_reason' => null,
                        'payload' => [
                            'momo_request_id' => $requestId,
                            'momo_order_id' => $gatewayPayment['orderId'] ?? null,
                            'momo_request_type' => 'captureWallet',
                            // MoMo returns the QR payload as a string, not an image URL.
                            // Keep it so the order page can render a fresh QR locally.
                            'qr_code' => $gatewayPayment['qrCodeUrl'] ?? null,
                            'deeplink' => $gatewayPayment['deeplink'] ?? null,
                        ],
                    ]);
                    $lockedPayment->order()->update(['payment_status' => 'pending']);

                    if ($previousStatus !== 'pending') {
                        PaymentStatusHistory::query()->create([
                            'payment_id' => $lockedPayment->getKey(),
                            'from_status' => $previousStatus,
                            'to_status' => 'pending',
                            'changed_by' => null,
                            'note' => 'Khách hàng tạo lại phiên thanh toán MoMo.',
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

    private function recordFailure(Payment $payment, string $reason): void
    {
        DB::transaction(function () use ($payment, $reason): void {
            $lockedPayment = Payment::query()->lockForUpdate()->findOrFail($payment->getKey());
            $previousStatus = $lockedPayment->status;
            $lockedPayment->update(['status' => 'failed', 'failure_reason' => mb_substr($reason, 0, 2000), 'approval_url' => null]);
            $lockedPayment->order()->update(['payment_status' => 'failed']);

            if ($previousStatus !== 'failed') {
                PaymentStatusHistory::query()->create([
                    'payment_id' => $lockedPayment->getKey(), 'from_status' => $previousStatus, 'to_status' => 'failed', 'changed_by' => null,
                    'note' => 'Không thể khởi tạo giao dịch MoMo.',
                ]);
            }
        });
    }
}
