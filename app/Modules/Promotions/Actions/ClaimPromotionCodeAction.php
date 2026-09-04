<?php

namespace App\Modules\Promotions\Actions;

use App\Models\User;
use App\Modules\Promotions\Models\PromotionCode;
use App\Modules\Promotions\Models\UserVoucher;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ClaimPromotionCodeAction
{
    public function execute(User $customer, PromotionCode $promotion): UserVoucher
    {
        return DB::transaction(function () use ($customer, $promotion): UserVoucher {
            $lockedPromotion = PromotionCode::query()->lockForUpdate()->findOrFail($promotion->getKey());
            $existing = UserVoucher::query()
                ->where('user_id', $customer->getKey())
                ->where('promotion_code_id', $lockedPromotion->getKey())
                ->lockForUpdate()
                ->first();

            if ($existing !== null) {
                return $existing;
            }

            if (! $lockedPromotion->isClaimableNow()) {
                throw ValidationException::withMessages([
                    'voucher' => 'Voucher này hiện chưa thể nhận hoặc đã hết số lượng.',
                ]);
            }

            $voucher = UserVoucher::query()->create([
                'user_id' => $customer->getKey(),
                'promotion_code_id' => $lockedPromotion->getKey(),
                'claimed_at' => now(),
            ]);

            $lockedPromotion->increment('claim_count');

            return $voucher->fresh('promotionCode');
        });
    }
}
