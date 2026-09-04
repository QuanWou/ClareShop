<?php

namespace App\Modules\Promotions\Actions;

use App\Models\User;
use App\Modules\Orders\Models\Order;
use App\Modules\Promotions\Data\PromotionDiscountData;
use App\Modules\Promotions\Models\VoucherReservation;

class ReservePromotionForOrderAction
{
    public function execute(Order $order, User $customer, PromotionDiscountData $discount): ?VoucherReservation
    {
        if (! $discount->isApplied()) {
            return null;
        }

        $expiresAt = $order->payment_method === 'cod'
            ? null
            : now()->addMinutes((int) config('checkout.voucher.pending_minutes', 30));

        return VoucherReservation::query()->create([
            'promotion_code_id' => $discount->promotion->getKey(),
            'user_id' => $customer->getKey(),
            'user_voucher_id' => $discount->userVoucher?->getKey(),
            'order_id' => $order->getKey(),
            'status' => VoucherReservation::STATUS_RESERVED,
            'discount_amount' => $discount->amount,
            'reserved_at' => now(),
            'expires_at' => $expiresAt,
        ]);
    }
}
