<?php

namespace App\Modules\Orders\Data;

use App\Modules\Orders\Models\Order;
use App\Modules\Orders\Models\Payment;
use App\Modules\Shared\Support\Money;

readonly class CreatedOrderData
{
    public function __construct(
        public Order $order,
        public Payment $payment,
        public ?VietQrPaymentData $vietQr,
    ) {}

    public function toArray(): array
    {
        return [
            'order' => [
                'number' => $this->order->number,
                'status' => $this->order->status,
                'payment_method' => $this->order->payment_method,
                'payment_status' => $this->order->payment_status,
                'subtotal' => (int) $this->order->subtotal,
                'shipping_fee' => (int) $this->order->shipping_fee,
                'discount_total' => (int) $this->order->discount_total,
                'total' => (int) $this->order->total,
                'total_formatted' => Money::formatVnd($this->order->total),
                'currency' => $this->order->currency,
            ],
            'shipping' => [
                'provider' => $this->order->shipping_provider,
                'service' => $this->order->shipping_service,
                'quote_id' => $this->order->shipping_quote_id,
                'fee_is_estimated' => $this->order->shipping_fee_is_estimated,
                'estimated_days' => $this->order->shipping_estimated_days,
                'estimated_delivery_at' => $this->order->estimated_delivery_at?->toIso8601String(),
            ],
            'payment' => [
                'provider' => $this->payment->provider,
                'status' => $this->payment->status,
                'amount' => (int) $this->payment->amount,
                'currency' => $this->payment->currency,
                'vietqr' => $this->vietQr?->toArray(),
            ],
        ];
    }
}
