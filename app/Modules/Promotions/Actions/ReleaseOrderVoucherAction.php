<?php

namespace App\Modules\Promotions\Actions;

use App\Modules\Orders\Models\Order;
use App\Modules\Promotions\Models\PromotionCode;
use App\Modules\Promotions\Models\UserVoucher;
use App\Modules\Promotions\Models\VoucherReservation;
use Illuminate\Support\Facades\DB;

class ReleaseOrderVoucherAction
{
    public function execute(Order $order, string $reason): bool
    {
        return DB::transaction(function () use ($order, $reason): bool {
            $reservation = VoucherReservation::query()
                ->where('order_id', $order->getKey())
                ->lockForUpdate()
                ->first();

            if ($reservation === null || $reservation->status === VoucherReservation::STATUS_RELEASED) {
                return false;
            }

            if ($reservation->status === VoucherReservation::STATUS_REDEEMED) {
                $promotion = PromotionCode::query()->lockForUpdate()->find($reservation->promotion_code_id);

                if ($promotion !== null && $promotion->usage_count > 0) {
                    $promotion->decrement('usage_count');
                }

                if ($reservation->user_voucher_id !== null) {
                    $userVoucher = UserVoucher::query()->lockForUpdate()->find($reservation->user_voucher_id);

                    if ($userVoucher !== null && $userVoucher->used_count > 0) {
                        $userVoucher->decrement('used_count');
                    }
                }
            }

            $reservation->update([
                'status' => VoucherReservation::STATUS_RELEASED,
                'released_at' => now(),
                'release_reason' => mb_substr($reason, 0, 500),
            ]);

            return true;
        });
    }
}
