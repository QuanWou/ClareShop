<?php

namespace App\Modules\Orders\Actions;

use App\Modules\Orders\Models\Order;
use App\Modules\Orders\Models\Payment;
use App\Modules\Orders\Models\PaymentStatusHistory;
use App\Modules\Promotions\Actions\RedeemOrderVoucherAction;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ConfirmPayOsPaymentAction
{
    public function __construct(
        private readonly SendOrderConfirmationAction $sendConfirmation,
        private readonly RedeemOrderVoucherAction $redeemVoucher,
        private readonly EnsureCurrentPaymentAttemptAction $ensureCurrentPaymentAttempt,
    ) {}

    public function execute(Payment $payment, string $transactionId, int $amount, string $source = 'webhook'): Payment
    {
        $confirmedPayment = DB::transaction(function () use ($payment, $transactionId, $amount, $source): Payment {
            $lockedPayment = Payment::query()->lockForUpdate()->findOrFail($payment->getKey());
            $lockedOrder = Order::query()->lockForUpdate()->findOrFail($lockedPayment->order_id);

            if ($lockedPayment->provider !== 'payos') {
                throw ValidationException::withMessages(['payment' => 'Thanh toán này không thuộc payOS.']);
            }

            $this->ensureCurrentPaymentAttempt->execute($lockedOrder, $lockedPayment);

            if ($lockedPayment->status === 'paid') {
                return $lockedPayment->setRelation('order', $lockedOrder);
            }

            if ($amount !== (int) $lockedPayment->amount) {
                throw ValidationException::withMessages(['payment' => 'Số tiền payOS không khớp với tổng đơn hàng Clare.']);
            }

            $previousStatus = $lockedPayment->status;
            $lockedPayment->update([
                'provider_transaction_id' => $transactionId,
                'status' => 'paid',
                'paid_at' => now(),
                'webhook_confirmed_at' => $source === 'webhook' ? now() : $lockedPayment->webhook_confirmed_at,
                'failure_reason' => null,
            ]);
            $lockedOrder->update(['payment_status' => 'paid']);
            $this->redeemVoucher->execute($lockedOrder);

            PaymentStatusHistory::query()->create([
                'payment_id' => $lockedPayment->getKey(),
                'from_status' => $previousStatus,
                'to_status' => 'paid',
                'changed_by' => null,
                'note' => $source === 'webhook'
                    ? 'Webhook payOS có chữ ký hợp lệ đã xác nhận thanh toán.'
                    : 'API payOS đã xác nhận thanh toán khi khách quay lại Clare.',
            ]);

            return $lockedPayment->fresh('order');
        });

        $this->sendConfirmation->execute($confirmedPayment->order);

        return $confirmedPayment;
    }
}
