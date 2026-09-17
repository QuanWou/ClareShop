<?php

namespace App\Modules\Billing\Actions;

use App\Modules\Billing\Models\PayLaterPurchase;
use App\Modules\Orders\Models\Payment;
use App\Modules\Orders\Models\PaymentStatusHistory;
use App\Modules\Promotions\Actions\RedeemOrderVoucherAction;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CompletePayLaterPurchaseAction
{
    public function __construct(
        private readonly RedeemOrderVoucherAction $redeemVoucher,
        private readonly DispatchPayLaterNotificationAction $notify,
    ) {}

    public function execute(PayLaterPurchase $purchase, string $method): PayLaterPurchase
    {
        $completed = DB::transaction(function () use ($purchase, $method): PayLaterPurchase {
            $locked = PayLaterPurchase::query()->with('order')->lockForUpdate()->findOrFail($purchase->getKey());

            if ($locked->status === 'paid') {
                return $locked;
            }
            if (! in_array($locked->status, [...PayLaterPurchase::PAYABLE_STATUSES, 'processing'], true)) {
                throw ValidationException::withMessages(['payment_method' => 'Khoản trả sau hiện chưa thể thanh toán.']);
            }

            $locked->update([
                'selected_payment_method' => $method,
                'status' => 'paid',
                'amount_due' => 0,
                'paid_at' => now(),
                'latest_failure_reason' => null,
            ]);
            $locked->order->update(['payment_status' => 'paid']);

            $payment = Payment::query()->where('order_id', $locked->order_id)->latest('id')->lockForUpdate()->first();
            if ($payment !== null && $payment->status !== 'paid') {
                $previousStatus = $payment->status;
                $payment->update(['status' => 'paid', 'paid_at' => now(), 'failure_reason' => null]);
                PaymentStatusHistory::query()->create([
                    'payment_id' => $payment->getKey(),
                    'from_status' => $previousStatus,
                    'to_status' => 'paid',
                    'changed_by' => $locked->user_id,
                    'note' => 'Khách hàng chủ động thanh toán khoản mua trước, trả sau bằng '.$method.'.',
                ]);
            }

            $this->redeemVoucher->execute($locked->order);

            return $locked->fresh(['user', 'order']);
        });

        $this->notify->execute($completed, 'paid');

        return $completed;
    }
}
