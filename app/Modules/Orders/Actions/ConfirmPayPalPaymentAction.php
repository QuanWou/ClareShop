<?php

namespace App\Modules\Orders\Actions;

use App\Modules\Orders\Models\Order;
use App\Modules\Orders\Models\Payment;
use App\Modules\Orders\Models\PaymentStatusHistory;
use App\Modules\Promotions\Actions\RedeemOrderVoucherAction;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ConfirmPayPalPaymentAction
{
    public function __construct(
        private readonly SendOrderConfirmationAction $sendConfirmation,
        private readonly RedeemOrderVoucherAction $redeemVoucher,
        private readonly EnsureCurrentPaymentAttemptAction $ensureCurrentPaymentAttempt,
    ) {}

    public function execute(
        Payment $payment,
        string $transactionId,
        string $amount,
        string $currency,
        string $source,
    ): Payment {
        $confirmedPayment = DB::transaction(function () use ($payment, $transactionId, $amount, $currency, $source): Payment {
            $lockedPayment = Payment::query()->lockForUpdate()->findOrFail($payment->getKey());
            $lockedOrder = Order::query()->lockForUpdate()->findOrFail($lockedPayment->order_id);

            if ($lockedPayment->provider !== 'paypal') {
                throw ValidationException::withMessages(['payment' => 'Thanh toán này không thuộc PayPal.']);
            }

            $this->ensureCurrentPaymentAttempt->execute($lockedOrder, $lockedPayment);

            if ($lockedPayment->status === 'paid') {
                return $lockedPayment->setRelation('order', $lockedOrder);
            }

            if (
                strtoupper($currency) !== strtoupper((string) $lockedPayment->gateway_currency)
                || abs(((float) $amount) - ((float) $lockedPayment->gateway_amount)) > 0.001
            ) {
                throw ValidationException::withMessages([
                    'payment' => 'Số tiền hoặc tiền tệ PayPal không khớp với giao dịch Clare.',
                ]);
            }

            $previousStatus = $lockedPayment->status;
            $payload = $lockedPayment->payload ?? [];
            $payload['confirmation_source'] = $source;

            $lockedPayment->update([
                'provider_transaction_id' => $transactionId,
                'status' => 'paid',
                'paid_at' => now(),
                'webhook_confirmed_at' => $source === 'webhook' ? now() : $lockedPayment->webhook_confirmed_at,
                'failure_reason' => null,
                'payload' => $payload,
            ]);
            $lockedOrder->update(['payment_status' => 'paid']);
            $this->redeemVoucher->execute($lockedOrder);

            PaymentStatusHistory::query()->create([
                'payment_id' => $lockedPayment->getKey(),
                'from_status' => $previousStatus,
                'to_status' => 'paid',
                'changed_by' => null,
                'note' => $source === 'webhook'
                    ? 'PayPal webhook đã xác minh thanh toán thành công.'
                    : 'PayPal Capture API đã xác nhận thanh toán thành công.',
            ]);

            return $lockedPayment->fresh('order');
        });

        $this->sendConfirmation->execute($confirmedPayment->order);

        return $confirmedPayment;
    }
}
