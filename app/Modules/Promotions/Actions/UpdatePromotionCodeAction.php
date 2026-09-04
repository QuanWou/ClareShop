<?php

namespace App\Modules\Promotions\Actions;

use App\Modules\Promotions\Models\PromotionCode;
use App\Modules\Promotions\Models\UserVoucher;
use Illuminate\Validation\ValidationException;

class UpdatePromotionCodeAction
{
    public function execute(PromotionCode $promotion, array $validated): PromotionCode
    {
        if (($validated['usage_limit'] ?? null) !== null && $validated['usage_limit'] < $promotion->usage_count) {
            throw ValidationException::withMessages([
                'usage_limit' => 'Giới hạn lượt dùng không thể thấp hơn '.$promotion->usage_count.' lượt đã sử dụng.',
            ]);
        }

        if (($validated['claim_limit'] ?? null) !== null && $validated['claim_limit'] < $promotion->claim_count) {
            throw ValidationException::withMessages([
                'claim_limit' => 'Giới hạn lượt nhận không thể thấp hơn '.$promotion->claim_count.' lượt đã nhận.',
            ]);
        }

        if (($validated['per_user_usage_limit'] ?? null) !== null) {
            $highestUsedCount = (int) UserVoucher::query()
                ->where('promotion_code_id', $promotion->getKey())
                ->max('used_count');

            if ($validated['per_user_usage_limit'] < $highestUsedCount) {
                throw ValidationException::withMessages([
                    'per_user_usage_limit' => 'Giới hạn cá nhân không thể thấp hơn lượt khách đã dùng ('.$highestUsedCount.').',
                ]);
            }
        }

        $promotion->update($validated);

        return $promotion->fresh();
    }
}
