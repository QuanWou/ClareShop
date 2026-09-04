<?php

namespace App\Modules\Orders\Actions;

use App\Modules\Orders\Models\Payment;

class ExpirePendingPayPalPaymentsAction
{
    public function __construct(
        private readonly FailPayPalPaymentAction $failPayment,
        private readonly TransitionOrderStatusAction $transitionOrder,
    ) {}

    public function execute(): int
    {
        $expired = 0;

        Payment::query()
            ->with('order')
            ->where('provider', 'paypal')
            ->where('status', 'pending')
            ->whereNotNull('expires_at')
            ->where('expires_at', '<=', now())
            ->whereHas('order', fn ($query) => $query->where('status', 'pending'))
            ->orderBy('id')
            ->chunkById(100, function ($payments) use (&$expired): void {
                foreach ($payments as $payment) {
                    $payment = $this->failPayment->execute(
                        $payment,
                        'Giao dịch PayPal đã quá thời hạn thanh toán.',
                        'expired',
                    );

                    $this->transitionOrder->execute(
                        order: $payment->order,
                        actorId: null,
                        nextStatus: 'cancelled',
                        note: 'Hệ thống tự động hủy đơn hết hạn thanh toán.',
                        cancelReason: 'Đơn đã quá thời hạn thanh toán PayPal.',
                    );

                    $expired++;
                }
            });

        return $expired;
    }
}
