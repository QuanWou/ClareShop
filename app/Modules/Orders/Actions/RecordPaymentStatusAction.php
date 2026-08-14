<?php

namespace App\Modules\Orders\Actions;

use App\Modules\Orders\Models\Order;
use App\Modules\Orders\Models\Payment;
use App\Modules\Orders\Models\PaymentStatusHistory;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RecordPaymentStatusAction
{
    public function execute(Order $order, Payment $payment, int $actorId, string $nextStatus, string $note): Payment
    {
        return DB::transaction(function () use ($order, $payment, $actorId, $nextStatus, $note): Payment {
            $lockedOrder = Order::query()->lockForUpdate()->findOrFail($order->getKey());
            $lockedPayment = Payment::query()->lockForUpdate()->findOrFail($payment->getKey());
            $currentStatus = $lockedPayment->status;

            if ($lockedPayment->order_id !== $lockedOrder->getKey()) {
                throw (new ModelNotFoundException)->setModel(Payment::class, [$payment->getKey()]);
            }

            $this->ensureTransitionIsAllowed($lockedOrder, $lockedPayment, $nextStatus);

            $lockedPayment->update([
                'status' => $nextStatus,
                'paid_at' => $nextStatus === 'paid' ? now() : $lockedPayment->paid_at,
            ]);

            $lockedOrder->update(['payment_status' => $nextStatus]);

            PaymentStatusHistory::query()->create([
                'payment_id' => $lockedPayment->getKey(),
                'from_status' => $currentStatus,
                'to_status' => $nextStatus,
                'changed_by' => $actorId,
                'note' => $note,
            ]);

            return $lockedPayment->fresh('statusHistories.changedBy');
        });
    }

    public function allowedNextStatuses(Order $order, Payment $payment): array
    {
        if ($payment->status === 'paid') {
            return ['refunded'];
        }

        if (in_array($payment->status, ['pending', 'unpaid'], true) && $order->status !== 'cancelled') {
            return ['paid'];
        }

        return [];
    }

    private function ensureTransitionIsAllowed(Order $order, Payment $payment, string $nextStatus): void
    {
        if (! in_array($nextStatus, $this->allowedNextStatuses($order, $payment), true)) {
            throw ValidationException::withMessages([
                'payment_status' => 'Không thể cập nhật thanh toán từ trạng thái hiện tại.',
            ]);
        }
    }
}
