<?php

namespace App\Modules\Orders\Actions;

use App\Modules\Orders\Models\Order;
use App\Modules\Orders\Models\Payment;
use App\Modules\Orders\Models\PaymentStatusHistory;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class FailPaymentAttemptAction
{
    public function execute(
        Payment $payment,
        string $reason,
        string $status = 'failed',
        ?int $actorId = null,
    ): Payment {
        if (! in_array($status, ['failed', 'expired'], true)) {
            throw ValidationException::withMessages([
                'payment' => 'Trạng thái kết thúc phiên thanh toán không hợp lệ.',
            ]);
        }

        return DB::transaction(function () use ($payment, $reason, $status, $actorId): Payment {
            $lockedPayment = Payment::query()->lockForUpdate()->findOrFail($payment->getKey());
            $lockedOrder = Order::query()->lockForUpdate()->findOrFail($lockedPayment->order_id);

            if ($lockedPayment->status === 'paid') {
                return $lockedPayment->setRelation('order', $lockedOrder);
            }

            $previousStatus = $lockedPayment->status;
            $lockedPayment->update([
                'status' => $status,
                'failure_reason' => mb_substr($reason, 0, 2000),
                'approval_url' => null,
                'expires_at' => now(),
            ]);

            $latestPaymentId = Payment::query()
                ->where('order_id', $lockedOrder->getKey())
                ->max('id');

            if ((int) $latestPaymentId === (int) $lockedPayment->getKey() && $lockedOrder->status !== 'cancelled') {
                $lockedOrder->update(['payment_status' => $status]);
            }

            if ($previousStatus !== $status) {
                PaymentStatusHistory::query()->create([
                    'payment_id' => $lockedPayment->getKey(),
                    'from_status' => $previousStatus,
                    'to_status' => $status,
                    'changed_by' => $actorId,
                    'note' => $reason,
                ]);
            }

            return $lockedPayment->fresh('order');
        });
    }
}
