<?php

namespace App\Modules\Promotions\Actions;

use App\Modules\Orders\Models\Order;
use App\Modules\Promotions\Models\PromotionCode;
use App\Modules\Promotions\Models\UserVoucher;
use App\Modules\Promotions\Models\VoucherReservation;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RedeemOrderVoucherAction
{
    public function execute(Order $order): bool
    {
        return DB::transaction(function () use ($order): bool {
            $reservation = VoucherReservation::query()
                ->where('order_id', $order->getKey())
                ->lockForUpdate()
                ->first();

            if ($reservation === null || $reservation->status === VoucherReservation::STATUS_REDEEMED) {
                return $reservation !== null;
            }

            if ($reservation->status !== VoucherReservation::STATUS_RESERVED || ($reservation->expires_at?->isPast() ?? false)) {
                throw ValidationException::withMessages([
                    'voucher' => 'Voucher của đơn hàng này đã hết hiệu lực hoặc đã được giải phóng.',
                ]);
            }

            $promotion = PromotionCode::query()->lockForUpdate()->findOrFail($reservation->promotion_code_id);
            $userVoucher = $reservation->user_voucher_id === null
                ? null
                : UserVoucher::query()->lockForUpdate()->find($reservation->user_voucher_id);

            $promotion->increment('usage_count');

            if ($userVoucher !== null) {
                $userVoucher->increment('used_count');
                $userVoucher->update(['last_used_at' => now()]);
            }

            $reservation->update([
                'status' => VoucherReservation::STATUS_REDEEMED,
                'redeemed_at' => now(),
            ]);

            return true;
        });
    }
}
