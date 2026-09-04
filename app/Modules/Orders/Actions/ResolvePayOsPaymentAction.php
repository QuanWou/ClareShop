<?php

namespace App\Modules\Orders\Actions;

use App\Modules\Orders\Models\Order;
use App\Modules\Orders\Models\Payment;

class ResolvePayOsPaymentAction
{
    /** @return array<string, mixed>|null */
    public function execute(Order $order, ?Payment $payment): ?array
    {
        if ($order->status !== 'pending'
            || $order->payment_method !== 'bank_transfer'
            || $payment?->provider !== 'payos'
            || ! in_array($order->payment_status, ['unpaid', 'pending'], true)
            || $payment->expires_at?->isPast()) {
            return null;
        }

        $payload = $payment->payload;
        $requiredKeys = ['qr_code', 'checkout_url', 'payment_link_id', 'transfer_content', 'amount'];

        if (! is_array($payload)
            || ! collect($requiredKeys)->every(fn (string $key) => filled($payload[$key] ?? null))) {
            return null;
        }

        return $payload;
    }
}
