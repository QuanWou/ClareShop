<?php

namespace App\Modules\Orders\Actions;

use App\Modules\Orders\Gateways\PayPalClient;
use App\Modules\Orders\Models\Payment;
use App\Modules\Orders\Models\PaymentStatusHistory;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use RuntimeException;

class InitializePayPalPaymentAction
{
    public function __construct(
        private readonly PayPalClient $client,
        private readonly EnsureCurrentPaymentAttemptAction $ensureCurrentPaymentAttempt,
    ) {}

    public function execute(Payment $payment): Payment
    {
        if ($payment->provider !== 'paypal') {
            throw ValidationException::withMessages(['payment' => 'Thanh toán này không thuộc PayPal.']);
        }

        return Cache::lock("paypal-initialize:{$payment->getKey()}", 20)->block(5, function () use ($payment): Payment {
            $payment = Payment::query()->with('order')->findOrFail($payment->getKey());
            $this->ensureCurrentPaymentAttempt->execute($payment->order, $payment);

            if ($payment->status === 'paid') {
                return $payment;
            }

            if (
                filled($payment->approval_url)
                && $payment->expires_at?->isFuture()
                && $payment->status === 'pending'
            ) {
                return $payment;
            }

            $rate = (int) config('services.paypal.vnd_per_unit', 25000);
            $currency = strtoupper((string) config('services.paypal.currency', 'USD'));

            if ($rate <= 0 || strlen($currency) !== 3) {
                throw ValidationException::withMessages(['payment_method' => 'Cấu hình tỷ giá hoặc tiền tệ PayPal không hợp lệ.']);
            }

            $gatewayAmount = round(((float) $payment->amount) / $rate, 2, PHP_ROUND_HALF_UP);

            if ($gatewayAmount < 0.01) {
                throw ValidationException::withMessages(['payment_method' => 'Giá trị đơn hàng quá nhỏ để thanh toán qua PayPal.']);
            }

            try {
                $paypalOrder = $this->client->createOrder(
                    orderNumber: $payment->order->number,
                    paymentId: (int) $payment->getKey(),
                    amount: number_format($gatewayAmount, 2, '.', ''),
                    currency: $currency,
                    returnUrl: route('payments.paypal.return'),
                    cancelUrl: route('payments.paypal.cancel'),
                );

                $providerOrderId = data_get($paypalOrder, 'id');
                $approvalUrl = collect(data_get($paypalOrder, 'links', []))
                    ->firstWhere('rel', 'payer-action')['href']
                    ?? collect(data_get($paypalOrder, 'links', []))->firstWhere('rel', 'approve')['href']
                    ?? null;

                if (! is_string($providerOrderId) || ! is_string($approvalUrl)) {
                    throw new RuntimeException('PayPal không trả về mã đơn hoặc liên kết phê duyệt hợp lệ.');
                }

                return DB::transaction(function () use ($payment, $providerOrderId, $approvalUrl, $gatewayAmount, $currency, $rate, $paypalOrder): Payment {
                    $lockedPayment = Payment::query()->lockForUpdate()->findOrFail($payment->getKey());
                    $previousStatus = $lockedPayment->status;

                    $lockedPayment->update([
                        'provider_reference' => $providerOrderId,
                        'gateway_amount' => $gatewayAmount,
                        'gateway_currency' => $currency,
                        'exchange_rate' => $rate,
                        'approval_url' => $approvalUrl,
                        'expires_at' => now()->addMinutes((int) config('services.paypal.pending_minutes', 30)),
                        'status' => 'pending',
                        'failure_reason' => null,
                        'payload' => [
                            'paypal_order_status' => data_get($paypalOrder, 'status'),
                            'conversion_note' => "1 {$currency} = {$rate} VND",
                        ],
                    ]);

                    if ($previousStatus !== 'pending') {
                        PaymentStatusHistory::query()->create([
                            'payment_id' => $lockedPayment->getKey(),
                            'from_status' => $previousStatus,
                            'to_status' => 'pending',
                            'changed_by' => null,
                            'note' => 'Khởi tạo lại giao dịch PayPal.',
                        ]);
                    }

                    $lockedPayment->order()->update(['payment_status' => 'pending']);

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
                    'note' => 'Không thể khởi tạo giao dịch PayPal.',
                ]);
            }
        });
    }
}
