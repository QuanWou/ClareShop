<?php

namespace App\Modules\Promotions\Actions;

use App\Modules\Orders\Actions\TransitionOrderStatusAction;
use App\Modules\Orders\Models\Order;
use App\Modules\Orders\Models\Payment;
use App\Modules\Orders\Models\PaymentStatusHistory;
use App\Modules\Promotions\Models\VoucherReservation;
use Illuminate\Support\Facades\DB;

class ExpirePendingVoucherReservationsAction
{
    public function __construct(private readonly TransitionOrderStatusAction $transitionOrder) {}

    public function execute(): int
    {
        $expired = 0;

        VoucherReservation::query()
            ->where('status', VoucherReservation::STATUS_RESERVED)
            ->whereNotNull('expires_at')
            ->where('expires_at', '<=', now())
            ->whereHas('order', fn ($query) => $query->where('status', 'pending'))
            ->whereHas('order.payments', fn ($query) => $query->whereNotIn('provider', ['paypal', 'cod'])->whereIn('status', ['pending', 'failed']))
            ->orderBy('id')
            ->chunkById(100, function ($reservations) use (&$expired): void {
                foreach ($reservations as $reservation) {
                    $order = $this->expireReservation($reservation);

                    if ($order === null) {
                        continue;
                    }

                    $this->transitionOrder->execute(
                        order: $order,
                        actorId: null,
                        nextStatus: 'cancelled',
                        note: 'Hệ thống tự động hủy đơn đã quá thời hạn giữ voucher.',
                        cancelReason: 'Đơn đã quá thời hạn thanh toán khi đang giữ voucher.',
                    );

                    $expired++;
                }
            });

        return $expired;
    }

    private function expireReservation(VoucherReservation $reservation): ?Order
    {
        return DB::transaction(function () use ($reservation): ?Order {
            $lockedReservation = VoucherReservation::query()->lockForUpdate()->find($reservation->getKey());

            if (
                $lockedReservation === null
                || $lockedReservation->status !== VoucherReservation::STATUS_RESERVED
                || $lockedReservation->expires_at === null
                || $lockedReservation->expires_at->isFuture()
            ) {
                return null;
            }

            $order = Order::query()->lockForUpdate()->find($lockedReservation->order_id);

            if ($order === null || $order->status !== 'pending') {
                return null;
            }

            $payment = Payment::query()
                ->where('order_id', $order->getKey())
                ->whereNotIn('provider', ['paypal', 'cod'])
                ->latest('id')
                ->lockForUpdate()
                ->first();

            if ($payment === null || ! in_array($payment->status, ['pending', 'failed'], true)) {
                return null;
            }

            $previousStatus = $payment->status;
            $payment->update([
                'status' => 'expired',
                'failure_reason' => 'Đã quá thời hạn thanh toán khi đang giữ voucher.',
            ]);
            $order->update(['payment_status' => 'expired']);

            PaymentStatusHistory::query()->create([
                'payment_id' => $payment->getKey(),
                'from_status' => $previousStatus,
                'to_status' => 'expired',
                'changed_by' => null,
                'note' => 'Hệ thống đánh dấu giao dịch hết hạn vì voucher đã hết thời gian giữ.',
            ]);

            return $order->fresh();
        });
    }
}
