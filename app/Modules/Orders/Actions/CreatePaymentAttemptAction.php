<?php

namespace App\Modules\Orders\Actions;

use App\Modules\Orders\Models\Order;
use App\Modules\Orders\Models\Payment;
use App\Modules\Orders\Models\PaymentStatusHistory;
use App\Modules\Orders\Support\PaymentMethodCatalog;

class CreatePaymentAttemptAction
{
    public function execute(
        Order $order,
        string $paymentMethodCode,
        ?int $actorId,
        string $historyNote,
    ): Payment {
        $paymentMethod = PaymentMethodCatalog::get($paymentMethodCode);
        $requiresShortTimeout = (bool) $paymentMethod['requires_qr']
            || in_array($paymentMethod['provider'], ['momo', 'vnpay', 'payos'], true);

        $payment = $order->payments()->create([
            'provider' => $paymentMethod['provider'],
            'provider_reference' => null,
            'amount' => $order->total,
            'currency' => $order->currency,
            'status' => $paymentMethod['initial_status'],
            'expires_at' => $paymentMethod['initial_status'] === 'pending' && $paymentMethod['provider'] !== 'paypal'
                ? now()->addSeconds($requiresShortTimeout
                    ? (int) config('checkout.payment.qr_timeout_seconds', 180)
                    : ((int) config('checkout.voucher.pending_minutes', 30) * 60))
                : null,
            'payload' => $this->paymentPayload($paymentMethodCode, $paymentMethod),
        ]);

        PaymentStatusHistory::query()->create([
            'payment_id' => $payment->getKey(),
            'from_status' => null,
            'to_status' => $payment->status,
            'changed_by' => $actorId,
            'note' => $historyNote,
        ]);

        return $payment;
    }

    /** @param array<string, mixed> $paymentMethod */
    private function paymentPayload(string $paymentMethodCode, array $paymentMethod): ?array
    {
        if (! $paymentMethod['is_simulated']) {
            return null;
        }

        return [
            'payment_method' => $paymentMethodCode,
            'integration_status' => 'pending_gateway_integration',
            'message' => $paymentMethod['confirmation_description'],
        ];
    }
}
