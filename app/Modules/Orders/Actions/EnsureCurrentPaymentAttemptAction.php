<?php

namespace App\Modules\Orders\Actions;

use App\Modules\Orders\Models\Order;
use App\Modules\Orders\Models\Payment;
use App\Modules\Orders\Support\PaymentMethodCatalog;
use Illuminate\Validation\ValidationException;

class EnsureCurrentPaymentAttemptAction
{
    public function execute(Order $order, Payment $payment): void
    {
        $currentProvider = PaymentMethodCatalog::get($order->payment_method)['provider'];
        $latestPaymentId = Payment::query()
            ->where('order_id', $order->getKey())
            ->max('id');

        if (
            $order->status === 'cancelled'
            || $currentProvider !== $payment->provider
            || (int) $latestPaymentId !== (int) $payment->getKey()
        ) {
            throw ValidationException::withMessages([
                'payment' => 'Phiên thanh toán này không còn hiệu lực đối với đơn hàng.',
            ]);
        }
    }
}
