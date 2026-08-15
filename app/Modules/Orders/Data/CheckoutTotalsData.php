<?php

namespace App\Modules\Orders\Data;

use App\Modules\Promotions\Data\PromotionDiscountData;
use App\Modules\Shared\Support\Money;

readonly class CheckoutTotalsData
{
    /** @param array<int, CheckoutLineData> $lines @param array<int, ShippingQuoteData> $shippingOptions */
    public function __construct(
        public array $lines,
        public int $subtotal,
        public ShippingQuoteData $shipping,
        public array $shippingOptions,
        public PromotionDiscountData $discount,
        public int $total,
    ) {}

    public function toArray(): array
    {
        return [
            'items' => array_map(fn (CheckoutLineData $line) => $line->toArray(), $this->lines),
            'subtotal' => $this->subtotal,
            'subtotal_formatted' => Money::formatVnd($this->subtotal),
            'shipping' => $this->shipping->toArray(),
            'shipping_options' => array_map(fn (ShippingQuoteData $shipping) => $shipping->toArray(), $this->shippingOptions),
            'discount' => $this->discount->toArray(),
            'discount_total' => $this->discount->amount,
            'discount_total_formatted' => Money::formatVnd($this->discount->amount),
            'total' => $this->total,
            'total_formatted' => Money::formatVnd($this->total),
            'currency' => config('commerce.currency'),
        ];
    }
}
